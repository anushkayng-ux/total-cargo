<?php

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\ClientAuth;
use App\Models\ClientUserInviteModel;
use App\Models\ClientUserModel;

class AuthController extends Controller
{
    protected ClientAuth $clientAuth;
    protected $session;
    protected $helpers = ['form', 'url'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->session    = service('session');
        $this->clientAuth = new ClientAuth();
    }

    public function login()
    {
        return view('portal/auth/login', [
            'error' => $this->session->getFlashdata('error'),
        ]);
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check your email and password.');
        }

        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');
        $result   = $this->clientAuth->attempt($email, $password);

        if (!$result['ok']) {
            // Collapse all reasons that would reveal whether an email is valid
            // into a single generic message — prevents email-enumeration.
            $msg = match ($result['reason'] ?? 'invalid') {
                'locked', 'wrong_password_locked'
                            => 'Account temporarily locked. Try again in ' . ClientAuth::LOCK_MINUTES . ' minutes.',
                default     => 'Invalid email or password.',
            };
            return redirect()->back()->withInput()->with('error', $msg);
        }

        if (!empty($result['must_change_password'])) {
            return redirect()->to(site_url('portal/profile/change-password'))
                ->with('success', 'Welcome — please set a new password to continue.');
        }
        return redirect()->to(site_url('portal'));
    }

    public function logout()
    {
        $this->clientAuth->logout();
        return redirect()->to(site_url('portal/login'))->with('success', 'Signed out.');
    }

    public function forgotForm()
    {
        return view('portal/auth/forgot');
    }

    /**
     * Issues a single-use reset token (NOT a temp password).
     * The user clicks the emailed link → resetForm() → resetSubmit() to set a new password.
     * No password is changed in DB before the user clicks the link, so a failed email
     * never locks anyone out.
     */
    public function forgotSubmit()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Please enter a valid email address.');
        }

        $userModel = new ClientUserModel();
        $user = $userModel->findActiveByEmail($email);

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));      // 256 bits
            $hash     = hash('sha256', $rawToken);
            (new \App\Models\ClientUserResetModel())->insert([
                'client_user_id' => (int) $user['id'],
                'token_hash'     => $hash,
                'expires_at'     => date('Y-m-d H:i:s', time() + 60 * 60),
                'created_at'     => date('Y-m-d H:i:s'),
                'ip_address'     => $this->request->getIPAddress(),
            ]);
            $resetUrl = site_url('portal/reset/' . $rawToken);
            try {
                (new \App\Libraries\EmailService())->sendTemplate('portal_password_reset', $email, [
                    'name'      => $user['name'],
                    'reset_url' => $resetUrl,
                    'login_url' => site_url('portal/login'),
                    0 => $user['name'],
                    1 => $resetUrl,
                    2 => site_url('portal/login'),
                ], [
                    'to_name'           => $user['name'],
                    'related_module'    => 'client_user',
                    'related_id'        => (int) $user['id'],
                    'related_client_id' => (int) $user['client_id'],
                    'bypass_suppression'=> true,
                ]);
            } catch (\Throwable $e) {
                log_message('warning', 'Portal password reset email failed: ' . $e->getMessage());
            }
        }

        return redirect()->to(site_url('portal/login'))
            ->with('success', 'If that email exists, we have sent password reset instructions.');
    }

    /** GET /portal/reset/:token — show the new-password form if the token is valid. */
    public function resetForm(string $rawToken)
    {
        $hash  = hash('sha256', $rawToken);
        $reset = (new \App\Models\ClientUserResetModel())->findValidByHash($hash);
        if (!$reset) {
            return view('portal/auth/reset_invalid');
        }
        return view('portal/auth/reset', [
            'token' => $rawToken,
            'error' => $this->session->getFlashdata('error'),
        ]);
    }

    /** POST /portal/reset/:token — set the new password and consume the token. */
    public function resetSubmit(string $rawToken)
    {
        $hash  = hash('sha256', $rawToken);
        $resetModel = new \App\Models\ClientUserResetModel();
        $reset = $resetModel->findValidByHash($hash);
        if (!$reset) {
            return redirect()->to(site_url('portal/login'))->with('error', 'Reset link is invalid or expired.');
        }

        $rules = [
            'password'         => 'required|min_length[8]|max_length[64]',
            'password_confirm' => 'required|matches[password]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters and match the confirmation.');
        }

        $userModel = new ClientUserModel();
        $userModel->update((int) $reset['client_user_id'], [
            'password_hash'        => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'must_change_password' => 0,
            'failed_attempts'      => 0,
            'locked_until'         => null,
        ]);
        $resetModel->update($reset['id'], ['used_at' => date('Y-m-d H:i:s')]);
        // Invalidate any other unused tokens for this user
        $resetModel->where('client_user_id', $reset['client_user_id'])
            ->where('used_at', null)
            ->set(['used_at' => date('Y-m-d H:i:s')])
            ->update();

        return redirect()->to(site_url('portal/login'))
            ->with('success', 'Password updated. Please sign in.');
    }

    /**
     * Invite acceptance: GET shows a form, POST sets a password and creates the client_user.
     */
    public function acceptInvite(string $token)
    {
        $invite = (new ClientUserInviteModel())->findByToken($token);
        if (!$invite || !empty($invite['accepted_at'])) {
            return view('portal/auth/invite_invalid');
        }
        if (!empty($invite['expires_at']) && strtotime($invite['expires_at']) < time()) {
            return view('portal/auth/invite_invalid', ['expired' => true]);
        }
        return view('portal/auth/accept_invite', [
            'invite' => $invite,
            'token'  => $token,
            'error'  => $this->session->getFlashdata('error'),
        ]);
    }

    public function submitInvite(string $token)
    {
        $inviteModel = new ClientUserInviteModel();
        $invite = $inviteModel->findByToken($token);
        if (!$invite || !empty($invite['accepted_at'])) {
            return redirect()->to(site_url('portal/login'))->with('error', 'Invite invalid.');
        }
        if (!empty($invite['expires_at']) && strtotime($invite['expires_at']) < time()) {
            return redirect()->to(site_url('portal/login'))->with('error', 'Invite expired. Please request a new one.');
        }

        $rules = [
            'password'         => 'required|min_length[8]|max_length[64]',
            'password_confirm' => 'required|matches[password]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters and match the confirmation.');
        }

        $userModel = new ClientUserModel();
        if ($userModel->where('email', $invite['email'])->countAllResults() > 0) {
            return redirect()->to(site_url('portal/login'))->with('error', 'A login already exists for this email.');
        }

        $userModel->insert([
            'client_id'            => $invite['client_id'],
            'name'                 => $invite['name'],
            'email'                => $invite['email'],
            'mobile'               => $invite['mobile'],
            'password_hash'        => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'portal_role'          => $invite['portal_role'],
            'status'               => 1,
            'must_change_password' => 0,
            'failed_attempts'      => 0,
        ]);

        $inviteModel->update($invite['id'], ['accepted_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(site_url('portal/login'))
            ->with('success', 'Account created. Please sign in.');
    }
}

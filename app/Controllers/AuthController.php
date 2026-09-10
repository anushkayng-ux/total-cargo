<?php

namespace App\Controllers;

class AuthController extends BaseController
{
    public function login()
    {
        return view('auth/login', [
            'error' => $this->session->getFlashdata('error'),
        ]);
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check your email and password.');
        }

        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $result = $this->auth->attemptWithReason($email, $password);
        if (empty($result['ok'])) {
            // 2FA required — go to the code prompt
            if (($result['reason'] ?? '') === 'pending_2fa') {
                return redirect()->to(site_url('login/2fa'));
            }
            $msg = match ($result['reason'] ?? '') {
                'locked'     => 'Account temporarily locked after repeated failed attempts. Try again in ' . \App\Libraries\Auth::LOCK_MINUTES . ' minutes.',
                'ip_blocked' => 'Sign-in from this IP is not permitted.',
                default      => 'Invalid credentials.',
            };
            return redirect()->back()->withInput()->with('error', $msg);
        }

        return $this->postLoginRedirect();
    }

    /** Show TOTP prompt after a password-only success. */
    public function twoFactorForm()
    {
        if (!$this->session->get('pending_2fa_user_id')) {
            return redirect()->to(site_url('login'));
        }
        return view('auth/login_2fa', ['error' => $this->session->getFlashdata('error')]);
    }

    public function twoFactorSubmit()
    {
        $code = (string) $this->request->getPost('code');
        $r = $this->auth->completeTwoFactor($code);
        if (!empty($r['ok'])) {
            return $this->postLoginRedirect();
        }
        if (($r['reason'] ?? '') === 'expired') {
            return redirect()->to(site_url('login'))->with('error', 'Sign-in attempt expired. Please start again.');
        }
        return redirect()->back()->with('error', 'Code did not verify. Please try again.');
    }

    public function logout()
    {
        $this->auth->logout();
        return redirect()->to(site_url('login'))->with('success', 'Signed out.');
    }

    /* ──────────────── Forgot-password flow (staff) ──────────────── */

    public function forgotForm()
    {
        return view('auth/forgot', ['error' => $this->session->getFlashdata('error')]);
    }

    public function forgotSubmit()
    {
        $email = trim((string) $this->request->getPost('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Enter a valid email address.');
        }

        $db = \Config\Database::connect();
        $user = $db->table('users')
            ->where('email', $email)->where('status', 1)->where('deleted_at', null)
            ->get()->getRowArray();

        // Always show the same success page (do not leak whether the email exists)
        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $hash  = hash('sha256', $token);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $db->table('password_reset_tokens')->insert([
                    'user_id'    => (int) $user['id'],
                    'token_hash' => $hash,
                    'expires_at' => $expires,
                    'ip_address' => (string) ($this->request->getIPAddress() ?: ''),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $resetLink = site_url('reset/' . $token);
                (new \App\Libraries\EmailService())->sendTemplate('staff_password_reset', $email, [
                    'name'       => $user['name'] ?? 'there',
                    'reset_link' => $resetLink,
                    'expires'    => '1 hour',
                ], [
                    'related_module' => 'users',
                    'related_id'     => (int) $user['id'],
                    'bypass_suppression' => true,
                ]);
            } catch (\Throwable $e) {
                log_message('warning', 'Staff password reset failed: ' . $e->getMessage());
            }
        }
        return redirect()->to(site_url('login'))->with('success', 'If that email exists, we\'ve sent reset instructions. Check your inbox (and spam folder).');
    }

    public function resetForm(string $token)
    {
        $row = $this->findResetToken($token);
        if (!$row) {
            return view('auth/reset_invalid');
        }
        return view('auth/reset', ['token' => $token, 'error' => $this->session->getFlashdata('error')]);
    }

    public function resetSubmit(string $token)
    {
        $row = $this->findResetToken($token);
        if (!$row) return view('auth/reset_invalid');

        $pw1 = (string) $this->request->getPost('password');
        $pw2 = (string) $this->request->getPost('password_confirm');
        if (strlen($pw1) < 10 || $pw1 !== $pw2) {
            return redirect()->back()->with('error', 'Passwords must match and be at least 10 characters.');
        }
        if (\App\Libraries\PasswordHygiene::isBreached($pw1)) {
            return redirect()->back()->with('error', 'That password appears in a public breach list. Please pick something unique.');
        }

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('users')->where('id', (int) $row['user_id'])->update([
            'password_hash'    => password_hash($pw1, PASSWORD_BCRYPT),
            'failed_attempts'  => 0,
            'locked_until'     => null,
            'updated_at'       => $now,
        ]);
        // Burn the token and any other unused tokens for this user
        $db->table('password_reset_tokens')
            ->where('user_id', (int) $row['user_id'])
            ->where('used_at', null)
            ->update(['used_at' => $now]);

        return redirect()->to(site_url('login'))->with('success', 'Password updated. Please sign in.');
    }

    /** @return array|null  the password_reset_tokens row if usable, else null. */
    private function findResetToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $hash = hash('sha256', $token);
        $row = \Config\Database::connect()->table('password_reset_tokens')
            ->where('token_hash', $hash)->where('used_at', null)
            ->get()->getRowArray();
        if (!$row) return null;
        if (strtotime((string) $row['expires_at']) < time()) return null;
        return $row;
    }

    /**
     * Decide where to send the user after a successful login / 2FA. Honour an
     * intended URL stashed by AuthFilter (so deep links survive a login round
     * trip) and fall back to dashboard / super-admin console.
     */
    private function postLoginRedirect()
    {
        $intended = (string) $this->session->get('intended_url');
        $this->session->remove('intended_url');
        if ($intended !== '' && $this->isSafeRedirectUrl($intended)) {
            return redirect()->to($intended);
        }
        return redirect()->to($this->auth->isSuperAdmin() ? site_url('sys') : site_url('dashboard'));
    }

    /** Only allow redirects to URLs on this app's host. */
    private function isSafeRedirectUrl(string $url): bool
    {
        $host = parse_url(base_url(), PHP_URL_HOST);
        $target = parse_url($url, PHP_URL_HOST);
        return $target !== null && $host !== null && strcasecmp($host, $target) === 0;
    }
}

<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\ClientUserModel;
use App\Models\ClientUserInviteModel;

/**
 * Staff-side management of portal user accounts for a given client.
 * Routes are namespaced under /clients/:client_id/portal-users.
 */
class ClientUsersController extends BaseController
{
    public function index(int $clientId)
    {
        $client = (new ClientModel())->find($clientId);
        if (!$client) {
            return redirect()->to(site_url('clients'))->with('error', 'Client not found.');
        }

        $users   = (new ClientUserModel())->where('client_id', $clientId)->orderBy('id', 'DESC')->find();
        $invites = (new ClientUserInviteModel())
            ->where('client_id', $clientId)
            ->where('accepted_at', null)
            ->orderBy('id', 'DESC')
            ->find();

        return $this->render('clients/portal_users', [
            'pageTitle' => 'Client Master [General Masters] — Portal Users',
            'client'    => $client,
            'users'     => $users,
            'invites'   => $invites,
            'roles'     => ClientUserModel::ROLES,
        ], retroFixedShell: true);
    }

    public function invite(int $clientId)
    {
        $client = (new ClientModel())->find($clientId);
        if (!$client) {
            return redirect()->to(site_url('clients'))->with('error', 'Client not found.');
        }
        if ((int) ($client['portal_enabled'] ?? 0) !== 1) {
            return redirect()->back()->with('error', 'Enable portal access on the client first.');
        }

        $rules = [
            'name'        => 'required|min_length[2]|max_length[120]',
            'email'       => 'required|valid_email|max_length[150]',
            'mobile'      => 'permit_empty|max_length[20]',
            'portal_role' => 'required|in_list[Owner,Booker,Accounts,Viewer]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        if ((new ClientUserModel())->where('email', $email)->countAllResults() > 0) {
            return redirect()->back()->with('error', 'A login already exists for this email.');
        }

        $token = bin2hex(random_bytes(24));
        (new ClientUserInviteModel())->insert([
            'client_id'   => $clientId,
            'name'        => trim((string) $this->request->getPost('name')),
            'email'       => $email,
            'mobile'      => trim((string) $this->request->getPost('mobile')),
            'portal_role' => (string) $this->request->getPost('portal_role'),
            'token'       => $token,
            'expires_at'  => date('Y-m-d H:i:s', time() + 7 * 86400),
            'invited_by'  => $this->auth->id(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $url = site_url('portal/invite/' . $token);

        // Try to send invite email — surface honest result to staff
        $emailStatus = 'no_driver';
        try {
            $vars = [
                'name'       => trim((string) $this->request->getPost('name')),
                'company'    => $client['company_name'],
                'invite_url' => $url,
                0 => trim((string) $this->request->getPost('name')),
                1 => $client['company_name'],
                2 => $url,
            ];
            $res = (new \App\Libraries\EmailService())->sendTemplate('portal_invite', $email, $vars, [
                'to_name'           => trim((string) $this->request->getPost('name')),
                'related_module'    => 'client_user_invite',
                'related_client_id' => $client['id'],
                'sent_by_user_id'   => $this->auth->id(),
                'bypass_suppression'=> true,
            ]);
            $emailStatus = !empty($res['ok']) ? 'sent'
                : (!empty($res['queued']) ? 'queued'
                : (!empty($res['suppressed']) ? 'suppressed' : 'failed'));
        } catch (\Throwable $e) {
            log_message('warning', 'Portal invite email failed: ' . $e->getMessage());
            $emailStatus = 'failed';
        }

        $msg = match ($emailStatus) {
            'sent'       => 'Invite created and email sent. Backup link (valid 7 days): ',
            'queued'     => 'Invite created. Email queued (no driver configured yet) — share this link manually: ',
            'suppressed' => 'Invite created BUT recipient is on the suppression list. Share this link manually: ',
            'failed'     => 'Invite created BUT email send failed. Share this link manually: ',
            default      => 'Invite created. Share this link with the user (valid 7 days): ',
        };
        return redirect()->back()->with('success', $msg . $url);
    }

    public function toggle(int $clientId, int $userId)
    {
        $model = new ClientUserModel();
        $u = $model->find($userId);
        if (!$u || (int) $u['client_id'] !== $clientId) {
            return redirect()->to(site_url('clients/' . $clientId . '/portal-users'))->with('error', 'Not found.');
        }
        $model->update($userId, [
            'status'          => (int) $u['status'] === 1 ? 0 : 1,
            'failed_attempts' => 0,
            'locked_until'    => null,
        ]);
        return redirect()->back()->with('success', 'User status updated.');
    }

    public function resetPassword(int $clientId, int $userId)
    {
        $model = new ClientUserModel();
        $u = $model->find($userId);
        if (!$u || (int) $u['client_id'] !== $clientId) {
            return redirect()->to(site_url('clients/' . $clientId . '/portal-users'))->with('error', 'Not found.');
        }
        // Issue a single-use reset token instead of a brute-forceable temp password.
        $rawToken = bin2hex(random_bytes(32));
        (new \App\Models\ClientUserResetModel())->insert([
            'client_user_id' => (int) $userId,
            'token_hash'     => hash('sha256', $rawToken),
            'expires_at'     => date('Y-m-d H:i:s', time() + 60 * 60),
            'created_at'     => date('Y-m-d H:i:s'),
            'ip_address'     => $this->request->getIPAddress(),
        ]);
        $resetUrl = site_url('portal/reset/' . $rawToken);
        return redirect()->back()->with('success',
            "Password-reset link generated for {$u['email']} (valid 1 hour). Share with user: {$resetUrl}");
    }

    public function delete(int $clientId, int $userId)
    {
        $model = new ClientUserModel();
        $u = $model->find($userId);
        if ($u && (int) $u['client_id'] === $clientId) {
            $model->delete($userId); // soft delete
        }
        return redirect()->to(site_url('clients/' . $clientId . '/portal-users'))->with('success', 'User removed.');
    }
}

<?php

namespace App\Controllers\Portal;

use App\Models\ClientUserModel;
use App\Models\ClientUserLoginLogModel;
use App\Models\ClientModel;

class ProfileController extends BaseController
{
    public function index()
    {
        $user      = (new ClientUserModel())->find($this->clientAuth->id());
        $client    = (new ClientModel())->find($this->clientAuth->clientId());
        $loginLogs = (new ClientUserLoginLogModel())->recentForUser((int) $user['id'], 8);

        return $this->render('portal/profile/index', [
            'pageTitle' => 'My Profile',
            'user'      => $user,
            'client'    => $client,
            'loginLogs' => $loginLogs,
        ]);
    }

    public function saveNotifications()
    {
        $model = new ClientUserModel();
        $model->update($this->clientAuth->id(), [
            'notify_whatsapp' => $this->request->getPost('notify_whatsapp') ? 1 : 0,
            'notify_email'    => $this->request->getPost('notify_email') ? 1 : 0,
        ]);
        $this->audit('client_user', $this->clientAuth->id(), 'update_notifications');
        return redirect()->to(site_url('portal/profile'))->with('success', 'Notification preferences saved.');
    }

    public function changePasswordForm()
    {
        return $this->render('portal/profile/change_password', [
            'pageTitle' => 'Change Password',
        ]);
    }

    public function changePassword()
    {
        $rules = [
            'new_password'         => 'required|min_length[8]|max_length[64]',
            'new_password_confirm' => 'required|matches[new_password]',
        ];
        $user = (new ClientUserModel())->find($this->clientAuth->id());

        // If they already had a password (i.e., not first-login), require current password.
        if (!$user['must_change_password']) {
            $rules['current_password'] = 'required';
        }
        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if (!$user['must_change_password']) {
            $cur = (string) $this->request->getPost('current_password');
            if (empty($user['password_hash']) || !password_verify($cur, $user['password_hash'])) {
                return redirect()->back()->with('error', 'Current password is incorrect.');
            }
        }

        (new ClientUserModel())->update($user['id'], [
            'password_hash'        => password_hash((string) $this->request->getPost('new_password'), PASSWORD_BCRYPT),
            'must_change_password' => 0,
            'failed_attempts'      => 0,
            'locked_until'         => null,
        ]);
        $this->clientAuth->clearMustChange();
        $this->audit('client_user', (int) $user['id'], 'change_password');
        return redirect()->to(site_url('portal'))->with('success', 'Password updated.');
    }
}

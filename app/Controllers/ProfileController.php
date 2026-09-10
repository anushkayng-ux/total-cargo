<?php

namespace App\Controllers;

use App\Libraries\Totp;
use App\Models\UserModel;
use App\Models\SettingModel;

/**
 * Self-service profile + 2FA for any logged-in staff member.
 * Mirrors the super-admin TOTP flow (SysController) but lives at /profile so
 * regular users — not just super-admins — can enable a second factor.
 */
class ProfileController extends BaseController
{
    public function index()
    {
        $user   = (new UserModel())->find($this->auth->id());
        $issuer = (string) ((new SettingModel())->get('company_name', 'TPT Logistics') ?: 'TPT Logistics');
        $newSecret    = $this->session->get('totp_setup_secret');
        $provisioning = $newSecret ? Totp::provisioningUri($newSecret, $user['email'], $issuer) : null;

        return $this->render('profile/index', [
            'pageTitle'    => 'My Profile',
            'user'         => $user,
            'newSecret'    => $newSecret,
            'provisioning' => $provisioning,
        ]);
    }

    public function totpStart()
    {
        $secret = Totp::generateSecret();
        $this->session->set('totp_setup_secret', $secret);
        return redirect()->to(site_url('profile'))->with('success', 'Scan the QR or paste the secret into your authenticator, then enter the 6-digit code below to confirm.');
    }

    public function totpEnable()
    {
        $secret = (string) $this->session->get('totp_setup_secret');
        $code   = (string) $this->request->getPost('code');
        if ($secret === '') return redirect()->to(site_url('profile'))->with('error', 'Start 2FA setup first.');
        if (!Totp::verify($secret, $code)) {
            return redirect()->to(site_url('profile'))->with('error', 'That code did not verify. Please try again.');
        }
        (new UserModel())->update($this->auth->id(), [
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ]);
        $this->session->remove('totp_setup_secret');
        log_message('info', 'totp_enabled by user ' . $this->auth->id());
        return redirect()->to(site_url('profile'))->with('success', '2FA enabled. You will be asked for a code on next sign-in.');
    }

    public function totpDisable()
    {
        $code = (string) $this->request->getPost('code');
        $user = (new UserModel())->find($this->auth->id());
        if (empty($user['totp_enabled'])) {
            return redirect()->to(site_url('profile'))->with('error', '2FA is not enabled.');
        }
        if (!Totp::verify((string) $user['totp_secret'], $code)) {
            return redirect()->to(site_url('profile'))->with('error', 'Code did not verify.');
        }
        (new UserModel())->update($this->auth->id(), [
            'totp_secret'  => null,
            'totp_enabled' => 0,
        ]);
        log_message('info', 'totp_disabled by user ' . $this->auth->id());
        return redirect()->to(site_url('profile'))->with('success', '2FA disabled.');
    }

    /**
     * Change password — requires current password + a passing strength check.
     * Same HIBP breached-password check as fresh registrations.
     */
    public function changePassword()
    {
        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');

        if ($new !== $confirm) {
            return redirect()->to(site_url('profile'))->with('error', 'New password and confirmation do not match.');
        }
        if (strlen($new) < 10) {
            return redirect()->to(site_url('profile'))->with('error', 'New password must be at least 10 characters.');
        }
        $user = (new UserModel())->find($this->auth->id());
        if (!password_verify($current, (string) $user['password_hash'])) {
            return redirect()->to(site_url('profile'))->with('error', 'Current password is incorrect.');
        }

        // Refuse known-breached passwords (HIBP Pwned-Passwords k-anonymity)
        if (\App\Libraries\PasswordHygiene::isBreached($new)) {
            return redirect()->to(site_url('profile'))->with('error', 'That password appears in a public breach list. Pick something unique.');
        }

        (new UserModel())->update($this->auth->id(), [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
        ]);
        return redirect()->to(site_url('profile'))->with('success', 'Password updated.');
    }
}

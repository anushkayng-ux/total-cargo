<?php

namespace App\Libraries;

use App\Models\UserModel;
use App\Models\RolePermissionModel;

class Auth
{
    public const ATTEMPT_LIMIT = 5;
    public const LOCK_MINUTES  = 15;

    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    /**
     * Returns one of:
     *   ['ok'=>true]
     *   ['ok'=>false, 'reason'=>'invalid'|'locked'|'ip_blocked']
     *   ['ok'=>false, 'reason'=>'pending_2fa', 'user_id'=>N]
     */
    public function attemptWithReason(string $email, string $password): array
    {
        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);
        if (!$user) return ['ok' => false, 'reason' => 'invalid'];

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['ok' => false, 'reason' => 'locked'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int) ($user['failed_attempts'] ?? 0) + 1;
            $update   = ['failed_attempts' => $attempts];
            if ($attempts >= self::ATTEMPT_LIMIT) {
                $update['locked_until'] = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
            }
            $userModel->update($user['id'], $update);
            return ['ok' => false, 'reason' => 'invalid'];
        }

        // Super-admin IP allowlist enforcement
        if ((int) ($user['is_super_admin'] ?? 0) === 1 && !self::ipAllowed()) {
            return ['ok' => false, 'reason' => 'ip_blocked'];
        }

        // 2FA step — defer login completion if TOTP is enabled for this user
        if ((int) ($user['totp_enabled'] ?? 0) === 1) {
            // Stash a short-lived pending state; do NOT mark as logged-in yet
            $this->session->set([
                'pending_2fa_user_id' => (int) $user['id'],
                'pending_2fa_at'      => time(),
            ]);
            return ['ok' => false, 'reason' => 'pending_2fa', 'user_id' => (int) $user['id']];
        }

        $this->completeLogin($user);
        return ['ok' => true];
    }

    /** Finalize a 2FA challenge — verifies TOTP and completes the login. */
    public function completeTwoFactor(string $code): array
    {
        $userId = (int) $this->session->get('pending_2fa_user_id');
        $at     = (int) $this->session->get('pending_2fa_at');
        if (!$userId || !$at || (time() - $at) > 300) {
            return ['ok' => false, 'reason' => 'expired'];
        }
        $user = (new UserModel())->find($userId);
        if (!$user || empty($user['totp_secret'])) {
            return ['ok' => false, 'reason' => 'invalid'];
        }
        if (!\App\Libraries\Totp::verify($user['totp_secret'], $code)) {
            // Bump failed attempts; lock if too many
            $attempts = (int) ($user['failed_attempts'] ?? 0) + 1;
            $update   = ['failed_attempts' => $attempts];
            if ($attempts >= self::ATTEMPT_LIMIT) {
                $update['locked_until'] = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
            }
            (new UserModel())->update($userId, $update);
            return ['ok' => false, 'reason' => 'invalid_code'];
        }
        $this->session->remove(['pending_2fa_user_id', 'pending_2fa_at']);
        $this->completeLogin($user);
        return ['ok' => true];
    }

    /** Check super-admin IP allowlist (CSV in settings.super_admin_ip_allowlist). Empty = open. */
    public static function ipAllowed(): bool
    {
        $list = (string) ((new \App\Models\SettingModel())->get('super_admin_ip_allowlist', '') ?? '');
        if ($list === '') return true;
        $ip = service('request')->getIPAddress();
        foreach (array_filter(array_map('trim', explode(',', $list))) as $entry) {
            if (self::ipMatches($ip, $entry)) return true;
        }
        return false;
    }

    private static function ipMatches(string $ip, string $rule): bool
    {
        if ($ip === $rule) return true;
        if (strpos($rule, '/') === false) return false;
        // CIDR (IPv4)
        [$subnet, $bits] = explode('/', $rule, 2);
        if (!ctype_digit($bits) || (int) $bits < 0 || (int) $bits > 32) return false;
        $ipLong   = ip2long($ip);
        $netLong  = ip2long($subnet);
        if ($ipLong === false || $netLong === false) return false;
        $mask = -1 << (32 - (int) $bits);
        return ($ipLong & $mask) === ($netLong & $mask);
    }

    /** Establishes the actual session keys after credentials + 2FA + IP all pass. */
    private function completeLogin(array $user): void
    {
        $userModel = new UserModel();
        $userModel->update($user['id'], [
            'last_login_at'   => date('Y-m-d H:i:s'),
            'last_login_ip'   => service('request')->getIPAddress(),
            'failed_attempts' => 0,
            'locked_until'    => null,
        ]);

        // Drop any portal-realm session keys before establishing staff session,
        // so a single browser cannot have both realms active simultaneously.
        foreach (['user_id','client_id','name','email','role','company','must_change','logged_in'] as $k) {
            $this->session->remove(\App\Libraries\ClientAuth::SESSION_PREFIX . $k);
        }
        $permissions = (new RolePermissionModel())->mapForRole((int) $user['role_id']);
        $this->session->regenerate();
        $this->session->set([
            'auth_user_id'        => (int) $user['id'],
            'auth_user_name'      => $user['name'],
            'auth_user_email'     => $user['email'],
            'auth_role_id'        => (int) $user['role_id'],
            'auth_perms'          => $permissions,
            'auth_is_super_admin' => (int) ($user['is_super_admin'] ?? 0) === 1,
            'auth_logged_in'      => true,
        ]);
    }

    /** Backwards-compatible boolean wrapper. */
    public function attempt(string $email, string $password): bool
    {
        $r = $this->attemptWithReason($email, $password);
        return !empty($r['ok']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->check() && (bool) $this->session->get('auth_is_super_admin');
    }

    /** Mark session as having step-up auth for the next $ttlSec seconds. */
    public function markStepUp(int $ttlSec = 600): void
    {
        $this->session->set('auth_step_up_until', time() + $ttlSec);
    }

    public function hasStepUp(): bool
    {
        return (int) $this->session->get('auth_step_up_until') > time();
    }

    public function clearStepUp(): void
    {
        $this->session->remove('auth_step_up_until');
    }

    /** Begin impersonation — switches the staff session to another user, remembering original. */
    public function startImpersonation(int $targetUserId): bool
    {
        if (!$this->isSuperAdmin()) return false;
        $target = (new UserModel())->find($targetUserId);
        if (!$target || (int) $target['status'] !== 1 || (int) ($target['is_super_admin'] ?? 0) === 1) return false;

        $impersonatorId = (int) $this->session->get('auth_user_id');
        $perms = (new RolePermissionModel())->mapForRole((int) $target['role_id']);
        $this->session->set([
            'auth_user_id'              => (int) $target['id'],
            'auth_user_name'            => $target['name'],
            'auth_user_email'           => $target['email'],
            'auth_role_id'              => (int) $target['role_id'],
            'auth_perms'                => $perms,
            'auth_is_super_admin'       => false,
            'auth_impersonator_id'      => $impersonatorId,
            'auth_impersonator_started' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function stopImpersonation(): bool
    {
        $imp = (int) $this->session->get('auth_impersonator_id');
        if ($imp <= 0) return false;
        $orig = (new UserModel())->find($imp);
        if (!$orig) return false;
        $perms = (new RolePermissionModel())->mapForRole((int) $orig['role_id']);
        $this->session->set([
            'auth_user_id'        => (int) $orig['id'],
            'auth_user_name'      => $orig['name'],
            'auth_user_email'     => $orig['email'],
            'auth_role_id'        => (int) $orig['role_id'],
            'auth_perms'          => $perms,
            'auth_is_super_admin' => (int) ($orig['is_super_admin'] ?? 0) === 1,
        ]);
        $this->session->remove(['auth_impersonator_id', 'auth_impersonator_started']);
        return true;
    }

    public function isImpersonating(): bool
    {
        return (int) $this->session->get('auth_impersonator_id') > 0;
    }

    public function check(): bool
    {
        return (bool) $this->session->get('auth_logged_in');
    }

    public function id(): ?int
    {
        $v = $this->session->get('auth_user_id');
        return $v ? (int) $v : null;
    }

    public function user(): ?array
    {
        if (!$this->check()) return null;
        return [
            'id'       => $this->id(),
            'name'     => $this->session->get('auth_user_name'),
            'email'    => $this->session->get('auth_user_email'),
            'role_id'  => (int) $this->session->get('auth_role_id'),
        ];
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function can(string $moduleKey, string $action = 'can_view'): bool
    {
        if (!$this->check()) return false;
        $perms = $this->session->get('auth_perms') ?? [];
        if (!isset($perms[$moduleKey])) return false;
        return !empty($perms[$moduleKey][$action]);
    }

    public function refreshPermissions(): void
    {
        if (!$this->check()) return;
        $permissions = (new RolePermissionModel())->mapForRole((int) $this->session->get('auth_role_id'));
        $this->session->set('auth_perms', $permissions);
    }
}

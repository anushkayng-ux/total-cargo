<?php

namespace App\Libraries;

use App\Models\ClientUserModel;
use App\Models\ClientModel;
use App\Models\ClientUserLoginLogModel;

/**
 * Client-portal auth — separate session realm from staff Auth.
 * Uses session keys prefixed cu_* so it cannot collide with staff auth_*.
 */
class ClientAuth
{
    public const ATTEMPT_LIMIT  = 5;
    public const LOCK_MINUTES   = 15;
    public const SESSION_PREFIX = 'cu_';

    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    /**
     * @return array{ok:bool, reason?:string, must_change_password?:bool}
     */
    public function attempt(string $email, string $password): array
    {
        $logModel  = new ClientUserLoginLogModel();
        $userModel = new ClientUserModel();
        $user      = $userModel->findActiveByEmail($email);

        if (!$user) {
            $logModel->record(null, $email, false, 'unknown_email');
            return ['ok' => false, 'reason' => 'invalid'];
        }

        // Lockout check
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $logModel->record((int) $user['id'], $email, false, 'locked');
            return ['ok' => false, 'reason' => 'locked'];
        }

        // Client must have portal enabled
        $client = (new ClientModel())->find((int) $user['client_id']);
        if (!$client || (int) $client['status'] !== 1 || (int) ($client['portal_enabled'] ?? 0) !== 1) {
            $logModel->record((int) $user['id'], $email, false, 'portal_disabled');
            return ['ok' => false, 'reason' => 'portal_disabled'];
        }

        if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            $attempts = (int) $user['failed_attempts'] + 1;
            $update   = ['failed_attempts' => $attempts];
            $reason   = 'wrong_password';
            if ($attempts >= self::ATTEMPT_LIMIT) {
                $update['locked_until'] = date('Y-m-d H:i:s', time() + (self::LOCK_MINUTES * 60));
                $reason = 'wrong_password_locked';
            }
            $userModel->update($user['id'], $update);
            $logModel->record((int) $user['id'], $email, false, $reason);
            return ['ok' => false, 'reason' => $reason];
        }

        // Success — reset counters, set session
        $userModel->update($user['id'], [
            'failed_attempts' => 0,
            'locked_until'    => null,
            'last_login_at'   => date('Y-m-d H:i:s'),
            'last_login_ip'   => service('request')->getIPAddress(),
        ]);
        $logModel->record((int) $user['id'], $email, true, 'ok');

        // Drop any staff-realm session keys before establishing portal session.
        // Single-browser realm isolation is required to prevent a compromised
        // staff XSS from also reading portal data, and vice versa.
        foreach (['user_id','user_name','user_email','role_id','perms','logged_in'] as $k) {
            $this->session->remove('auth_' . $k);
        }
        $this->session->regenerate();
        $this->session->set([
            self::SESSION_PREFIX . 'user_id'    => (int) $user['id'],
            self::SESSION_PREFIX . 'client_id'  => (int) $user['client_id'],
            self::SESSION_PREFIX . 'name'       => $user['name'],
            self::SESSION_PREFIX . 'email'      => $user['email'],
            self::SESSION_PREFIX . 'role'       => $user['portal_role'],
            self::SESSION_PREFIX . 'company'    => $client['company_name'],
            self::SESSION_PREFIX . 'must_change'=> (int) ($user['must_change_password'] ?? 0),
            self::SESSION_PREFIX . 'logged_in'  => true,
        ]);

        return [
            'ok'                   => true,
            'must_change_password' => (int) ($user['must_change_password'] ?? 0) === 1,
        ];
    }

    public function check(): bool
    {
        return (bool) $this->session->get(self::SESSION_PREFIX . 'logged_in');
    }

    public function id(): ?int
    {
        $v = $this->session->get(self::SESSION_PREFIX . 'user_id');
        return $v ? (int) $v : null;
    }

    public function clientId(): ?int
    {
        $v = $this->session->get(self::SESSION_PREFIX . 'client_id');
        return $v ? (int) $v : null;
    }

    public function role(): string
    {
        return (string) ($this->session->get(self::SESSION_PREFIX . 'role') ?? 'Viewer');
    }

    public function user(): ?array
    {
        if (!$this->check()) return null;
        return [
            'id'         => $this->id(),
            'client_id'  => $this->clientId(),
            'name'       => $this->session->get(self::SESSION_PREFIX . 'name'),
            'email'      => $this->session->get(self::SESSION_PREFIX . 'email'),
            'role'       => $this->role(),
            'company'    => $this->session->get(self::SESSION_PREFIX . 'company'),
            'must_change'=> (int) $this->session->get(self::SESSION_PREFIX . 'must_change'),
        ];
    }

    public function client(): ?array
    {
        $cid = $this->clientId();
        return $cid ? (new ClientModel())->find($cid) : null;
    }

    public function logout(): void
    {
        // Only blow away portal keys; do not destroy the whole session if a staff
        // user happens to be logged in alongside (e.g., dev machine).
        foreach ([
            'user_id','client_id','name','email','role','company','must_change','logged_in',
        ] as $k) {
            $this->session->remove(self::SESSION_PREFIX . $k);
        }
    }

    /** Coarse role gate — Owner does everything, others scoped per area. */
    public function can(string $action): bool
    {
        if (!$this->check()) return false;
        $role = $this->role();
        $matrix = [
            'Owner'    => ['view_bookings','view_trips','view_invoices','view_ledger','request_booking','manage_profile'],
            'Booker'   => ['view_bookings','view_trips','request_booking','manage_profile'],
            'Accounts' => ['view_invoices','view_ledger','manage_profile'],
            'Viewer'   => ['view_bookings','view_trips','view_invoices','view_ledger'],
        ];
        return in_array($action, $matrix[$role] ?? [], true);
    }

    public function clearMustChange(): void
    {
        $this->session->set(self::SESSION_PREFIX . 'must_change', 0);
    }
}

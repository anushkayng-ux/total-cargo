<?php

namespace App\Libraries;

use App\Models\NotificationModel;

/**
 * Central in-app notification dispatcher.
 *
 * Usage:
 *   Notify::toUser(5, 'Quote received', 'ABC quoted ₹52,000', site_url('rfq/12'),
 *                  ['type' => 'quote', 'icon' => 'cash-coin']);
 *   Notify::toRoles(['pur_exec','pur_mgr'], 'New query', 'Lead LD0007 sent to Purchase',
 *                   site_url('leads/7'), ['type' => 'lead_handoff', 'icon' => 'arrow-right-circle']);
 *   Notify::toAll('System', 'Maintenance tonight 10pm', null, ['icon' => 'megaphone']);
 *
 * The acting user (current logged-in staff) is never notified about their own
 * action — pass ['actor_id' => $id] (defaults to the current auth user) and
 * they are filtered out of the recipient set.
 */
class Notify
{
    /**
     * Master kill-switch for the whole in-app notification system.
     * Toggle from Settings → Notifications (setting_key `notifications_enabled`).
     * When OFF: every Notify::to*() call returns 0 immediately without writing
     * to `notifications` or firing the bell. Records already in the table stay
     * put — turning notifications back on resumes new events without touching
     * old data.
     */
    public static function isEnabled(): bool
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        try {
            $row = \Config\Database::connect()->table('settings')
                ->select('setting_value')
                ->where('setting_key', 'notifications_enabled')
                ->get()->getRow();
            // Default ON if the setting row doesn't exist yet (backward compat).
            $val = $row ? (string) $row->setting_value : '1';
            $cached = !in_array(strtolower($val), ['0', 'false', 'off', 'no', ''], true);
        } catch (\Throwable $e) {
            $cached = true;
        }
        return $cached;
    }

    /** Push to a single user. Returns number of rows inserted (0 or 1). */
    public static function toUser(int $userId, string $title, ?string $body = null, ?string $link = null, array $opts = []): int
    {
        return self::toUsers([$userId], $title, $body, $link, $opts);
    }

    /**
     * Push to many users (deduped, actor removed, inactive removed).
     *
     * Oversight rule: every notification ALSO goes to all admins / super-admins
     * so they have full visibility of the workflow — even for actions they
     * performed themselves. Pass ['skip_admin' => true] to suppress that for a
     * specific call. Pass ['actor_id' => 0] to disable actor-filtering entirely.
     */
    public static function toUsers(array $userIds, string $title, ?string $body = null, ?string $link = null, array $opts = []): int
    {
        // Single choke-point: every other toX() method routes through here, so
        // one guard turns the entire notification system off.
        if (!self::isEnabled()) return 0;

        $actorId = array_key_exists('actor_id', $opts) ? (int) $opts['actor_id'] : (int) (self::currentUserId() ?? 0);

        // Normal recipients: dedupe, drop the actor (they did the action).
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_filter($userIds, static fn ($id) => $id > 0 && $id !== $actorId);

        $db = \Config\Database::connect();

        // Admins are excluded from every notification event by policy —
        // owner does not want to be pinged for staff-level actions.
        // (Previously admins were MERGED IN as oversight copies; now they are
        // FILTERED OUT of the recipient set instead.) Non-admin users continue
        // to receive their normal notifications.
        $adminIds = self::adminUserIds($db);

        // Validate the normal-recipient set is active/non-deleted AND not admin.
        $valid = [];
        if (!empty($userIds)) {
            $rows = $db->table('users')->select('id')
                ->whereIn('id', $userIds)
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()->getResultArray();
            $valid = array_map(static fn ($r) => (int) $r['id'], $rows);
        }

        // Strip admins from the final recipient list.
        $valid = array_values(array_diff($valid, $adminIds));
        if (empty($valid)) return 0;

        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($valid as $uid) {
            $rows[] = [
                'user_id'    => $uid,
                'type'       => $opts['type'] ?? null,
                'title'      => mb_substr($title, 0, 180),
                'body'       => $body !== null ? mb_substr($body, 0, 400) : null,
                'link'       => $link !== null ? mb_substr($link, 0, 255) : null,
                'icon'       => $opts['icon'] ?? 'bell',
                'is_read'    => 0,
                'actor_id'   => $actorId ?: null,
                'created_at' => $now,
                'read_at'    => null,
            ];
        }
        try {
            $db->table('notifications')->insertBatch($rows);
        } catch (\Throwable $e) {
            // Never let a notification failure break the underlying action.
            log_message('warning', 'Notify failed: ' . $e->getMessage());
            return 0;
        }
        return count($rows);
    }

    /** Push to every active user whose role matches one of the given role keys. */
    public static function toRoles(array $roleKeys, string $title, ?string $body = null, ?string $link = null, array $opts = []): int
    {
        if (empty($roleKeys)) return 0;
        $db  = \Config\Database::connect();
        $ids = $db->table('users u')
            ->select('u.id')
            ->join('roles r', 'r.id = u.role_id')
            ->whereIn('r.role_key', $roleKeys)
            ->where('u.status', 1)
            ->where('u.deleted_at', null)
            ->get()->getResultArray();
        return self::toUsers(array_map(static fn ($r) => (int) $r['id'], $ids), $title, $body, $link, $opts);
    }

    /** Convenience alias for a single role key. */
    public static function toRole(string $roleKey, string $title, ?string $body = null, ?string $link = null, array $opts = []): int
    {
        return self::toRoles([$roleKey], $title, $body, $link, $opts);
    }

    /**
     * Push to every active user whose ROLE grants a given module permission —
     * regardless of what the role is named. This is the robust way to target
     * "the Purchase team" etc. without hard-coding role keys: e.g. anyone who
     * can ADD an RFQ is effectively Purchase.
     *
     *   Notify::toPermission('rfq', 'can_add', $title, $body, $link);
     */
    public static function toPermission(string $moduleKey, string $action, string $title, ?string $body = null, ?string $link = null, array $opts = []): int
    {
        $allowed = ['can_view', 'can_add', 'can_edit', 'can_delete', 'can_approve', 'can_export'];
        if (!in_array($action, $allowed, true)) $action = 'can_view';

        $db  = \Config\Database::connect();
        $ids = $db->table('users u')
            ->select('u.id')->distinct()
            ->join('role_permissions rp', 'rp.role_id = u.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('p.module_key', $moduleKey)
            ->where('rp.' . $action, 1)
            ->where('u.status', 1)
            ->where('u.deleted_at', null)
            ->get()->getResultArray();
        return self::toUsers(array_map(static fn ($r) => (int) $r['id'], $ids), $title, $body, $link, $opts);
    }

    /** Broadcast to every active user (e.g. system-wide announcements). */
    public static function toAll(string $title, ?string $body = null, ?string $link = null, array $opts = []): int
    {
        $db  = \Config\Database::connect();
        $ids = $db->table('users')->select('id')
            ->where('status', 1)->where('deleted_at', null)
            ->get()->getResultArray();
        return self::toUsers(array_map(static fn ($r) => (int) $r['id'], $ids), $title, $body, $link, $opts);
    }

    private static function currentUserId(): ?int
    {
        try {
            $v = session()->get('auth_user_id');
            return $v ? (int) $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * True when the given user is treated as admin (super_admin flag OR admin role_key).
     * Used to hide the bell/notifications UI from admins in the layout.
     */
    public static function isAdminUser(int $userId): bool
    {
        if ($userId <= 0) return false;
        static $cache = [];
        if (isset($cache[$userId])) return $cache[$userId];
        try {
            $db = \Config\Database::connect();
            $row = $db->table('users u')
                ->select('u.is_super_admin, r.role_key')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->where('u.id', $userId)->get()->getRow();
            $cache[$userId] = $row && ((int) $row->is_super_admin === 1 || $row->role_key === 'admin');
        } catch (\Throwable $e) {
            $cache[$userId] = false;
        }
        return $cache[$userId];
    }

    /** All active admin / super-admin user IDs. */
    public static function adminUserIds($db = null): array
    {
        if ($db === null) $db = \Config\Database::connect();
        try {
            $rows = $db->table('users u')
                ->select('u.id')->distinct()
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->groupStart()
                    ->where('u.is_super_admin', 1)
                    ->orWhere('r.role_key', 'admin')
                ->groupEnd()
                ->where('u.status', 1)
                ->where('u.deleted_at', null)
                ->get()->getResultArray();
            return array_map(static fn ($r) => (int) $r['id'], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

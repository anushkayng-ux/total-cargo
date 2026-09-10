<?php

namespace App\Libraries;

/**
 * Thin static facade over the activity_logs table.
 *
 * Use everywhere a sensitive change happens — quotes, vendor rates, role
 * permissions, manual GST/TDS overrides, settings tweaks. Diff is captured
 * automatically when both old + new arrays are passed.
 *
 *   AuditLogger::log('rfq_master', $rfqId, 'quote_edit',
 *       'Quote #42 rate updated', $oldRow, $newRow);
 *
 * Cheap (one INSERT). Failure is swallowed so a logging hiccup never breaks
 * a save path.
 */
class AuditLogger
{
    public static function log(
        string $module,
        ?int $refId,
        string $action,
        ?string $description = null,
        ?array $oldRow = null,
        ?array $newRow = null,
        ?int $userId = null
    ): void {
        try {
            $db  = \Config\Database::connect();
            $req = service('request');
            $auth = new Auth();
            $uid = $userId ?? ($auth->check() ? $auth->id() : null);

            $diff = null;
            if (is_array($oldRow) && is_array($newRow)) {
                $diff = self::diff($oldRow, $newRow);
            }

            $db->table('activity_logs')->insert([
                'module_name'        => substr($module, 0, 50),
                'module_ref_id'      => $refId,
                'action_type'        => substr($action, 0, 40),
                'action_description' => $description ? substr($description, 0, 400) : null,
                'old_value_json'     => $diff !== null ? json_encode($diff['old']) : ($oldRow ? json_encode($oldRow) : null),
                'new_value_json'     => $diff !== null ? json_encode($diff['new']) : ($newRow ? json_encode($newRow) : null),
                'user_id'            => $uid,
                'actor_type'         => 'staff',
                'ip_address'         => $req && method_exists($req, 'getIPAddress') ? $req->getIPAddress() : null,
                'user_agent'         => $req && method_exists($req, 'getUserAgent') ? (string) $req->getUserAgent() : null,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'AuditLogger failed: ' . $e->getMessage());
        }
    }

    /** Returns the subset of columns that actually changed (old & new shapes). */
    private static function diff(array $old, array $new): array
    {
        $changedOld = []; $changedNew = [];
        $skip = ['updated_at', 'created_at', 'updated_by'];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($keys as $k) {
            if (in_array($k, $skip, true)) continue;
            $a = $old[$k] ?? null;
            $b = $new[$k] ?? null;
            if ((string) $a !== (string) $b) {
                $changedOld[$k] = $a;
                $changedNew[$k] = $b;
            }
        }
        return ['old' => $changedOld, 'new' => $changedNew];
    }
}

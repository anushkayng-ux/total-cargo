<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardeningTables extends Migration
{
    public function up(): void
    {
        // ── email_logs: queue claim columns
        $this->forge->addColumn('email_logs', [
            'worker_id' => [
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'attempts',
            ],
            'worker_locked_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'worker_id',
            ],
        ]);
        $db = \Config\Database::connect();
        $db->query('CREATE INDEX idx_emaillogs_status_worker ON email_logs (status, worker_locked_at)');

        // ── client_user_resets: token-row pattern for portal forgot-password
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_user_id' => ['type' => 'INT', 'unsigned' => true],
            'token_hash'     => ['type' => 'VARCHAR', 'constraint' => 64],
            'expires_at'     => ['type' => 'DATETIME'],
            'used_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'ip_address'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('client_user_id');
        $this->forge->addForeignKey('client_user_id', 'client_users', 'id', '', 'CASCADE');
        $this->forge->createTable('client_user_resets', true);

        // ── users: lockout columns (mirror client_users)
        $this->forge->addColumn('users', [
            'failed_attempts' => [
                'type' => 'TINYINT', 'unsigned' => true, 'default' => 0, 'after' => 'last_login_ip',
            ],
            'locked_until' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'failed_attempts',
            ],
        ]);

        // ── perf indexes
        $this->createIfMissing($db, 'bookings',  'idx_bk_loading_status', '(loading_date, booking_status)');
        $this->createIfMissing($db, 'bookings',  'idx_bk_created_at',     '(created_at)');
        $this->createIfMissing($db, 'invoices',  'idx_inv_status_date',   '(invoice_status, invoice_date)');
        $this->createIfMissing($db, 'invoices',  'idx_inv_status_due',    '(invoice_status, due_date, balance_due)');
        $this->createIfMissing($db, 'receipts',  'idx_rct_date',          '(receipt_date)');
        $this->createIfMissing($db, 'trips',     'idx_trip_dispatch',     '(dispatch_datetime)');
        $this->createIfMissing($db, 'trips',     'idx_trip_delivery',     '(delivery_datetime)');
        $this->createIfMissing($db, 'trips',     'idx_trip_pod_status',   '(pod_status)');
        $this->createIfMissing($db, 'leads',     'idx_lead_expected',     '(expected_dispatch_date)');
        $this->createIfMissing($db, 'leads',     'idx_lead_assigned_created', '(assigned_crm_user_id, created_at)');
        $this->createIfMissing($db, 'trip_expenses',  'idx_tx_deleted_date',   '(deleted_at, expense_date)');
        $this->createIfMissing($db, 'email_logs',     'idx_emaillogs_tpl_at',  '(created_at, template_key)');

        // Update the password-reset email template body to use a reset URL pattern instead of temp password
        $newBody = '<h2>Reset your password</h2>'
            . '<p>Hi {{name}},</p>'
            . '<p>Click the button below to choose a new password. This link is valid for 1 hour and can only be used once.</p>'
            . '<p style="margin:24px 0;"><a class="btn" href="{{reset_url}}" style="display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;">Choose new password</a></p>'
            . '<p style="font-size:13px;color:#777;">If you did not request this reset, ignore this email — your existing password remains unchanged.</p>';
        $db->table('email_templates')
            ->where('template_key', 'portal_password_reset')
            ->update([
                'subject'        => 'Reset your portal password',
                'body_html'      => $newBody,
                'body_text'      => 'Reset your password: {{reset_url}}',
                'variables_json' => json_encode(['Pat Singh','https://example.com/portal/reset/abc','https://example.com/portal/login']),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
    }

    private function createIfMissing(\CodeIgniter\Database\BaseConnection $db, string $table, string $idxName, string $cols): void
    {
        $row = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$idxName])->getRow();
        if ($row === null) {
            try {
                $db->query("CREATE INDEX `{$idxName}` ON `{$table}` {$cols}");
            } catch (\Throwable $e) {
                log_message('warning', "index create skipped {$table}.{$idxName}: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        foreach ([
            ['bookings','idx_bk_loading_status'],
            ['bookings','idx_bk_created_at'],
            ['invoices','idx_inv_status_date'],
            ['invoices','idx_inv_status_due'],
            ['receipts','idx_rct_date'],
            ['trips','idx_trip_dispatch'],
            ['trips','idx_trip_delivery'],
            ['trips','idx_trip_pod_status'],
            ['leads','idx_lead_expected'],
            ['leads','idx_lead_assigned_created'],
            ['trip_expenses','idx_tx_deleted_date'],
            ['email_logs','idx_emaillogs_tpl_at'],
            ['email_logs','idx_emaillogs_status_worker'],
        ] as [$tbl, $idx]) {
            try { $db->query("DROP INDEX `{$idx}` ON `{$tbl}`"); } catch (\Throwable $e) {}
        }
        $this->forge->dropColumn('users', ['failed_attempts', 'locked_until']);
        $this->forge->dropTable('client_user_resets', true);
        $this->forge->dropColumn('email_logs', ['worker_id', 'worker_locked_at']);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuperAdminTables extends Migration
{
    public function up(): void
    {
        // ── users: super-admin flag + optional TOTP for 2FA
        $this->forge->addColumn('users', [
            'is_super_admin' => [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'role_id',
            ],
            'totp_secret' => [
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'is_super_admin',
            ],
            'totp_enabled' => [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'totp_secret',
            ],
        ]);
        $db = \Config\Database::connect();
        $row = $db->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_super'")->getRow();
        if ($row === null) {
            $db->query("CREATE INDEX idx_users_super ON users (is_super_admin)");
        }

        // ── super_admin_audit: separate immutable log for landlord actions
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'target_type'  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'target_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'description'  => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'details_json' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('super_admin_audit', true);

        // ── feature_flags: toggle modules per install
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'flag_key'   => ['type' => 'VARCHAR', 'constraint' => 60],
            'enabled'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notes'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('flag_key');
        $this->forge->createTable('feature_flags', true);

        // Seed default flags so the super admin can toggle them later
        $defaultFlags = [
            'whatsapp', 'email', 'gps', 'portal', 'einvoice', 'eway_bill',
            'reports', 'documents', 'comments', 'rfq', 'vendor_scoring',
        ];
        $b = $db->table('feature_flags');
        $now = date('Y-m-d H:i:s');
        foreach ($defaultFlags as $k) {
            if ($b->where('flag_key', $k)->countAllResults() === 0) {
                $b->insert(['flag_key' => $k, 'enabled' => 1, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        try { $db->query("DROP INDEX idx_users_super ON users"); } catch (\Throwable $e) {}
        $this->forge->dropTable('feature_flags', true);
        $this->forge->dropTable('super_admin_audit', true);
        $this->forge->dropColumn('users', ['is_super_admin', 'totp_secret', 'totp_enabled']);
    }
}

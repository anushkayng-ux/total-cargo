<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSystemTables extends Migration
{
    public function up(): void
    {
        // activity_logs
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'module_name'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'module_ref_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action_type'        => ['type' => 'VARCHAR', 'constraint' => 40],
            'action_description' => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'old_value_json'     => ['type' => 'MEDIUMTEXT', 'null' => true],
            'new_value_json'     => ['type' => 'MEDIUMTEXT', 'null' => true],
            'user_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ip_address'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['module_name', 'module_ref_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('activity_logs', true);

        // settings
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'setting_key'   => ['type' => 'VARCHAR', 'constraint' => 120],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'setting_group' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'general'],
            'updated_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('setting_key');
        $this->forge->addKey('setting_group');
        $this->forge->createTable('settings', true);

        // sessions (CI4 DB session handler ready, optional)
        $this->forge->addField([
            'id'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'timestamp'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'data'       => ['type' => 'BLOB', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ci_sessions', true);
        $this->forge->dropTable('settings', true);
        $this->forge->dropTable('activity_logs', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    public function up(): void
    {
        // roles
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_name'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'role_key'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('role_key');
        $this->forge->createTable('roles', true);

        // permissions
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'module_key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'action_key' => ['type' => 'VARCHAR', 'constraint' => 40],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['module_key', 'action_key']);
        $this->forge->createTable('permissions', true);

        // role_permissions
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_id'       => ['type' => 'INT', 'unsigned' => true],
            'permission_id' => ['type' => 'INT', 'unsigned' => true],
            'can_view'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_add'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_edit'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_delete'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_approve'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_export'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['role_id', 'permission_id']);
        $this->forge->addForeignKey('role_id', 'roles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', '', 'CASCADE');
        $this->forge->createTable('role_permissions', true);

        // users
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_id'       => ['type' => 'INT', 'unsigned' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'mobile'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('mobile');
        $this->forge->addForeignKey('role_id', 'roles', 'id', '', 'RESTRICT');
        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
    }
}

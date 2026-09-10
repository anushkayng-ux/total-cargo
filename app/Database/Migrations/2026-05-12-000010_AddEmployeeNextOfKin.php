<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmployeeNextOfKin extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'relation'             => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'phone'                => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => false],
            'alt_phone'            => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true],
            'email'                => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'address'              => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'is_emergency_contact' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'           => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('employee_next_of_kin');
    }

    public function down(): void
    {
        $this->forge->dropTable('employee_next_of_kin', true);
    }
}

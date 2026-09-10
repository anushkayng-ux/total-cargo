<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDriverMessages extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'direction'       => ['type' => 'ENUM', 'constraint' => ['driver','staff','system'], 'null' => false],
            'user_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'body'            => ['type' => 'TEXT', 'null' => true],
            'attachment_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attachment_mime' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'attachment_orig' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_read_by_staff'  => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'is_read_by_driver' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('trip_id');
        $this->forge->addKey(['trip_id', 'id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('driver_messages');
    }

    public function down(): void
    {
        $this->forge->dropTable('driver_messages', true);
    }
}

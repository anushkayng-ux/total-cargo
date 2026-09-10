<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-user in-app notifications.
 *
 * Unlike the derived "bell" counters (driver msgs, leaves, etc.) which are
 * computed on the fly, these are concrete rows pushed at the moment an event
 * happens (lead handoff, quote received, booking confirmed, …) and targeted at
 * specific users. The header bell merges both sources.
 */
class CreateNotifications extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 180],
            'body'       => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'link'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'actor_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'read_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('notifications', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications', true);
    }
}

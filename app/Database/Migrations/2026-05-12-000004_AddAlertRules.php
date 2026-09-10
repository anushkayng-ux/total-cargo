<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAlertRules extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_key'  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'audience'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'channel'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'email'],
            'enabled'    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_key', 'audience', 'channel']);
        $this->forge->createTable('alert_rules');

        // Seed default rules — every audience × channel pair enabled by default
        $now = date('Y-m-d H:i:s');
        $defaults = [
            // event_key                audience      channel
            ['rfq_dispatched',         'vendor',     'email', 1],
            ['quote_selected',         'vendor',     'email', 1],
            ['quote_selected',         'client',     'email', 1],
            ['trip_assigned',          'client',     'email', 1],
            ['trip_assigned',          'vendor',     'email', 1],
            ['trip_in_transit',        'client',     'email', 1],
            ['trip_in_transit',        'internal',   'email', 0],
            ['trip_arrived',           'client',     'email', 1],
            ['trip_arrived',           'internal',   'email', 0],
            ['trip_delivered',         'client',     'email', 1],
            ['trip_delivered',         'vendor',     'email', 0],
            ['trip_delivered',         'internal',   'email', 1],
            ['driver_dispatch',        'internal',   'email', 1],
            ['driver_dispatch',        'driver',     'email', 0], // drivers don't have email yet
        ];
        $rows = [];
        foreach ($defaults as $d) {
            $rows[] = [
                'event_key' => $d[0], 'audience' => $d[1], 'channel' => $d[2],
                'enabled' => $d[3], 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        \Config\Database::connect()->table('alert_rules')->insertBatch($rows);
    }

    public function down(): void
    {
        $this->forge->dropTable('alert_rules', true);
    }
}

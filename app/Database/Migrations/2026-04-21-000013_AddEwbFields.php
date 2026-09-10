<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEwbFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('trips', [
            'ewb_no'          => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true, 'after' => 'lr_generated_at'],
            'ewb_date'        => ['type' => 'DATETIME', 'null' => true, 'after' => 'ewb_no'],
            'ewb_valid_until' => ['type' => 'DATETIME', 'null' => true, 'after' => 'ewb_date'],
            'ewb_status'      => ['type' => 'VARCHAR', 'constraint' => 20,  'default' => 'None', 'after' => 'ewb_valid_until'],
        ]);

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'          => ['type' => 'INT', 'unsigned' => true],
            'action'           => ['type' => 'VARCHAR', 'constraint' => 30],
            'request_payload'  => ['type' => 'MEDIUMTEXT', 'null' => true],
            'response_payload' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Queued'],
            'error_message'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addKey('action');
        $this->forge->createTable('ewb_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ewb_logs', true);
        $this->forge->dropColumn('trips', ['ewb_no', 'ewb_date', 'ewb_valid_until', 'ewb_status']);
    }
}

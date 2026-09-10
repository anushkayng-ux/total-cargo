<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGpsTables extends Migration
{
    public function up(): void
    {
        // gps_logs
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vehicle_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'latitude'       => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'      => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'gps_timestamp'  => ['type' => 'DATETIME', 'null' => true],
            'speed'          => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'address'        => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'raw_payload'    => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addKey('vehicle_number');
        $this->forge->addKey('gps_timestamp');
        $this->forge->createTable('gps_logs', true);

        // latest_vehicle_status
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vehicle_number' => ['type' => 'VARCHAR', 'constraint' => 20],
            'latitude'       => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'      => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'gps_timestamp'  => ['type' => 'DATETIME', 'null' => true],
            'speed'          => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'address'        => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'eta_text'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'delay_flag'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('vehicle_number');
        $this->forge->addKey('trip_id');
        $this->forge->createTable('latest_vehicle_status', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('latest_vehicle_status', true);
        $this->forge->dropTable('gps_logs', true);
    }
}

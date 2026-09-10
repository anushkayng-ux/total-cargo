<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAndTripTables extends Migration
{
    public function up(): void
    {
        // bookings
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_no'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'lead_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'client_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vendor_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'final_buy_rate'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'final_sell_rate'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'margin_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'route_text'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'vehicle_type'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'load_details'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'loading_date'     => ['type' => 'DATE', 'null' => true],
            'billing_party'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'instructions'     => ['type' => 'TEXT', 'null' => true],
            'booking_status'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Pending'],
            'approved_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('booking_no');
        $this->forge->addKey('lead_id');
        $this->forge->addKey('client_id');
        $this->forge->addKey('vendor_id');
        $this->forge->addKey('booking_status');
        $this->forge->createTable('bookings', true);

        // trips
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_no'            => ['type' => 'VARCHAR', 'constraint' => 30],
            'booking_id'         => ['type' => 'INT', 'unsigned' => true],
            'vendor_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'driver_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vehicle_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'driver_name'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'driver_mobile'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'vehicle_number'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'loading_point'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'unloading_point'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dispatch_datetime'  => ['type' => 'DATETIME', 'null' => true],
            'delivery_datetime'  => ['type' => 'DATETIME', 'null' => true],
            'current_status'     => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Booking Created'],
            'delay_reason'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pod_status'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Pending'],
            'pod_received_at'    => ['type' => 'DATETIME', 'null' => true],
            'remarks'            => ['type' => 'TEXT', 'null' => true],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('trip_no');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('vendor_id');
        $this->forge->addKey('vehicle_number');
        $this->forge->addKey('current_status');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', '', 'CASCADE');
        $this->forge->createTable('trips', true);

        // trip_status_history
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'    => ['type' => 'INT', 'unsigned' => true],
            'old_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'new_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'notes'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'changed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'changed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addForeignKey('trip_id', 'trips', 'id', '', 'CASCADE');
        $this->forge->createTable('trip_status_history', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('trip_status_history', true);
        $this->forge->dropTable('trips', true);
        $this->forge->dropTable('bookings', true);
    }
}

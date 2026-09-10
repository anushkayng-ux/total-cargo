<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendorTables extends Migration
{
    public function up(): void
    {
        // vendors
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_code'    => ['type' => 'VARCHAR', 'constraint' => 30],
            'owner_name'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'company_name'   => ['type' => 'VARCHAR', 'constraint' => 200],
            'mobile'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alt_mobile'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'whatsapp_no'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'address'        => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'city'           => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'state'          => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'pincode'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'gst_no'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'pan_no'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'bank_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'account_no'     => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'ifsc_code'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'rating'         => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'is_preferred'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_blacklisted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('vendor_code');
        $this->forge->addKey('mobile');
        $this->forge->addKey('whatsapp_no');
        $this->forge->addKey('company_name');
        $this->forge->createTable('vendors', true);

        // vendor_routes
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'   => ['type' => 'INT', 'unsigned' => true],
            'pickup_city' => ['type' => 'VARCHAR', 'constraint' => 80],
            'drop_city'   => ['type' => 'VARCHAR', 'constraint' => 80],
            'status'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['vendor_id', 'pickup_city', 'drop_city']);
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'CASCADE');
        $this->forge->createTable('vendor_routes', true);

        // vendor_vehicle_types
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'    => ['type' => 'INT', 'unsigned' => true],
            'vehicle_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'status'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['vendor_id', 'vehicle_type']);
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'CASCADE');
        $this->forge->createTable('vendor_vehicle_types', true);

        // drivers
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'driver_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'mobile'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alt_mobile'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'license_no'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'license_expiry'  => ['type' => 'DATE', 'null' => true],
            'status'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('vendor_id');
        $this->forge->addKey('mobile');
        $this->forge->createTable('drivers', true);

        // vehicles
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vehicle_number'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'vehicle_type'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'rc_no'             => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'permit_no'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'insurance_no'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'insurance_expiry'  => ['type' => 'DATE', 'null' => true],
            'fitness_expiry'    => ['type' => 'DATE', 'null' => true],
            'status'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('vehicle_number');
        $this->forge->addKey('vendor_id');
        $this->forge->createTable('vehicles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('vehicles', true);
        $this->forge->dropTable('drivers', true);
        $this->forge->dropTable('vendor_vehicle_types', true);
        $this->forge->dropTable('vendor_routes', true);
        $this->forge->dropTable('vendors', true);
    }
}

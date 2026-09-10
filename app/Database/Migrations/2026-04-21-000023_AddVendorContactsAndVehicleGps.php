<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVendorContactsAndVehicleGps extends Migration
{
    public function up(): void
    {
        // ── vendor_contacts: multiple people per vendor (Owner, Manager, Accountant, Dispatch, etc.)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'    => ['type' => 'INT', 'unsigned' => true],
            'contact_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'designation'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'mobile'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'is_primary'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('vendor_id');
        $this->forge->addUniqueKey(['vendor_id', 'mobile']);  // phone unique within a vendor
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'CASCADE');
        $this->forge->createTable('vendor_contacts', true);

        // ── vehicles: GPS / tracking config per vehicle
        $this->forge->addColumn('vehicles', [
            'gps_provider' => [
                // none = not tracked; loconav/fasttag = use our integrations;
                // dedicated_link = vendor gives us a portal URL only;
                // driver_phone_only = rely on the per-trip PWA token
                'type' => 'ENUM', 'constraint' => ['none','loconav','fasttag','dedicated_link','driver_phone_only'],
                'default' => 'none', 'after' => 'fitness_expiry',
            ],
            'gps_device_imei' => [
                'type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'gps_provider',
            ],
            'gps_tracking_url' => [
                'type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'gps_device_imei',
            ],
            'gps_notes' => [
                'type' => 'VARCHAR', 'constraint' => 400, 'null' => true, 'after' => 'gps_tracking_url',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('vehicles', ['gps_provider','gps_device_imei','gps_tracking_url','gps_notes']);
        $this->forge->dropTable('vendor_contacts', true);
    }
}

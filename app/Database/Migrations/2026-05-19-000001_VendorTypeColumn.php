<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `vendor_type` to the vendors table so the DOMESTIC VENDOR DATA SHEET
 * import preserves the Broker / Fleet Owner / Transport Contractor /
 * Commission Agent classification that's in column 3 of the user's source file.
 *
 * Free-form varchar (not enum) because the field has many real-world variants
 * including hybrids like "Fleet Owner & Commission Agent" and typos like
 * "BRPKER". The vendor list page can group/filter by this column.
 */
class VendorTypeColumn extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('vendors', [
            'vendor_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'company_name',
            ],
        ]);
        $this->db->query('CREATE INDEX idx_vendors_vendor_type ON vendors(vendor_type)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX IF EXISTS idx_vendors_vendor_type ON vendors');
        $this->forge->dropColumn('vendors', ['vendor_type']);
    }
}

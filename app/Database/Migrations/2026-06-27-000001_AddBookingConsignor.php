<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds Consignor fields to bookings so we can capture "shipper" information —
 * separate from the Consignee (recipient) that's already stored. Required for
 * LR/Docket generation and for the tax invoice's supplier-of-supply block.
 */
class AddBookingConsignor extends Migration
{
    public function up(): void
    {
        $adds = [];
        if (!$this->db->fieldExists('consignor_name', 'bookings')) {
            $adds['consignor_name']    = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'consignee_gstin'];
        }
        if (!$this->db->fieldExists('consignor_mobile', 'bookings')) {
            $adds['consignor_mobile']  = ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true];
        }
        if (!$this->db->fieldExists('consignor_address', 'bookings')) {
            $adds['consignor_address'] = ['type' => 'TEXT',                        'null' => true];
        }
        if (!$this->db->fieldExists('consignor_gstin', 'bookings')) {
            $adds['consignor_gstin']   = ['type' => 'VARCHAR', 'constraint' => 15,  'null' => true];
        }
        if (!$this->db->fieldExists('consignor_state', 'bookings')) {
            $adds['consignor_state']   = ['type' => 'VARCHAR', 'constraint' => 80,  'null' => true];
        }
        if (!empty($adds)) $this->forge->addColumn('bookings', $adds);
    }

    public function down(): void
    {
        foreach (['consignor_name','consignor_mobile','consignor_address','consignor_gstin','consignor_state'] as $col) {
            if ($this->db->fieldExists($col, 'bookings')) {
                $this->forge->dropColumn('bookings', [$col]);
            }
        }
    }
}

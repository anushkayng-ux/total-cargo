<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds foreign keys from bookings → clients for both consignor and consignee
 * so the operator can pick from the same approved client list for both parties.
 * The existing free-text consignor_* / consignee_* columns are kept and get
 * auto-populated from the linked client's details at save time.
 */
class AddBookingPartyLinks extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('consignor_client_id', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'consignor_client_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            ]);
        }
        if (!$this->db->fieldExists('consignee_client_id', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'consignee_client_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['consignor_client_id', 'consignee_client_id'] as $col) {
            if ($this->db->fieldExists($col, 'bookings')) {
                $this->forge->dropColumn('bookings', [$col]);
            }
        }
    }
}

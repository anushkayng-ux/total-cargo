<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `lr_no` to bookings so the operator can type the LR/docket number
 * directly on the Add Booking form. At handover, the value is copied to
 * `trips.lr_no` (which has the true unique index). Booking-level uniqueness
 * is enforced softly in the controller.
 */
class AddBookingLrNo extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('lr_no', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'lr_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'booking_no'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('lr_no', 'bookings')) {
            $this->forge->dropColumn('bookings', ['lr_no']);
        }
    }
}

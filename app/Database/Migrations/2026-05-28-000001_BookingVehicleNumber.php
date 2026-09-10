<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `vehicle_number` (registration) to the bookings table.
 *
 * Workflow change: a booking can now be created without a vendor or rate
 * (draft-style capture). The "Confirm Booking" step then captures vendor +
 * buy/sell rate + vehicle type + vehicle registration number. The vehicle
 * number carries over to the trip at handover.
 */
class BookingVehicleNumber extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'vehicle_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'vehicle_count',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bookings', ['vehicle_number']);
    }
}

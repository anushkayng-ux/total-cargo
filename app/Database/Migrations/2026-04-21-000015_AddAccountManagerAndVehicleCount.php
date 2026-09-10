<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccountManagerAndVehicleCount extends Migration
{
    public function up(): void
    {
        // clients — assign a single account manager (a staff user)
        $this->forge->addColumn('clients', [
            'account_manager_user_id' => [
                'type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'portal_notes',
            ],
        ]);

        // leads — number of vehicles requested for the loading date
        $this->forge->addColumn('leads', [
            'vehicle_count' => [
                'type' => 'SMALLINT', 'unsigned' => true, 'default' => 1,
                'after' => 'vehicle_type_required',
            ],
        ]);

        // bookings — actual vehicle count for the loading date
        $this->forge->addColumn('bookings', [
            'vehicle_count' => [
                'type' => 'SMALLINT', 'unsigned' => true, 'default' => 1,
                'after' => 'vehicle_type',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bookings', ['vehicle_count']);
        $this->forge->dropColumn('leads',    ['vehicle_count']);
        $this->forge->dropColumn('clients',  ['account_manager_user_id']);
    }
}

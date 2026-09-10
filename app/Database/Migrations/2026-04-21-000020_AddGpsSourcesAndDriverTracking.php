<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGpsSourcesAndDriverTracking extends Migration
{
    public function up(): void
    {
        // trips: per-trip random token used by the driver-phone PWA
        $this->forge->addColumn('trips', [
            'driver_track_token' => [
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'driver_mobile',
            ],
            'driver_track_started_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'driver_track_token',
            ],
            'driver_track_last_ping' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'driver_track_started_at',
            ],
        ]);
        $db = \Config\Database::connect();
        $row = $db->query("SHOW INDEX FROM trips WHERE Key_name = 'idx_trip_track_token'")->getRow();
        if ($row === null) {
            $db->query("CREATE INDEX idx_trip_track_token ON trips (driver_track_token)");
        }

        // latest_vehicle_status + gps_logs: where did this fix come from?
        // values: loconav | fasttag | driver_phone | manual
        foreach (['latest_vehicle_status', 'gps_logs'] as $tbl) {
            $col = $db->query("SHOW COLUMNS FROM `{$tbl}` LIKE 'source'")->getRow();
            if ($col === null) {
                $this->forge->addColumn($tbl, [
                    'source' => [
                        'type' => 'VARCHAR', 'constraint' => 20, 'null' => true,
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        try { $db->query("DROP INDEX idx_trip_track_token ON trips"); } catch (\Throwable $e) {}
        $this->forge->dropColumn('trips', ['driver_track_token','driver_track_started_at','driver_track_last_ping']);
        $this->forge->dropColumn('latest_vehicle_status', ['source']);
        $this->forge->dropColumn('gps_logs', ['source']);
    }
}

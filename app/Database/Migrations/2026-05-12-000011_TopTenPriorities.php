<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Schema for the "top-ten priorities" rollout:
 *   - password_reset_tokens   (staff forgot-password flow)
 *   - holidays                (HR calendar; consumed by payroll)
 *   - trips.client_track_token + trips.client_track_expires_at  (client live-tracking link)
 */
class TopTenPriorities extends Migration
{
    public function up(): void
    {
        // ─── password_reset_tokens : staff side (clients use portal_password_resets) ───
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('password_reset_tokens');

        // ─── holidays : HR calendar ───
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'holiday_date'    => ['type' => 'DATE', 'null' => false],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'pay_status'      => ['type' => 'ENUM', 'constraint' => ['paid','unpaid','optional'], 'default' => 'paid'],
            'applies_to'      => ['type' => 'ENUM', 'constraint' => ['all','department','employee'], 'default' => 'all'],
            'applies_value'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'status'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['holiday_date','applies_to','applies_value']);
        $this->forge->createTable('holidays');

        // Seed a handful of upcoming public holidays so payroll can compute correctly
        $now = date('Y-m-d H:i:s');
        $year = (int) date('Y');
        $seedHolidays = [
            [($year)     . '-08-15', 'Independence Day'],
            [($year)     . '-10-02', 'Gandhi Jayanti'],
            [($year)     . '-12-25', 'Christmas Day'],
            [($year + 1) . '-01-26', 'Republic Day'],
            [($year + 1) . '-08-15', 'Independence Day'],
            [($year + 1) . '-10-02', 'Gandhi Jayanti'],
        ];
        $db = \Config\Database::connect();
        foreach ($seedHolidays as [$d, $n]) {
            if ($db->table('holidays')->where('holiday_date', $d)->where('applies_to', 'all')->countAllResults() === 0) {
                $db->table('holidays')->insert([
                    'holiday_date' => $d, 'name' => $n,
                    'pay_status'   => 'paid', 'applies_to' => 'all',
                    'applies_value'=> null, 'status' => 1,
                    'created_at'   => $now, 'updated_at' => $now,
                ]);
            }
        }

        // ─── trips: client_track_token + expiry (for the client live-tracking page) ───
        $this->forge->addColumn('trips', [
            'client_track_token'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'driver_track_last_ping'],
            'client_track_expires_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'client_track_token'],
        ]);
        $db->query("CREATE INDEX idx_trip_client_track ON trips (client_track_token)");
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->query("DROP INDEX IF EXISTS idx_trip_client_track ON trips");
        $this->forge->dropColumn('trips', ['client_track_token', 'client_track_expires_at']);
        $this->forge->dropTable('holidays', true);
        $this->forge->dropTable('password_reset_tokens', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Splits the single free-text `route_text` on bookings into structured
 * `pickup_city` + `drop_city` columns so downstream flows (LR, invoice, RFQ
 * matching, trip handover) don't have to string-parse "A → B".
 *
 * The existing `route_text` column stays — it's kept in sync as
 * "pickup → drop" for backward-compat with searches / list views.
 * Legacy rows are backfilled by splitting on " → ", " to ", or "-".
 */
class AddBookingPickupDrop extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('pickup_city', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'pickup_city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'route_text'],
            ]);
        }
        if (!$this->db->fieldExists('drop_city', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'drop_city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            ]);
        }

        // Backfill: parse existing route_text into pickup/drop where possible.
        $rows = $this->db->table('bookings')
            ->select('id, route_text')
            ->where('route_text IS NOT NULL', null, false)
            ->groupStart()
                ->where('pickup_city', null)
                ->orWhere('drop_city', null)
            ->groupEnd()
            ->get()->getResultArray();

        foreach ($rows as $r) {
            $txt = trim((string) $r['route_text']);
            if ($txt === '') continue;
            $parts = preg_split('/\s*(?:→|->|to|—|-)\s*/i', $txt, 2);
            if (!is_array($parts) || count($parts) < 2) continue;
            $this->db->table('bookings')->where('id', (int) $r['id'])->update([
                'pickup_city' => trim($parts[0]) ?: null,
                'drop_city'   => trim($parts[1]) ?: null,
            ]);
        }
    }

    public function down(): void
    {
        foreach (['pickup_city', 'drop_city'] as $col) {
            if ($this->db->fieldExists($col, 'bookings')) {
                $this->forge->dropColumn('bookings', [$col]);
            }
        }
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use App\Models\CityModel;

/**
 * Soft foreign keys for the city master.
 *
 * Adds nullable `*_city_id` columns alongside the existing varchar `*_city`
 * columns across: clients.city, vendors.city, leads.pickup_city / drop_city,
 * rfq_master.pickup_city / drop_city, vendor_routes.pickup_city / drop_city,
 * rate_contract_lanes.pickup_city / drop_city.
 *
 * Why soft (not strict FK)?
 *   - Free-text entries from imports, RFQs etc. shouldn't break the save path.
 *   - Existing rows have varchars that may or may not match the master.
 *   - Reporting can join on city_id where present, fall back to LIKE otherwise.
 *
 * Backfill: walks every (table, col) pair and populates the *_id column from
 * CityModel::resolve(). Cities not found in the master get added as new rows
 * (state = '— Unknown —') so the FK still resolves. The CitiesController UI
 * surfaces those for an admin to correct.
 */
class CitySoftForeignKeys extends Migration
{
    /** Tables and their (varchar, fk) column pairs. */
    private array $map = [
        'clients'             => [['city', 'city_id']],
        'vendors'             => [['city', 'city_id']],
        'leads'               => [['pickup_city', 'pickup_city_id'], ['drop_city', 'drop_city_id']],
        'rfq_master'          => [['pickup_city', 'pickup_city_id'], ['drop_city', 'drop_city_id']],
        'vendor_routes'       => [['pickup_city', 'pickup_city_id'], ['drop_city', 'drop_city_id']],
        'rate_contract_lanes' => [['pickup_city', 'pickup_city_id'], ['drop_city', 'drop_city_id']],
    ];

    public function up(): void
    {
        foreach ($this->map as $table => $pairs) {
            if (!$this->db->tableExists($table)) continue;
            foreach ($pairs as [$src, $fk]) {
                if ($this->db->fieldExists($fk, $table)) continue;
                $this->forge->addColumn($table, [
                    $fk => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => $src],
                ]);
                $this->db->query("CREATE INDEX idx_{$table}_{$fk} ON {$table}({$fk})");
            }
        }

        $this->backfill();
    }

    public function down(): void
    {
        foreach ($this->map as $table => $pairs) {
            if (!$this->db->tableExists($table)) continue;
            foreach ($pairs as [, $fk]) {
                if (!$this->db->fieldExists($fk, $table)) continue;
                $this->db->query("DROP INDEX IF EXISTS idx_{$table}_{$fk} ON {$table}");
                $this->forge->dropColumn($table, $fk);
            }
        }
    }

    /**
     * Walks every row and resolves the varchar city → city_id. Cache by lowercased
     * name so repeated values don't hammer the master. Unknown cities get
     * auto-inserted (state = '— Unknown —') and the new id is reused.
     */
    private function backfill(): void
    {
        $cityModel = new CityModel();
        $cache     = [];
        $now       = date('Y-m-d H:i:s');

        foreach ($this->map as $table => $pairs) {
            if (!$this->db->tableExists($table)) continue;

            foreach ($pairs as [$src, $fk]) {
                $rows = $this->db->table($table)
                    ->select("id, $src AS city_name")
                    ->where("$src IS NOT NULL", null, false)
                    ->where("$src !=", '')
                    ->where("$fk IS NULL", null, false)
                    ->get()->getResultArray();

                foreach ($rows as $r) {
                    $name = trim((string) $r['city_name']);
                    if ($name === '') continue;
                    $key  = strtolower($name);
                    if (!array_key_exists($key, $cache)) {
                        $hit = $cityModel->resolve($name);
                        if ($hit) {
                            $cache[$key] = (int) $hit['id'];
                        } else {
                            $id = $cityModel->insert([
                                'name'   => $name,
                                'state'  => '— Unknown —',
                                'status' => 1,
                            ]);
                            $cache[$key] = (int) $id;
                        }
                    }
                    $this->db->table($table)->where('id', (int) $r['id'])->update([$fk => $cache[$key]]);
                }
            }
        }
    }
}

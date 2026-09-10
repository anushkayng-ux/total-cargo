<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seed aliases for canonically-renamed Indian cities so the resolver maps
 * "Bombay" → Mumbai, "Bangalore" → Bengaluru, etc. Also collapses the
 * stub rows the previous harvest produced for those old names.
 */
class CityAliases extends Migration
{
    public function up(): void
    {
        $db = \Config\Database::connect();

        // canonical => [aliases…, …old-name stubs to absorb]
        $map = [
            'Mumbai'             => ['Bombay'],
            'Bengaluru'          => ['Bangalore', 'Bengalooru'],
            'Chennai'            => ['Madras'],
            'Kolkata'            => ['Calcutta'],
            'Pune'               => ['Poona'],
            'Thiruvananthapuram' => ['Trivandrum'],
            'Kochi'              => ['Cochin'],
            'Kozhikode'          => ['Calicut'],
            'Mysuru'             => ['Mysore'],
            'Hubballi'           => ['Hubli', 'Hubli-Dharwad'],
            'Tumakuru'           => ['Tumkur'],
            'Vadodara'           => ['Baroda'],
            'Prayagraj'          => ['Allahabad'],
            'Varanasi'           => ['Banaras', 'Benares'],
            'Vijayawada'         => ['Bezawada'],
            'Visakhapatnam'      => ['Vizag'],
            'Tiruchirappalli'    => ['Trichy'],
            'Puducherry'         => ['Pondicherry'],
            'Tiruvananthapuram'  => ['Trivandrum'],
        ];

        foreach ($map as $canonical => $aliases) {
            $row = $db->table('cities')->where('name', $canonical)->get()->getRowArray();
            if (!$row) continue;

            // Merge with any existing aliases
            $existing = array_filter(array_map('trim', explode(',', (string) ($row['aliases'] ?? ''))));
            $merged   = array_values(array_unique(array_merge($existing, $aliases)));
            $db->table('cities')->where('id', $row['id'])->update([
                'aliases'    => implode(', ', $merged),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Delete any stub rows that share an alias name as their primary name
            foreach ($aliases as $oldName) {
                $db->table('cities')
                    ->where('LOWER(name)', strtolower($oldName))
                    ->where('id !=', $row['id'])
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // No-op: aliases are descriptive; removing them is non-destructive but
        // would also delete the stub rows we collapsed, which is undesirable.
    }
}

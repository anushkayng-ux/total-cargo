<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Make pickup_city/drop_city nullable on vendor_routes so a vendor can declare
 * "I cover any pickup to <city>" (drop_city set, pickup NULL) or vice versa.
 * Add a single-column index on drop_city for the destination-only suggest
 * lookup. Existing rows keep their (pickup, drop) pairs untouched.
 */
class VendorRoutesNullable extends Migration
{
    public function up(): void
    {
        // Modify columns to be nullable. Using raw SQL because CI4 Forge's
        // modifyColumn() shape requires repeating the full column definition.
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE vendor_routes
                    MODIFY pickup_city VARCHAR(80) NULL,
                    MODIFY drop_city   VARCHAR(80) NULL");

        // Single-column index on drop_city for the "vendors who serve <city>" lookup
        $row = $db->query("SHOW INDEX FROM vendor_routes WHERE Key_name = 'idx_vendor_routes_drop_city'")->getRow();
        if ($row === null) {
            $db->query("CREATE INDEX idx_vendor_routes_drop_city ON vendor_routes (drop_city)");
        }
        $row = $db->query("SHOW INDEX FROM vendor_routes WHERE Key_name = 'idx_vendor_routes_pickup_city'")->getRow();
        if ($row === null) {
            $db->query("CREATE INDEX idx_vendor_routes_pickup_city ON vendor_routes (pickup_city)");
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->query("DROP INDEX IF EXISTS idx_vendor_routes_drop_city  ON vendor_routes");
        $db->query("DROP INDEX IF EXISTS idx_vendor_routes_pickup_city ON vendor_routes");
        $db->query("ALTER TABLE vendor_routes
                    MODIFY pickup_city VARCHAR(80) NOT NULL,
                    MODIFY drop_city   VARCHAR(80) NOT NULL");
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the remaining Indian RTO compliance fields that every commercial vehicle
 * carries. We already store insurance_expiry + fitness_expiry; this fills in
 * RC validity, permit validity, PUC, and road-tax dates so the expiry-alert
 * widget can warn drivers/admins before any document lapses.
 *
 * No data backfill — existing rows get NULL, prompting the admin to fill them
 * via the vehicle edit form. The dashboard widget hides rows with all-null
 * dates so unfilled vehicles don't drown the alert list.
 */
class VehicleComplianceFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('vehicles', [
            'rc_expiry'     => ['type' => 'DATE', 'null' => true, 'after' => 'rc_no'],
            'permit_expiry' => ['type' => 'DATE', 'null' => true, 'after' => 'permit_no'],
            'puc_no'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'fitness_expiry'],
            'puc_expiry'    => ['type' => 'DATE', 'null' => true, 'after' => 'puc_no'],
            'tax_expiry'    => ['type' => 'DATE', 'null' => true, 'after' => 'puc_expiry'],
        ]);
        // Index expiry dates so the dashboard widget query is fast even with
        // thousands of vehicles.
        $this->db->query('CREATE INDEX idx_vehicles_rc_expiry      ON vehicles(rc_expiry)');
        $this->db->query('CREATE INDEX idx_vehicles_permit_expiry  ON vehicles(permit_expiry)');
        $this->db->query('CREATE INDEX idx_vehicles_puc_expiry     ON vehicles(puc_expiry)');
        $this->db->query('CREATE INDEX idx_vehicles_tax_expiry     ON vehicles(tax_expiry)');
        $this->db->query('CREATE INDEX idx_vehicles_insurance_exp  ON vehicles(insurance_expiry)');
        $this->db->query('CREATE INDEX idx_vehicles_fitness_exp    ON vehicles(fitness_expiry)');
        // Drivers too — license expiry is the most common compliance fault.
        $this->db->query('CREATE INDEX idx_drivers_license_expiry  ON drivers(license_expiry)');
    }

    public function down(): void
    {
        foreach (['idx_vehicles_rc_expiry','idx_vehicles_permit_expiry','idx_vehicles_puc_expiry','idx_vehicles_tax_expiry','idx_vehicles_insurance_exp','idx_vehicles_fitness_exp'] as $idx) {
            $this->db->query("DROP INDEX IF EXISTS $idx ON vehicles");
        }
        $this->db->query("DROP INDEX IF EXISTS idx_drivers_license_expiry ON drivers");
        $this->forge->dropColumn('vehicles', ['rc_expiry','permit_expiry','puc_no','puc_expiry','tax_expiry']);
    }
}

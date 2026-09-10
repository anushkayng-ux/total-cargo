<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds all the docket-specific fields that TCE's LR/Docket format prints so
 * every cell in the printed Lorry Receipt can be populated automatically from
 * booking data instead of being blank.
 *
 * Everything is nullable — bookings can be saved without docket details and
 * filled in later at dispatch time.
 */
class AddBookingDocketFields extends Migration
{
    private array $fields = [
        // Load composition
        'packages_count'      => ['type' => 'INT',     'constraint' => 10, 'null' => true],
        'packing_method'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        // Weights
        'actual_weight_kg'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
        'charge_weight_kg'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
        // Dimensions (LxWxH) — stored in cm
        'dim_length_cm'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
        'dim_width_cm'        => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
        'dim_height_cm'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
        // Charges breakdown
        'additional_charges'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0],
        'other_charges'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0],
        'gst_amount'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0],
        'service_tax_amount'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0],
        // References
        'invoice_number'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        'bill_of_entry'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        'bl_number'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        'container_number'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        'seal_number'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        // Tax liability
        'person_liable_gst'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
        'cargo_value_inr'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
    ];

    public function up(): void
    {
        $adds = [];
        foreach ($this->fields as $col => $spec) {
            if (!$this->db->fieldExists($col, 'bookings')) {
                $adds[$col] = $spec;
            }
        }
        if (!empty($adds)) $this->forge->addColumn('bookings', $adds);
    }

    public function down(): void
    {
        foreach (array_keys($this->fields) as $col) {
            if ($this->db->fieldExists($col, 'bookings')) {
                $this->forge->dropColumn('bookings', [$col]);
            }
        }
    }
}

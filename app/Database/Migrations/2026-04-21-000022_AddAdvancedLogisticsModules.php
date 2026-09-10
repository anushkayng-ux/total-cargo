<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seven modular add-ons. Each is gated by a feature_flag so a tenant can turn
 * them off without touching code. None of these tables are referenced by the
 * core booking → trip → invoice flow, so disabling has zero impact on operations.
 */
class AddAdvancedLogisticsModules extends Migration
{
    public function up(): void
    {
        // ── 1. Rate contracts: long-term rate cards per client/lane ─────────
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'contract_no'  => ['type' => 'VARCHAR', 'constraint' => 30],
            'client_id'    => ['type' => 'INT', 'unsigned' => true],
            'valid_from'   => ['type' => 'DATE'],
            'valid_to'     => ['type' => 'DATE'],
            'status'       => ['type' => 'ENUM', 'constraint' => ['Active','Expired','Suspended','Draft'], 'default' => 'Active'],
            'tds_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'gst_treatment'=> ['type' => 'ENUM', 'constraint' => ['rcm','fcm5','fcm12'], 'default' => 'fcm5'],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'created_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('contract_no');
        $this->forge->addKey('client_id');
        $this->forge->addForeignKey('client_id', 'clients', 'id', '', 'CASCADE');
        $this->forge->createTable('rate_contracts', true);

        // Lanes — one rate per (pickup → drop, vehicle type) inside a contract
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'contract_id'         => ['type' => 'INT', 'unsigned' => true],
            'pickup_city'         => ['type' => 'VARCHAR', 'constraint' => 80],
            'drop_city'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'vehicle_type'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'rate_inr'            => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'min_load_tons'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'free_loading_hrs'    => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'free_unloading_hrs'  => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'detention_per_hour'  => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'notes'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['contract_id','pickup_city','drop_city']);
        $this->forge->addForeignKey('contract_id', 'rate_contracts', 'id', '', 'CASCADE');
        $this->forge->createTable('rate_contract_lanes', true);

        // ── 2. Loading slot booking ─────────────────────────────────────────
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id'      => ['type' => 'INT', 'unsigned' => true],
            'plant_name'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'slot_date'       => ['type' => 'DATE'],
            'slot_window_start'=> ['type' => 'TIME', 'null' => true],
            'slot_window_end' => ['type' => 'TIME', 'null' => true],
            'gate_pass_no'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['Requested','Confirmed','Cancelled','Used','Missed'], 'default' => 'Requested'],
            'requested_at'    => ['type' => 'DATETIME', 'null' => true],
            'confirmed_at'    => ['type' => 'DATETIME', 'null' => true],
            'used_at'         => ['type' => 'DATETIME', 'null' => true],
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_id');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', '', 'CASCADE');
        $this->forge->createTable('loading_slots', true);

        // ── 3. e-POD with consignee digital signature ───────────────────────
        $this->forge->addColumn('trips', [
            'epod_token' => [
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'pod_received_at',
            ],
        ]);
        $db = \Config\Database::connect();
        $row = $db->query("SHOW INDEX FROM trips WHERE Key_name = 'idx_trip_epod_token'")->getRow();
        if ($row === null) {
            $db->query("CREATE INDEX idx_trip_epod_token ON trips (epod_token)");
        }
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'        => ['type' => 'INT', 'unsigned' => true],
            'consignee_name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'consignee_mobile'=> ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'signed_at'      => ['type' => 'DATETIME'],
            'signature_data' => ['type' => 'MEDIUMTEXT'], // PNG data URL
            'remarks'        => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'damage_noted'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'shortage_noted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ip_address'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'geo_lat'        => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'geo_lng'        => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addForeignKey('trip_id', 'trips', 'id', '', 'CASCADE');
        $this->forge->createTable('epod_signatures', true);

        // ── 4. TDS certificate (Form 16A) collection — quarterly ────────────
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'       => ['type' => 'INT', 'unsigned' => true],
            'financial_year'  => ['type' => 'VARCHAR', 'constraint' => 9],   // e.g., "2025-2026"
            'quarter'         => ['type' => 'ENUM', 'constraint' => ['Q1','Q2','Q3','Q4']],
            'expected_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'received_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'certificate_no'  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'certificate_path'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'received_date'   => ['type' => 'DATE', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['Pending','Received','Disputed','Reconciled'], 'default' => 'Pending'],
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['client_id','financial_year','quarter']);
        $this->forge->addForeignKey('client_id', 'clients', 'id', '', 'CASCADE');
        $this->forge->createTable('tds_certificates', true);

        // ── 5. Vendor security deposit ledger ───────────────────────────────
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'      => ['type' => 'INT', 'unsigned' => true],
            'txn_type'       => ['type' => 'ENUM', 'constraint' => ['Deposit','Release','Forfeit']],
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'balance_after'  => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'reference_trip_id'=> ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reason'         => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'txn_date'       => ['type' => 'DATE'],
            'reference_no'   => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'created_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('vendor_id');
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'CASCADE');
        $this->forge->createTable('vendor_deposits', true);

        // ── 6. Insurance quotes per trip (lane profitability is report-only) ─
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'        => ['type' => 'INT', 'unsigned' => true],
            'cargo_value'    => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'premium'        => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'provider'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'policy_no'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'valid_from'     => ['type' => 'DATETIME', 'null' => true],
            'valid_until'    => ['type' => 'DATETIME', 'null' => true],
            'raw_payload'    => ['type' => 'MEDIUMTEXT', 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['Quoted','Bound','Cancelled'], 'default' => 'Quoted'],
            'created_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addForeignKey('trip_id', 'trips', 'id', '', 'CASCADE');
        $this->forge->createTable('insurance_quotes', true);

        // ── Seed 7 new feature flags (default OFF — admin enables what they need) ──
        $b = $db->table('feature_flags');
        $now = date('Y-m-d H:i:s');
        foreach ([
            'rate_contracts'    => 'Long-term rate cards per client/lane',
            'loading_slots'     => 'Loading slot booking with consignor warehouse',
            'epod'              => 'Consignee digital signature on POD',
            'tds_certificates'  => 'Quarterly Form 16A collection workflow',
            'vendor_deposits'   => 'Vendor security deposit ledger',
            'lane_profitability'=> 'Lane-level profitability report',
            'trip_insurance'    => 'Per-trip cargo insurance quotes',
        ] as $key => $note) {
            if ($b->where('flag_key', $key)->countAllResults() === 0) {
                $b->insert(['flag_key' => $key, 'enabled' => 0, 'notes' => $note, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        try { $db->query("DROP INDEX idx_trip_epod_token ON trips"); } catch (\Throwable $e) {}
        $this->forge->dropColumn('trips', ['epod_token']);
        $this->forge->dropTable('insurance_quotes', true);
        $this->forge->dropTable('vendor_deposits', true);
        $this->forge->dropTable('tds_certificates', true);
        $this->forge->dropTable('epod_signatures', true);
        $this->forge->dropTable('loading_slots', true);
        $this->forge->dropTable('rate_contract_lanes', true);
        $this->forge->dropTable('rate_contracts', true);
        $db->table('feature_flags')->whereIn('flag_key', [
            'rate_contracts','loading_slots','epod','tds_certificates','vendor_deposits','lane_profitability','trip_insurance',
        ])->delete();
    }
}

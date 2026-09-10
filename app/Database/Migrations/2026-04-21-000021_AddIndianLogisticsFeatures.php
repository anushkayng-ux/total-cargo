<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndianLogisticsFeatures extends Migration
{
    public function up(): void
    {
        // ── clients: GST treatment, TDS rate, detention policy, MSME flag
        $this->forge->addColumn('clients', [
            'gst_treatment' => [
                // rcm  : Reverse Charge — recipient pays GST, transporter shows tax = 0
                // fcm5 : Forward 5% GTA without ITC
                // fcm12: Forward 12% GTA with ITC
                'type' => 'ENUM', 'constraint' => ['rcm','fcm5','fcm12'],
                'default' => 'fcm5', 'after' => 'pincode',
            ],
            'tds_rate' => [
                'type' => 'DECIMAL', 'constraint' => '5,2',
                'default' => 2.00, 'after' => 'gst_treatment',
            ],
            'detention_free_hours_loading' => [
                'type' => 'TINYINT', 'unsigned' => true, 'default' => 4, 'after' => 'tds_rate',
            ],
            'detention_free_hours_unloading' => [
                'type' => 'TINYINT', 'unsigned' => true, 'default' => 4, 'after' => 'detention_free_hours_loading',
            ],
            'detention_rate_per_hour' => [
                'type' => 'DECIMAL', 'constraint' => '10,2',
                'default' => 150.00, 'after' => 'detention_free_hours_unloading',
            ],
            'is_msme' => [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'detention_rate_per_hour',
            ],
        ]);

        // ── trips: cargo insurance, dwell timestamps, detention summary
        $this->forge->addColumn('trips', [
            'cargo_value_inr' => [
                'type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true, 'after' => 'remarks',
            ],
            'insurance_policy_no' => [
                'type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'cargo_value_inr',
            ],
            'insurance_provider' => [
                'type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'insurance_policy_no',
            ],
            'loading_arrived_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'insurance_provider',
            ],
            'loading_departed_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'loading_arrived_at',
            ],
            'unloading_arrived_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'loading_departed_at',
            ],
            'unloading_departed_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'unloading_arrived_at',
            ],
            'detention_billable_hours' => [
                'type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0, 'after' => 'unloading_departed_at',
            ],
            'detention_amount' => [
                'type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'after' => 'detention_billable_hours',
            ],
        ]);

        // ── invoices: TDS, GST treatment snapshot, detention line
        $this->forge->addColumn('invoices', [
            'gst_treatment' => [
                'type' => 'ENUM', 'constraint' => ['rcm','fcm5','fcm12'],
                'default' => 'fcm5', 'after' => 'igst_amount',
            ],
            'detention_amount' => [
                'type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'after' => 'gst_treatment',
            ],
            'tds_rate' => [
                'type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0, 'after' => 'detention_amount',
            ],
            'tds_amount' => [
                'type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'after' => 'tds_rate',
            ],
            'net_receivable' => [
                'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'tds_amount',
            ],
        ]);

        // ── drivers: KYC fields
        $this->forge->addColumn('drivers', [
            'aadhaar_last_4' => [
                'type' => 'VARCHAR', 'constraint' => 4, 'null' => true, 'after' => 'license_no',
            ],
            'dl_verified' => [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'aadhaar_last_4',
            ],
            'kyc_status' => [
                'type' => 'ENUM', 'constraint' => ['Pending','Verified','Rejected'],
                'default' => 'Pending', 'after' => 'dl_verified',
            ],
            'kyc_verified_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'kyc_status',
            ],
            'kyc_verified_by' => [
                'type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'kyc_verified_at',
            ],
            'kyc_notes' => [
                'type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'kyc_verified_by',
            ],
        ]);

        // ── trip_stops: multi-pickup / multi-drop sequence
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'      => ['type' => 'INT', 'unsigned' => true],
            'sequence'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'stop_type'    => ['type' => 'ENUM', 'constraint' => ['pickup','drop'], 'default' => 'pickup'],
            'address'      => ['type' => 'VARCHAR', 'constraint' => 400],
            'city'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'contact_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'contact_mobile'=> ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'gstin'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'planned_at'   => ['type' => 'DATETIME', 'null' => true],
            'arrived_at'   => ['type' => 'DATETIME', 'null' => true],
            'departed_at'  => ['type' => 'DATETIME', 'null' => true],
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['trip_id','sequence']);
        $this->forge->addForeignKey('trip_id', 'trips', 'id', '', 'CASCADE');
        $this->forge->createTable('trip_stops', true);

        // ── trip_advances: bhatta / cash advance to driver per trip
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'        => ['type' => 'INT', 'unsigned' => true],
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'mode'           => ['type' => 'ENUM', 'constraint' => ['Cash','UPI','Bank Transfer','FuelCard','FastTag'], 'default' => 'Cash'],
            'reference_no'   => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'given_to'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'given_at'       => ['type' => 'DATETIME', 'null' => true],
            'given_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'settled_at'     => ['type' => 'DATETIME', 'null' => true],
            'settled_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'notes'          => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addForeignKey('trip_id', 'trips', 'id', '', 'CASCADE');
        $this->forge->createTable('trip_advances', true);

        // ── ewb_consolidated: one consolidated EWB across multiple LRs
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'consol_no'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'vehicle_no'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'from_state'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'trip_ids_json' => ['type' => 'TEXT', 'null' => true],
            'generated_at'  => ['type' => 'DATETIME', 'null' => true],
            'valid_until'   => ['type' => 'DATETIME', 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Active'],
            'raw_payload'   => ['type' => 'MEDIUMTEXT', 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('consol_no');
        $this->forge->createTable('ewb_consolidated', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ewb_consolidated', true);
        $this->forge->dropTable('trip_advances', true);
        $this->forge->dropTable('trip_stops', true);
        $this->forge->dropColumn('drivers', ['aadhaar_last_4','dl_verified','kyc_status','kyc_verified_at','kyc_verified_by','kyc_notes']);
        $this->forge->dropColumn('invoices', ['gst_treatment','detention_amount','tds_rate','tds_amount','net_receivable']);
        $this->forge->dropColumn('trips', ['cargo_value_inr','insurance_policy_no','insurance_provider','loading_arrived_at','loading_departed_at','unloading_arrived_at','unloading_departed_at','detention_billable_hours','detention_amount']);
        $this->forge->dropColumn('clients', ['gst_treatment','tds_rate','detention_free_hours_loading','detention_free_hours_unloading','detention_rate_per_hour','is_msme']);
    }
}

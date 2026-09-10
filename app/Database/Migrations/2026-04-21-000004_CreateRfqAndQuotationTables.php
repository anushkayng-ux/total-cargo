<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRfqAndQuotationTables extends Migration
{
    public function up(): void
    {
        // rfq_master
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rfq_no'             => ['type' => 'VARCHAR', 'constraint' => 30],
            'lead_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'masked_reference'   => ['type' => 'VARCHAR', 'constraint' => 40],
            'pickup_city'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'drop_city'          => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'vehicle_type'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'material_category'  => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'weight'             => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'weight_unit'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TON'],
            'loading_date'       => ['type' => 'DATE', 'null' => true],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Open'],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('rfq_no');
        $this->forge->addUniqueKey('masked_reference');
        $this->forge->addKey('lead_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', '', 'SET NULL');
        $this->forge->createTable('rfq_master', true);

        // rfq_vendors
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rfq_id'              => ['type' => 'INT', 'unsigned' => true],
            'vendor_id'           => ['type' => 'INT', 'unsigned' => true],
            'whatsapp_message_id' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'sent_at'             => ['type' => 'DATETIME', 'null' => true],
            'reminder_count'      => ['type' => 'INT', 'default' => 0],
            'last_reminder_at'    => ['type' => 'DATETIME', 'null' => true],
            'response_status'     => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Pending'],
            'responded_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['rfq_id', 'vendor_id']);
        $this->forge->addForeignKey('rfq_id', 'rfq_master', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'CASCADE');
        $this->forge->createTable('rfq_vendors', true);

        // quotations
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rfq_id'                  => ['type' => 'INT', 'unsigned' => true],
            'vendor_id'               => ['type' => 'INT', 'unsigned' => true],
            'quote_amount'            => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'availability_notes'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'transit_days'            => ['type' => 'INT', 'null' => true],
            'quote_valid_till'        => ['type' => 'DATE', 'null' => true],
            'response_source'         => ['type' => 'ENUM', 'constraint' => ['whatsapp', 'manual', 'email', 'phone'], 'default' => 'manual'],
            'is_shortlisted'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_final_selected'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'response_time_minutes'   => ['type' => 'INT', 'null' => true],
            'remarks'                 => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['rfq_id', 'vendor_id']);
        $this->forge->addForeignKey('rfq_id', 'rfq_master', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'CASCADE');
        $this->forge->createTable('quotations', true);

        // quotation_comparison_logs
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rfq_id'             => ['type' => 'INT', 'unsigned' => true],
            'selected_vendor_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'logic_notes'        => ['type' => 'TEXT', 'null' => true],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('rfq_id');
        $this->forge->addForeignKey('rfq_id', 'rfq_master', 'id', '', 'CASCADE');
        $this->forge->createTable('quotation_comparison_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('quotation_comparison_logs', true);
        $this->forge->dropTable('quotations', true);
        $this->forge->dropTable('rfq_vendors', true);
        $this->forge->dropTable('rfq_master', true);
    }
}

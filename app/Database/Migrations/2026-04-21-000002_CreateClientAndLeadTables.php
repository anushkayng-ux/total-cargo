<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientAndLeadTables extends Migration
{
    public function up(): void
    {
        // clients
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_code'  => ['type' => 'VARCHAR', 'constraint' => 30],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'contact_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'mobile'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alt_mobile'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'gst_no'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'pan_no'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'address'      => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'city'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'state'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'pincode'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'credit_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'credit_days'  => ['type' => 'INT', 'default' => 0],
            'status'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('client_code');
        $this->forge->addKey('mobile');
        $this->forge->addKey('company_name');
        $this->forge->createTable('clients', true);

        // lead_sources
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'source_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'source_key'  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'status'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('source_key');
        $this->forge->createTable('lead_sources', true);

        // leads
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'lead_no'                => ['type' => 'VARCHAR', 'constraint' => 30],
            'lead_datetime'          => ['type' => 'DATETIME', 'null' => true],
            'source_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'client_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'client_name'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'company_name'           => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'mobile'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alt_mobile'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'                  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'pickup_city'            => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'pickup_state'           => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'drop_city'              => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'drop_state'             => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'material_type'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'vehicle_type_required'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'weight'                 => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'weight_unit'            => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TON'],
            'expected_dispatch_date' => ['type' => 'DATE', 'null' => true],
            'priority'               => ['type' => 'ENUM', 'constraint' => ['Low', 'Normal', 'High', 'Urgent'], 'default' => 'Normal'],
            'assigned_crm_user_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'current_status'         => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'New'],
            'lost_reason'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'remarks'                => ['type' => 'TEXT', 'null' => true],
            'created_by'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('lead_no');
        $this->forge->addKey('current_status');
        $this->forge->addKey('assigned_crm_user_id');
        $this->forge->addKey('client_id');
        $this->forge->addKey(['pickup_city', 'drop_city']);
        $this->forge->createTable('leads', true);

        // lead_followups
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'lead_id'               => ['type' => 'INT', 'unsigned' => true],
            'followup_datetime'     => ['type' => 'DATETIME', 'null' => true],
            'followup_type'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'discussion_notes'      => ['type' => 'TEXT', 'null' => true],
            'next_followup_datetime'=> ['type' => 'DATETIME', 'null' => true],
            'created_by'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('lead_id');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', '', 'CASCADE');
        $this->forge->createTable('lead_followups', true);

        // lead_status_history
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'lead_id'    => ['type' => 'INT', 'unsigned' => true],
            'old_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'new_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'remarks'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'changed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'changed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('lead_id');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', '', 'CASCADE');
        $this->forge->createTable('lead_status_history', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('lead_status_history', true);
        $this->forge->dropTable('lead_followups', true);
        $this->forge->dropTable('leads', true);
        $this->forge->dropTable('lead_sources', true);
        $this->forge->dropTable('clients', true);
    }
}

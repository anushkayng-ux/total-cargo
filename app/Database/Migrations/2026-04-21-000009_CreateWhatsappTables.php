<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsappTables extends Migration
{
    public function up(): void
    {
        // whatsapp_templates
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'template_key'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'audience_type' => ['type' => 'ENUM', 'constraint' => ['client', 'vendor', 'internal', 'driver'], 'default' => 'client'],
            'template_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'body_text'     => ['type' => 'TEXT', 'null' => true],
            'variables'     => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('template_key');
        $this->forge->addKey('audience_type');
        $this->forge->createTable('whatsapp_templates', true);

        // whatsapp_logs (outgoing)
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'module_name'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'module_ref_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'audience_type'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'recipient_no'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'template_key'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'message_type'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'text'],
            'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'delivery_status'     => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Queued'],
            'request_payload'     => ['type' => 'MEDIUMTEXT', 'null' => true],
            'response_payload'    => ['type' => 'MEDIUMTEXT', 'null' => true],
            'error_message'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'retry_count'         => ['type' => 'INT', 'default' => 0],
            'sent_at'             => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'        => ['type' => 'DATETIME', 'null' => true],
            'read_at'             => ['type' => 'DATETIME', 'null' => true],
            'failed_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('recipient_no');
        $this->forge->addKey('provider_message_id');
        $this->forge->addKey(['module_name', 'module_ref_id']);
        $this->forge->addKey('delivery_status');
        $this->forge->createTable('whatsapp_logs', true);

        // whatsapp_incoming_messages
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sender_no'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'message_type'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'text'],
            'text_body'           => ['type' => 'TEXT', 'null' => true],
            'media_url'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'media_type'          => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'media_id'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'rfq_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'trip_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'booking_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vendor_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'client_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_processed'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'raw_payload'         => ['type' => 'MEDIUMTEXT', 'null' => true],
            'received_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('sender_no');
        $this->forge->addKey('provider_message_id');
        $this->forge->addKey('rfq_id');
        $this->forge->addKey('trip_id');
        $this->forge->createTable('whatsapp_incoming_messages', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('whatsapp_incoming_messages', true);
        $this->forge->dropTable('whatsapp_logs', true);
        $this->forge->dropTable('whatsapp_templates', true);
    }
}

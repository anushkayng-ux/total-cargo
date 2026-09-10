<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailTables extends Migration
{
    public function up(): void
    {
        // email_templates — HTML transactional templates with variable interpolation.
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'template_key'   => ['type' => 'VARCHAR', 'constraint' => 80],
            'audience_type'  => ['type' => 'ENUM', 'constraint' => ['client','vendor','internal','driver'], 'default' => 'client'],
            'subject'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'body_html'      => ['type' => 'MEDIUMTEXT'],
            'body_text'      => ['type' => 'TEXT', 'null' => true],
            'variables_json' => ['type' => 'TEXT', 'null' => true],
            'status'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('template_key');
        $this->forge->createTable('email_templates', true);

        // email_logs — every email message we attempt to send.
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tracking_token'    => ['type' => 'VARCHAR', 'constraint' => 64],
            'template_key'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'to_email'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'to_name'           => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'cc'                => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'bcc'               => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'reply_to'          => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'subject'           => ['type' => 'VARCHAR', 'constraint' => 250],
            'body_html'         => ['type' => 'MEDIUMTEXT', 'null' => true],
            'body_text'         => ['type' => 'TEXT', 'null' => true],
            'attachments_json'  => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => [
                'Queued','Sending','Sent','Delivered','Failed','Bounced','Complained','Suppressed','Opened','Clicked'
            ], 'default' => 'Queued'],
            'provider'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'provider_msg_id'   => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'error'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'attempts'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'queued_at'         => ['type' => 'DATETIME', 'null' => true],
            'sent_at'           => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'      => ['type' => 'DATETIME', 'null' => true],
            'opened_at'         => ['type' => 'DATETIME', 'null' => true],
            'open_count'        => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'first_clicked_at'  => ['type' => 'DATETIME', 'null' => true],
            'click_count'       => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'bounced_at'        => ['type' => 'DATETIME', 'null' => true],
            'bounce_type'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'complained_at'     => ['type' => 'DATETIME', 'null' => true],
            'unsubscribed_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_event_at'     => ['type' => 'DATETIME', 'null' => true],
            'related_module'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'related_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'related_client_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sent_by_user_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('tracking_token');
        $this->forge->addKey('to_email');
        $this->forge->addKey('status');
        $this->forge->addKey('provider_msg_id');
        $this->forge->addKey(['related_module','related_id']);
        $this->forge->addKey('related_client_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('email_logs', true);

        // email_events — full event history (one log row may have multiple events)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'email_log_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'event_type'   => ['type' => 'VARCHAR', 'constraint' => 30],
            'data_json'    => ['type' => 'TEXT', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('email_log_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('email_log_id', 'email_logs', 'id', '', 'CASCADE');
        $this->forge->createTable('email_events', true);

        // email_unsubscribes — suppression list. Sent emails check this before dispatch.
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'reason'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'source'     => ['type' => 'ENUM', 'constraint' => ['link','complaint','bounce','manual'], 'default' => 'link'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('email_unsubscribes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('email_unsubscribes', true);
        $this->forge->dropTable('email_events', true);
        $this->forge->dropTable('email_logs', true);
        $this->forge->dropTable('email_templates', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMeetingsTables extends Migration
{
    public function up(): void
    {
        // ── meetings : one row per planned client/external meeting ──
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'title'               => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'with_company'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'with_contact_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'with_contact_phone'  => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true],
            'location'            => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'location_lat'        => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'location_lng'        => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'scheduled_at'        => ['type' => 'DATETIME', 'null' => false],
            'pwa_token'           => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['Planned','InProgress','Completed','Cancelled'], 'default' => 'Planned'],
            'outcome'             => ['type' => 'TEXT', 'null' => true],
            'next_steps'          => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('scheduled_at');
        $this->forge->addUniqueKey('pwa_token');
        $this->forge->createTable('meetings');

        // ── meeting_events : punch events with GPS ──
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'event_type'   => ['type' => 'ENUM', 'constraint' => ['depart_office','arrive_meeting','leave_meeting','return_office'], 'null' => false],
            'occurred_at'  => ['type' => 'DATETIME', 'null' => false],
            'latitude'     => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'    => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'accuracy_m'   => ['type' => 'DECIMAL', 'constraint' => '8,1', 'null' => true],
            'source'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pwa'],
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('meeting_id');
        $this->forge->addKey(['meeting_id','event_type']);
        $this->forge->createTable('meeting_events');
    }

    public function down(): void
    {
        $this->forge->dropTable('meeting_events', true);
        $this->forge->dropTable('meetings', true);
    }
}

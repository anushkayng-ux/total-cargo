<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSupportAndHelpTables extends Migration
{
    public function up(): void
    {
        // ── support_tickets ────────────────────────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ticket_no'   => ['type' => 'VARCHAR', 'constraint' => 30],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],     // raised by
            'type'        => ['type' => 'ENUM', 'constraint' => ['Bug','Improvement','Question','Other'], 'default' => 'Question'],
            'priority'    => ['type' => 'ENUM', 'constraint' => ['Low','Normal','High','Urgent'], 'default' => 'Normal'],
            'subject'     => ['type' => 'VARCHAR', 'constraint' => 200],
            'body'        => ['type' => 'TEXT'],
            'screen_url'  => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['Open','In Progress','Resolved','Closed','Reopened'], 'default' => 'Open'],
            'assigned_to' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resolution'  => ['type' => 'TEXT', 'null' => true],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'resolved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'closed_at'   => ['type' => 'DATETIME', 'null' => true],
            // Rating (1–5) on closed tickets — only collected if support_rating_enabled = '1'
            'rating'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_comment' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'rated_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('ticket_no');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('assigned_to');
        $this->forge->createTable('support_tickets', true);

        // ── support_ticket_replies ─────────────────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ticket_id'   => ['type' => 'INT', 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'body'        => ['type' => 'TEXT'],
            'is_internal' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('ticket_id');
        $this->forge->addForeignKey('ticket_id', 'support_tickets', 'id', '', 'CASCADE');
        $this->forge->createTable('support_ticket_replies', true);

        // ── help_topics ────────────────────────────────────────────────────
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'               => ['type' => 'VARCHAR', 'constraint' => 100],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'category'           => ['type' => 'VARCHAR', 'constraint' => 60],
            'body_md'            => ['type' => 'MEDIUMTEXT'],
            // Comma-separated list of role_keys this topic is relevant to (or 'all')
            'applicable_roles'   => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'all'],
            'sort_order'         => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 100],
            'is_published'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'updated_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category');
        $this->forge->createTable('help_topics', true);

        // ── settings: rating toggle (default ON) ───────────────────────────
        $db = \Config\Database::connect();
        $b = $db->table('settings');
        if ($b->where('setting_key', 'support_rating_enabled')->countAllResults() === 0) {
            $b->insert([
                'setting_key'   => 'support_rating_enabled',
                'setting_value' => '1',
                'setting_group' => 'support',
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('help_topics', true);
        $this->forge->dropTable('support_ticket_replies', true);
        $this->forge->dropTable('support_tickets', true);
        \Config\Database::connect()->table('settings')->where('setting_key', 'support_rating_enabled')->delete();
    }
}

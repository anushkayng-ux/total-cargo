<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecordComments extends Migration
{
    public function up(): void
    {
        // record_comments — polymorphic internal-team chat thread for any record (booking, trip, lead).
        // Use record_type + record_id to attach a thread to any entity.
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'record_type'   => ['type' => 'VARCHAR', 'constraint' => 30],
            'record_id'     => ['type' => 'INT', 'unsigned' => true],
            'user_id'       => ['type' => 'INT', 'unsigned' => true],
            'body'          => ['type' => 'TEXT'],
            'mentions_json' => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['record_type', 'record_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('record_comments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('record_comments', true);
    }
}

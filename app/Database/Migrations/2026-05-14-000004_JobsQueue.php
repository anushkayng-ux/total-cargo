<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Generic background-job queue. One row = one unit of deferred work.
 *
 * Decouples HTTP request from heavy IO (PDF generation, bulk emails, vendor
 * webhooks, GPS provider polling) so users don't sit through 10s of
 * blocking work. A long-running worker (`spark tpt:queue-work`) picks rows
 * up by oldest-pending-first, marks them running, executes the handler,
 * and stamps the outcome.
 */
class JobsQueue extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'queue'        => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'default'],
            'handler'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],  // App\Jobs\SendInvoiceEmail or callable string
            'payload_json' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending','running','done','failed'], 'default' => 'pending'],
            'attempts'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'max_attempts' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 3],
            'available_at' => ['type' => 'DATETIME', 'null' => true],  // for deferred jobs
            'reserved_at'  => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'failed_at'    => ['type' => 'DATETIME', 'null' => true],
            'error_text'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'available_at']);
        $this->forge->addKey(['queue', 'status']);
        $this->forge->createTable('jobs');
    }

    public function down(): void { $this->forge->dropTable('jobs', true); }
}

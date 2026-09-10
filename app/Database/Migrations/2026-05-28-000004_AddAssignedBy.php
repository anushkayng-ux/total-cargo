<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `assigned_by` (the user who handed the work off) to leads, RFQs,
 * bookings and trips. Combined with `assigned_to`/`assigned_crm_user_id`, this
 * lets us close the loop: the assignee is notified on handoff, and the assigner
 * is notified back when the step progresses/completes.
 */
class AddAssignedBy extends Migration
{
    public function up(): void
    {
        foreach (['leads', 'rfq_master', 'bookings', 'trips'] as $table) {
            if (!$this->db->fieldExists('assigned_by', $table)) {
                $this->forge->addColumn($table, [
                    'assigned_by' => [
                        'type'       => 'INT',
                        'constraint' => 10,
                        'unsigned'   => true,
                        'null'       => true,
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['leads', 'rfq_master', 'bookings', 'trips'] as $table) {
            if ($this->db->fieldExists('assigned_by', $table)) {
                $this->forge->dropColumn($table, ['assigned_by']);
            }
        }
    }
}

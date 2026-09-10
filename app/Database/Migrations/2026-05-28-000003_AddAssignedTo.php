<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a staff-assignee column to RFQs, Bookings and Trips so a manager can
 * hand a specific work item to a specific team member (who then gets an in-app
 * notification). Leads already have `assigned_crm_user_id`.
 */
class AddAssignedTo extends Migration
{
    public function up(): void
    {
        foreach (['rfq_master', 'bookings', 'trips'] as $table) {
            if (!$this->db->fieldExists('assigned_to', $table)) {
                $this->forge->addColumn($table, [
                    'assigned_to' => [
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
        foreach (['rfq_master', 'bookings', 'trips'] as $table) {
            if ($this->db->fieldExists('assigned_to', $table)) {
                $this->forge->dropColumn($table, ['assigned_to']);
            }
        }
    }
}

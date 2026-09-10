<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Composite indexes for the hot paths surfaced by EXPLAIN on /dashboard,
 * /invoices, /trips, /quotations and the aging reports. Each one targets a
 * specific `WHERE … ORDER BY` pattern in the codebase — they're not
 * speculative.
 *
 * If you ever need to confirm an index is helping, enable the slow-query log
 * in my.ini (`slow_query_log=ON`, `long_query_time=0.2`) for an hour, then
 * `mysqldumpslow -s t writable/slow.log | head -30`.
 */
class PerfIndexes extends Migration
{
    /** @var array<int, array{table:string,name:string,cols:string}> */
    private array $indexes = [
        // Invoices list: status filter + due-date order
        ['table' => 'invoices',           'name' => 'idx_inv_status_due',         'cols' => '(invoice_status, due_date)'],
        // Receivables aging: balance + due
        ['table' => 'invoices',           'name' => 'idx_inv_balance_due',        'cols' => '(balance_due, due_date)'],
        // Vendor bills payables
        ['table' => 'vendor_bills',       'name' => 'idx_vb_status_due',          'cols' => '(status, due_date)'],
        ['table' => 'vendor_bills',       'name' => 'idx_vb_balance_due',         'cols' => '(balance_due, due_date)'],
        // Trips list: most-recent first, status filter
        ['table' => 'trips',              'name' => 'idx_trips_status_id',        'cols' => '(current_status, id)'],
        ['table' => 'trips',              'name' => 'idx_trips_pod_id',           'cols' => '(pod_status, id)'],
        // Leads: status + assignee + date — three biggest filter combinations
        ['table' => 'leads',              'name' => 'idx_leads_status_id',        'cols' => '(current_status, id)'],
        ['table' => 'leads',              'name' => 'idx_leads_assignee_status',  'cols' => '(assigned_crm_user_id, current_status)'],
        ['table' => 'leads',              'name' => 'idx_leads_datetime',         'cols' => '(lead_datetime)'],
        // RFQs list — status + most-recent
        ['table' => 'rfq_master',         'name' => 'idx_rfq_status_id',          'cols' => '(status, id)'],
        // Quotations — RFQ + shortlist/final flags drive almost every read
        ['table' => 'quotations',         'name' => 'idx_quotes_rfq_flags',       'cols' => '(rfq_id, is_shortlisted, is_final_selected)'],
        // GPS — latest_vehicle_status keyed by vehicle number + updated_at
        ['table' => 'latest_vehicle_status', 'name' => 'idx_lvs_vehicle_time',    'cols' => '(vehicle_number, updated_at)'],
        // Activity log — most-common filter combinations
        ['table' => 'activity_logs',      'name' => 'idx_alog_module_ref',        'cols' => '(module_name, module_ref_id)'],
        ['table' => 'activity_logs',      'name' => 'idx_alog_user_time',         'cols' => '(user_id, created_at)'],
        // Email logs status filters
        ['table' => 'email_logs',         'name' => 'idx_email_status_id',        'cols' => '(status, id)'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $idx) {
            if (!$this->db->tableExists($idx['table'])) continue;
            if ($this->indexExists($idx['table'], $idx['name'])) continue;
            $this->db->query("CREATE INDEX {$idx['name']} ON {$idx['table']} {$idx['cols']}");
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $idx) {
            if (!$this->db->tableExists($idx['table'])) continue;
            $this->db->query("DROP INDEX IF EXISTS {$idx['name']} ON {$idx['table']}");
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$name])->getResultArray();
        return !empty($rows);
    }
}

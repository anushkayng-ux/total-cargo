<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Promotes each report to its own permission so a role can be granted Payables
 * Aging *without* also seeing Profitability / SOP KPIs / Email Analytics / etc.
 *
 * Migration strategy:
 *  1. Insert one new permission row per report (module_key = report_*).
 *  2. Auto-grant the new permission to every role that currently has View on
 *     the underlying source module (e.g. report_payables → granted to roles
 *     that already have vendor_bills view). This preserves existing visibility.
 *  3. Always grant to admins & super-admins.
 *
 * After deploy, the admin can untick individual report_* permissions per role
 * to restrict reports independently of module access.
 */
class AddReportPermissions extends Migration
{
    /** [permission_key => [label, source_module_for_initial_grant]] */
    private array $reports = [
        'report_email_analytics'   => ['Report — Email Analytics',     'whatsapp'],
        'report_sop_kpis'          => ['Report — SOP / TAT KPIs',      'reports'],
        'report_profitability'     => ['Report — Profitability',       'bookings'],
        'report_receivables'       => ['Report — Receivables Aging',   'invoices'],
        'report_payables'          => ['Report — Payables Aging',      'vendor_bills'],
        'report_lead_funnel'       => ['Report — Lead Funnel',         'leads'],
        'report_vendor_scoring'    => ['Report — Vendor Scorecard',    'vendors'],
        'report_trip_expenses'     => ['Report — Trip Expenses',       'trips'],
        'report_unbilled_billable' => ['Report — Unbilled Billable',   'invoices'],
        'report_expense_categories'=> ['Report — Expense Categories',  'trips'],
    ];

    public function up(): void
    {
        $db   = $this->db;
        $pb   = $db->table('permissions');
        $rpb  = $db->table('role_permissions');

        // 1) Insert the new permission rows (skip if already present).
        foreach ($this->reports as $key => [$label, $_]) {
            if ($pb->where(['module_key' => $key, 'action_key' => 'access'])->countAllResults() === 0) {
                $pb->insert(['module_key' => $key, 'action_key' => 'access', 'label' => $label, 'status' => 1]);
            }
        }

        // 2) Build a [permission_key => permission_id] lookup.
        $idMap = [];
        foreach ($db->table('permissions')->whereIn('module_key', array_keys($this->reports))->get()->getResultArray() as $row) {
            $idMap[$row['module_key']] = (int) $row['id'];
        }

        // 3) Collect admin role IDs (always granted).
        $adminRoleIds = array_map(
            static fn ($r) => (int) $r['id'],
            $db->table('roles')->select('id')->where('role_key', 'admin')->get()->getResultArray()
        );

        // 4) For each report, grant to (a) admins, (b) every role that currently
        //    has can_view on the source module — so existing roles keep what
        //    they already see.
        foreach ($this->reports as $reportKey => [$_, $sourceModule]) {
            $permId = $idMap[$reportKey] ?? 0;
            if ($permId === 0) continue;

            // Roles to auto-grant: admins + roles with View on the source module.
            $sourceRoleIds = array_map(
                static fn ($r) => (int) $r['role_id'],
                $db->table('role_permissions rp')
                    ->select('rp.role_id')->distinct()
                    ->join('permissions p', 'p.id = rp.permission_id')
                    ->where('p.module_key', $sourceModule)
                    ->where('rp.can_view', 1)
                    ->get()->getResultArray()
            );
            $grantTo = array_values(array_unique(array_merge($adminRoleIds, $sourceRoleIds)));

            foreach ($grantTo as $roleId) {
                if ($rpb->where(['role_id' => $roleId, 'permission_id' => $permId])->countAllResults() === 0) {
                    $rpb->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                        'can_view'      => 1,
                        'can_add'       => 0,
                        'can_edit'      => 0,
                        'can_delete'    => 0,
                        'can_approve'   => 0,
                        'can_export'    => in_array($roleId, $adminRoleIds, true) ? 1 : 0,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $db    = $this->db;
        $keys  = array_keys($this->reports);
        $perms = $db->table('permissions')->whereIn('module_key', $keys)->get()->getResultArray();
        $ids   = array_map(static fn ($r) => (int) $r['id'], $perms);
        if (!empty($ids)) {
            $db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
            $db->table('permissions')->whereIn('id', $ids)->delete();
        }
    }
}

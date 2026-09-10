<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedHrmsModules extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        // ── 1. Permissions for the three new modules ──
        $modules = [
            'hrms'     => 'HR Management',
            'meetings' => 'Meetings / Travel',
            'payroll'  => 'Payroll',
        ];
        $pb = $db->table('permissions');
        foreach ($modules as $key => $label) {
            if ($pb->where(['module_key' => $key, 'action_key' => 'access'])->countAllResults() === 0) {
                $pb->insert(['module_key' => $key, 'action_key' => 'access', 'label' => $label . ' — Access', 'status' => 1]);
            }
        }

        // ── 2. Grant admin role every flag on the new permissions ──
        $adminRoleId = (int) ($db->table('roles')->where('role_key', 'admin')->get()->getRow('id') ?? 0);
        if ($adminRoleId) {
            $rp = $db->table('role_permissions');
            foreach (array_keys($modules) as $mod) {
                $permId = (int) ($db->table('permissions')->where(['module_key' => $mod, 'action_key' => 'access'])->get()->getRow('id') ?? 0);
                if (!$permId) continue;
                if ($rp->where(['role_id' => $adminRoleId, 'permission_id' => $permId])->countAllResults() === 0) {
                    $rp->insert([
                        'role_id' => $adminRoleId, 'permission_id' => $permId,
                        'can_view' => 1, 'can_add' => 1, 'can_edit' => 1,
                        'can_delete' => 1, 'can_approve' => 1, 'can_export' => 1,
                    ]);
                }
            }
        }

        // ── 3. Default leave types ──
        $leaveTypes = [
            ['code' => 'CL', 'name' => 'Casual Leave',  'default_annual_quota' => 12, 'is_paid' => 1, 'carries_forward' => 0],
            ['code' => 'SL', 'name' => 'Sick Leave',    'default_annual_quota' => 12, 'is_paid' => 1, 'carries_forward' => 0],
            ['code' => 'EL', 'name' => 'Earned Leave',  'default_annual_quota' => 18, 'is_paid' => 1, 'carries_forward' => 1],
            ['code' => 'LOP','name' => 'Loss of Pay',   'default_annual_quota' => 0,  'is_paid' => 0, 'carries_forward' => 0],
        ];
        $lt = $db->table('leave_types');
        foreach ($leaveTypes as $t) {
            if ($lt->where('code', $t['code'])->countAllResults() === 0) {
                $t['status'] = 1; $t['created_at'] = $now; $t['updated_at'] = $now;
                $lt->insert($t);
            }
        }

        // ── 4. Grant every staff role view-only on hrms + meetings (self-service) ──
        // Managers (crm_mgr, pur_mgr, ops_mgr, management) also get can_approve for hrms (leave approvals).
        $staffRoles  = ['management','crm_exec','crm_mgr','pur_exec','pur_mgr','ops_exec','ops_mgr','accounts'];
        $managerRoles= ['management','crm_mgr','pur_mgr','ops_mgr'];
        foreach (['hrms','meetings'] as $mod) {
            $permId = (int) ($db->table('permissions')->where(['module_key' => $mod, 'action_key' => 'access'])->get()->getRow('id') ?? 0);
            if (!$permId) continue;
            foreach ($staffRoles as $rk) {
                $roleId = (int) ($db->table('roles')->where('role_key', $rk)->get()->getRow('id') ?? 0);
                if (!$roleId) continue;
                $approve = ($mod === 'hrms' && in_array($rk, $managerRoles, true)) ? 1 : 0;
                $exists = $db->table('role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permId])->countAllResults();
                if ($exists === 0) {
                    $db->table('role_permissions')->insert([
                        'role_id' => $roleId, 'permission_id' => $permId,
                        'can_view' => 1, 'can_add' => 1, 'can_edit' => 1,
                        'can_delete' => 0, 'can_approve' => $approve, 'can_export' => 0,
                    ]);
                }
            }
        }

        // ── 5. Open leave balances for every active staff user for the current year ──
        $year = (int) date('Y');
        $users = $db->table('users')->where('status', 1)->where('deleted_at', null)->get()->getResultArray();
        $types = $db->table('leave_types')->where('status', 1)->get()->getResultArray();
        foreach ($users as $u) {
            foreach ($types as $t) {
                $exists = $db->table('leave_balances')
                    ->where(['user_id' => (int) $u['id'], 'leave_type_id' => (int) $t['id'], 'year' => $year])
                    ->countAllResults();
                if ($exists > 0) continue;
                $alloc = (float) $t['default_annual_quota'];
                $db->table('leave_balances')->insert([
                    'user_id'       => (int) $u['id'],
                    'leave_type_id' => (int) $t['id'],
                    'year'          => $year,
                    'allocated'     => $alloc,
                    'used'          => 0,
                    'balance'       => $alloc,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }

        // ── 6. Office geolocation + late-mark cut-off settings ──
        $defaults = [
            ['office_latitude',  '',     'hrms'],
            ['office_longitude', '',     'hrms'],
            ['office_geo_radius_m', '300','hrms'],
            ['office_punch_late_after', '09:30', 'hrms'],
            ['office_punch_in_required_in_office', '0', 'hrms'], // 0 = let staff punch from anywhere
        ];
        $st = $db->table('settings');
        foreach ($defaults as $d) {
            if ($st->where('setting_key', $d[0])->countAllResults() === 0) {
                $st->insert(['setting_key' => $d[0], 'setting_value' => $d[1], 'setting_group' => $d[2], 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->table('settings')->whereIn('setting_key', [
            'office_latitude','office_longitude','office_geo_radius_m','office_punch_late_after','office_punch_in_required_in_office'
        ])->delete();
        $permIds = array_column($db->table('permissions')->whereIn('module_key', ['hrms','meetings','payroll'])->get()->getResultArray(), 'id');
        if (!empty($permIds)) {
            $db->table('role_permissions')->whereIn('permission_id', $permIds)->delete();
            $db->table('permissions')->whereIn('id', $permIds)->delete();
        }
        $db->table('leave_balances')->where('year', (int) date('Y'))->delete();
        $db->table('leave_types')->whereIn('code', ['CL','SL','EL','LOP'])->delete();
    }
}

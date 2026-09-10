<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Creates one demo user per role (Management, CRM Exec/Mgr, Purchase Exec/Mgr,
 * Operations Exec/Mgr, Accounts) and grants each role a sensible permission set
 * so the user can actually navigate the app. Idempotent: safe to re-run.
 *
 * NEVER touches the seeded admin@tpt.local — only adds peers around it.
 *
 * Default password for every demo user: `Demo@123` (8 chars, meets policy).
 */
class DemoUsersSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Demo@123';

    public function run(): void
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // ── 1. Demo users to create ────────────────────────────────────────
        $users = [
            // role_key       email                  name                         mobile
            ['management',    'mgr@tpt.local',       'Mira Mehta (Management)',   '9810000001'],
            ['crm_exec',      'crm.exec@tpt.local',  'Cory Sharma (CRM Exec)',    '9810000002'],
            ['crm_mgr',       'crm.mgr@tpt.local',   'Charu Iyer (CRM Mgr)',      '9810000003'],
            ['pur_exec',      'pur.exec@tpt.local',  'Pranav Singh (Purchase)',   '9810000004'],
            ['pur_mgr',       'pur.mgr@tpt.local',   'Priya Nair (Purchase Mgr)', '9810000005'],
            ['ops_exec',      'ops.exec@tpt.local',  'Omar Khan (Ops Exec)',      '9810000006'],
            ['ops_mgr',       'ops.mgr@tpt.local',   'Olivia Rao (Ops Mgr)',      '9810000007'],
            ['accounts',      'accounts@tpt.local',  'Anil Bhat (Accounts)',      '9810000008'],
        ];

        $uBuilder = $db->table('users');
        $rBuilder = $db->table('roles');
        foreach ($users as [$roleKey, $email, $name, $mobile]) {
            $roleId = (int) ($rBuilder->where('role_key', $roleKey)->get()->getRow('id') ?? 0);
            if (!$roleId) continue;
            if ($uBuilder->where('email', $email)->countAllResults() > 0) {
                $uBuilder->where('email', $email)->update([
                    'role_id'    => $roleId,
                    'name'       => $name,
                    'mobile'     => $mobile,
                    'status'     => 1,
                    'updated_at' => $now,
                ]);
                continue;
            }
            $uBuilder->insert([
                'role_id'        => $roleId,
                'name'           => $name,
                'email'          => $email,
                'mobile'         => $mobile,
                'password_hash'  => password_hash(self::DEFAULT_PASSWORD, PASSWORD_BCRYPT),
                'status'         => 1,
                'is_super_admin' => 0,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // ── 2. Role permission matrix ──────────────────────────────────────
        // Each role gets sensible can_view / can_add / can_edit / can_delete /
        // can_approve / can_export defaults across the 24 modules.
        // Layout: 'role_key' => [ 'module_key' => [V,A,E,D,Ap,Ex] ]
        // V=view, A=add, E=edit, D=delete, Ap=approve, Ex=export
        $matrix = [
            'management' => $this->wholeApp(),  // see helper below — full read + most writes, no destructive
            'crm_exec'   => [
                'dashboard'    => [1,0,0,0,0,0],
                'leads'        => [1,1,1,0,0,1],
                'lead_sources' => [1,0,0,0,0,0],
                'clients'      => [1,1,1,0,0,1],
                'comments'     => [1,1,1,0,0,0],
                'documents'    => [1,1,0,0,0,0],
                'whatsapp'     => [1,1,0,0,0,0],
                'reports'      => [1,0,0,0,0,1],
            ],
            'crm_mgr'    => [
                'dashboard'    => [1,0,0,0,0,0],
                'leads'        => [1,1,1,1,1,1],
                'lead_sources' => [1,1,1,1,0,0],
                'clients'      => [1,1,1,1,0,1],
                'rfq'          => [1,0,0,0,0,1],   // see-only
                'quotations'   => [1,0,0,0,0,1],
                'comments'     => [1,1,1,1,0,0],
                'documents'    => [1,1,1,0,0,0],
                'whatsapp'     => [1,1,1,1,0,0],
                'reports'      => [1,0,0,0,0,1],
            ],
            'pur_exec'   => [
                'dashboard'    => [1,0,0,0,0,0],
                'vendors'      => [1,1,1,0,0,1],
                'rfq'          => [1,1,1,0,0,1],
                'quotations'   => [1,1,1,0,0,1],
                'leads'        => [1,0,0,0,0,0],
                'comments'     => [1,1,1,0,0,0],
                'whatsapp'     => [1,1,0,0,0,0],
                'documents'    => [1,1,0,0,0,0],
                'reports'      => [1,0,0,0,0,1],
            ],
            'pur_mgr'    => [
                'dashboard'    => [1,0,0,0,0,0],
                'vendors'      => [1,1,1,1,1,1],
                'rfq'          => [1,1,1,1,1,1],
                'quotations'   => [1,1,1,1,1,1],
                'leads'        => [1,0,0,0,0,0],
                'comments'     => [1,1,1,1,0,0],
                'whatsapp'     => [1,1,1,1,0,0],
                'documents'    => [1,1,1,1,0,0],
                'reports'      => [1,0,0,0,0,1],
            ],
            'ops_exec'   => [
                'dashboard'    => [1,0,0,0,0,0],
                'bookings'     => [1,1,1,0,0,1],
                'trips'        => [1,1,1,0,0,1],
                'drivers'      => [1,1,1,0,0,0],
                'vehicles'     => [1,1,1,0,0,0],
                'gps'          => [1,0,0,0,0,1],
                'documents'    => [1,1,0,0,0,0],
                'comments'     => [1,1,1,0,0,0],
                'whatsapp'     => [1,1,0,0,0,0],
                'reports'      => [1,0,0,0,0,1],
            ],
            'ops_mgr'    => [
                'dashboard'    => [1,0,0,0,0,0],
                'bookings'     => [1,1,1,1,1,1],
                'trips'        => [1,1,1,1,1,1],
                'drivers'      => [1,1,1,1,0,0],
                'vehicles'     => [1,1,1,1,0,0],
                'gps'          => [1,1,1,0,0,1],
                'documents'    => [1,1,1,1,0,0],
                'comments'     => [1,1,1,1,0,0],
                'whatsapp'     => [1,1,1,1,0,0],
                'reports'      => [1,0,0,0,0,1],
            ],
            'accounts'   => [
                'dashboard'    => [1,0,0,0,0,0],
                'invoices'     => [1,1,1,1,1,1],
                'receipts'     => [1,1,1,1,0,1],
                'vendor_bills' => [1,1,1,1,1,1],
                'vendor_payments' => [1,1,1,1,0,1],
                'clients'      => [1,0,0,0,0,1],
                'vendors'      => [1,0,0,0,0,1],
                'bookings'     => [1,0,0,0,0,1],
                'trips'        => [1,0,0,0,0,1],
                'comments'     => [1,1,1,0,0,0],
                'documents'    => [1,1,0,0,0,0],
                'reports'      => [1,0,0,0,0,1],
                'email'        => [1,0,0,0,0,0],
            ],
        ];

        $rpBuilder = $db->table('role_permissions');
        $pBuilder  = $db->table('permissions');

        foreach ($matrix as $roleKey => $perModule) {
            $roleId = (int) ($rBuilder->where('role_key', $roleKey)->get()->getRow('id') ?? 0);
            if (!$roleId) continue;

            foreach ($perModule as $moduleKey => $flags) {
                [$view, $add, $edit, $del, $appr, $exp] = $flags;
                $permId = (int) ($pBuilder->where(['module_key' => $moduleKey, 'action_key' => 'access'])->get()->getRow('id') ?? 0);
                if (!$permId) continue;

                $existing = $rpBuilder->where(['role_id' => $roleId, 'permission_id' => $permId])->get()->getRow();
                $row = [
                    'can_view'    => $view,
                    'can_add'     => $add,
                    'can_edit'    => $edit,
                    'can_delete'  => $del,
                    'can_approve' => $appr,
                    'can_export'  => $exp,
                ];
                if ($existing) {
                    $rpBuilder->where('id', $existing->id)->update($row);
                } else {
                    $rpBuilder->insert(array_merge(['role_id' => $roleId, 'permission_id' => $permId], $row));
                }
            }
        }
    }

    /** Management = read everything, write everywhere, but no can_delete by default. */
    private function wholeApp(): array
    {
        $modules = [
            'dashboard','users','roles','clients','vendors','drivers','vehicles',
            'lead_sources','leads','rfq','quotations','bookings','trips','gps',
            'invoices','receipts','vendor_bills','vendor_payments','documents',
            'whatsapp','reports','settings','audit_logs','comments','email','client_portal',
        ];
        $out = [];
        foreach ($modules as $m) $out[$m] = [1,1,1,0,1,1];
        return $out;
    }
}

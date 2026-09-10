<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $modules = [
            'dashboard'       => 'Dashboard',
            'users'           => 'Users',
            'roles'           => 'Roles & Permissions',
            'clients'         => 'Clients',
            'vendors'         => 'Vendors',
            'drivers'         => 'Drivers',
            'vehicles'        => 'Vehicles',
            'lead_sources'    => 'Lead Sources',
            'leads'           => 'Leads',
            'rfq'             => 'RFQ / Purchase',
            'quotations'      => 'Quotations',
            'bookings'        => 'Bookings',
            'trips'           => 'Trips / Operations',
            'gps'             => 'GPS Tracking',
            'invoices'        => 'Invoices / Billing',
            'receipts'        => 'Client Receipts',
            'vendor_bills'    => 'Vendor Bills',
            'vendor_payments' => 'Vendor Payments',
            'documents'       => 'Documents',
            'whatsapp'        => 'WhatsApp',
            'reports'         => 'Reports & Dashboards',
            'settings'        => 'Settings',
            'audit_logs'      => 'Audit Logs',
            'client_portal'   => 'Client Portal Management',
            'email'           => 'Email Templates / Logs',
            'comments'        => 'Internal Comments',
            'rate_contracts'  => 'Rate Contracts',
            'tds_certificates'=> 'TDS Certificates (Form 16A)',
            'vendor_deposits' => 'Vendor Security Deposits',
            'support'         => 'Support Tickets',
            'help'            => 'Help &amp; Documentation',
        ];

        $rows = [];
        foreach ($modules as $key => $label) {
            $rows[] = ['module_key' => $key, 'action_key' => 'access', 'label' => $label . ' — Access', 'status' => 1];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('permissions');
        foreach ($rows as $r) {
            $exists = $builder->where(['module_key' => $r['module_key'], 'action_key' => $r['action_key']])->countAllResults();
            if ($exists === 0) {
                $builder->insert($r);
            }
        }

        // roles
        $roles = [
            ['role_key' => 'admin',      'role_name' => 'Administrator',    'status' => 1],
            ['role_key' => 'management', 'role_name' => 'Management',       'status' => 1],
            ['role_key' => 'crm_exec',   'role_name' => 'CRM Executive',    'status' => 1],
            ['role_key' => 'crm_mgr',    'role_name' => 'CRM Manager',      'status' => 1],
            ['role_key' => 'pur_exec',   'role_name' => 'Purchase Executive','status' => 1],
            ['role_key' => 'pur_mgr',    'role_name' => 'Purchase Manager', 'status' => 1],
            ['role_key' => 'ops_exec',   'role_name' => 'Operations Exec',  'status' => 1],
            ['role_key' => 'ops_mgr',    'role_name' => 'Operations Manager','status' => 1],
            ['role_key' => 'accounts',   'role_name' => 'Accounts',         'status' => 1],
        ];

        $rBuilder = $db->table('roles');
        foreach ($roles as $r) {
            $exists = $rBuilder->where('role_key', $r['role_key'])->countAllResults();
            if ($exists === 0) {
                $r['created_at'] = $now;
                $r['updated_at'] = $now;
                $rBuilder->insert($r);
            }
        }

        // Admin gets every permission, all flags enabled
        $adminRoleId = $db->table('roles')->where('role_key', 'admin')->get()->getRow('id');
        $allPerms = $db->table('permissions')->get()->getResultArray();
        $rpBuilder = $db->table('role_permissions');
        foreach ($allPerms as $p) {
            $exists = $rpBuilder->where(['role_id' => $adminRoleId, 'permission_id' => $p['id']])->countAllResults();
            if ($exists === 0) {
                $rpBuilder->insert([
                    'role_id'       => $adminRoleId,
                    'permission_id' => $p['id'],
                    'can_view'      => 1,
                    'can_add'       => 1,
                    'can_edit'      => 1,
                    'can_delete'    => 1,
                    'can_approve'   => 1,
                    'can_export'    => 1,
                ]);
            }
        }

        // Default admin user
        $uBuilder = $db->table('users');
        if ($uBuilder->where('email', 'admin@tpt.local')->countAllResults() === 0) {
            $uBuilder->insert([
                'role_id'       => $adminRoleId,
                'name'          => 'System Admin',
                'email'         => 'admin@tpt.local',
                'mobile'        => '9999999999',
                'password_hash' => password_hash('admin@123', PASSWORD_BCRYPT),
                'status'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // lead sources
        $sources = [
            ['source_key' => 'web',         'source_name' => 'Website',       'status' => 1],
            ['source_key' => 'whatsapp',    'source_name' => 'WhatsApp',      'status' => 1],
            ['source_key' => 'call',        'source_name' => 'Inbound Call',  'status' => 1],
            ['source_key' => 'referral',    'source_name' => 'Referral',      'status' => 1],
            ['source_key' => 'marketplace', 'source_name' => 'Marketplace',   'status' => 1],
            ['source_key' => 'social',      'source_name' => 'Social',        'status' => 1],
            ['source_key' => 'manual',      'source_name' => 'Manual Entry',  'status' => 1],
            ['source_key' => 'portal',      'source_name' => 'Client Portal', 'status' => 1],
        ];
        $lsBuilder = $db->table('lead_sources');
        foreach ($sources as $s) {
            if ($lsBuilder->where('source_key', $s['source_key'])->countAllResults() === 0) {
                $lsBuilder->insert($s);
            }
        }

        // settings
        $settings = [
            ['setting_key' => 'company_name',     'setting_value' => 'TPT Logistics', 'setting_group' => 'company'],
            ['setting_key' => 'company_gstin',    'setting_value' => '',              'setting_group' => 'company'],
            ['setting_key' => 'company_pan',      'setting_value' => '',              'setting_group' => 'company'],
            ['setting_key' => 'company_address',  'setting_value' => '',              'setting_group' => 'company'],
            ['setting_key' => 'company_state',    'setting_value' => '',              'setting_group' => 'company'],
            ['setting_key' => 'company_phone',    'setting_value' => '',              'setting_group' => 'company'],
            ['setting_key' => 'company_email',    'setting_value' => '',              'setting_group' => 'company'],
            ['setting_key' => 'default_gst_rate', 'setting_value' => '0',             'setting_group' => 'billing'],
            ['setting_key' => 'invoice_prefix',   'setting_value' => 'INV',           'setting_group' => 'numbering'],
            ['setting_key' => 'lead_prefix',      'setting_value' => 'LD',            'setting_group' => 'numbering'],
            ['setting_key' => 'rfq_prefix',       'setting_value' => 'RFQ',          'setting_group' => 'numbering'],
            ['setting_key' => 'booking_prefix',   'setting_value' => 'BK',            'setting_group' => 'numbering'],
            ['setting_key' => 'trip_prefix',      'setting_value' => 'TR',            'setting_group' => 'numbering'],
            ['setting_key' => 'lr_prefix',        'setting_value' => 'LR',            'setting_group' => 'numbering'],
        ];
        $sBuilder = $db->table('settings');
        foreach ($settings as $s) {
            if ($sBuilder->where('setting_key', $s['setting_key'])->countAllResults() === 0) {
                $s['updated_at'] = $now;
                $sBuilder->insert($s);
            }
        }

        // whatsapp templates (local drafts; must also be approved in Meta)
        $templates = [
            ['template_key' => 'rfq_vendor',         'audience_type' => 'vendor',   'template_name' => 'rfq_vendor',         'language_code' => 'en', 'body_text' => "RFQ {{1}}\nRoute: {{2}}\nVehicle: {{3}}\nMaterial: {{4}}\nWeight: {{5}}\nLoading: {{6}}\nReply with your best rate.", 'status' => 1],
            ['template_key' => 'quote_to_client',    'audience_type' => 'client',   'template_name' => 'quote_to_client',    'language_code' => 'en', 'body_text' => "Dear {{1}}, our quote for {{2}} route (Vehicle {{3}}) is INR {{4}}. Valid till {{5}}.", 'status' => 1],
            ['template_key' => 'vehicle_placed',     'audience_type' => 'client',   'template_name' => 'vehicle_placed',     'language_code' => 'en', 'body_text' => "Booking {{1}}: Vehicle {{2}} placed. Driver {{3}} ({{4}}).", 'status' => 1],
            ['template_key' => 'trip_in_transit',    'audience_type' => 'client',   'template_name' => 'trip_in_transit',    'language_code' => 'en', 'body_text' => "Trip {{1}} is in transit. Last location: {{2}}.", 'status' => 1],
            ['template_key' => 'trip_delivered',     'audience_type' => 'client',   'template_name' => 'trip_delivered',     'language_code' => 'en', 'body_text' => "Trip {{1}} delivered. Please share POD confirmation.", 'status' => 1],
            ['template_key' => 'pod_reminder',       'audience_type' => 'vendor',   'template_name' => 'pod_reminder',       'language_code' => 'en', 'body_text' => "Trip {{1}}: please send POD at the earliest.", 'status' => 1],
            ['template_key' => 'invoice_share',      'audience_type' => 'client',   'template_name' => 'invoice_share',      'language_code' => 'en', 'body_text' => "Invoice {{1}} for INR {{2}} is attached. Due: {{3}}.", 'status' => 1],
            ['template_key' => 'payment_reminder',   'audience_type' => 'client',   'template_name' => 'payment_reminder',   'language_code' => 'en', 'body_text' => "Friendly reminder: invoice {{1}} of INR {{2}} is due on {{3}}.", 'status' => 1],
        ];
        $tBuilder = $db->table('whatsapp_templates');
        foreach ($templates as $t) {
            if ($tBuilder->where('template_key', $t['template_key'])->countAllResults() === 0) {
                $t['created_at'] = $now;
                $t['updated_at'] = $now;
                $tBuilder->insert($t);
            }
        }
    }
}

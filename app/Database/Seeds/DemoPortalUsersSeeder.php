<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Enables the client portal on the first 3 demo clients (DemoDataSeeder must
 * have run first) and creates one ready-to-use portal-user account per client
 * so you can sign in to /portal/login as a client immediately.
 *
 * Default password for every demo portal user: `Portal@123`.
 */
class DemoPortalUsersSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Portal@123';

    public function run(): void
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Pull first 3 demo clients
        $clients = $db->table('clients')
            ->select('id, company_name, email')
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->limit(3)
            ->get()->getResultArray();

        if (empty($clients)) {
            // No DemoDataSeeder yet — nothing to seed against.
            return;
        }

        // Pick an account manager: prefer ops.mgr, fall back to admin
        $amId = (int) ($db->table('users')->where('email', 'ops.mgr@tpt.local')->get()->getRow('id') ?? 0);
        if (!$amId) {
            $amId = (int) ($db->table('users')->where('email', 'admin@tpt.local')->get()->getRow('id') ?? 0);
        }

        $cuBuilder = $db->table('client_users');
        $cBuilder  = $db->table('clients');

        $emails = ['portal1@example.com', 'portal2@example.com', 'portal3@example.com'];
        $names  = ['Pat Patel (Owner)', 'Riya Roy (Booker)', 'Vivek Verma (Accounts)'];
        $roles  = ['Owner', 'Booker', 'Accounts'];

        foreach ($clients as $i => $c) {
            // Enable portal + verify KYC + assign account manager
            $cBuilder->where('id', $c['id'])->update([
                'portal_enabled'          => 1,
                'kyc_status'              => 'Verified',
                'account_manager_user_id' => $amId ?: null,
                'updated_at'              => $now,
            ]);

            $email = $emails[$i] ?? ('portal' . ($i + 1) . '@example.com');
            $name  = $names[$i]  ?? 'Demo Portal User ' . ($i + 1);
            $role  = $roles[$i]  ?? 'Owner';

            // Idempotent: skip if a portal user with this email already exists
            if ($cuBuilder->where('email', $email)->countAllResults() > 0) {
                $cuBuilder->where('email', $email)->update([
                    'client_id'            => (int) $c['id'],
                    'name'                 => $name,
                    'portal_role'          => $role,
                    'status'               => 1,
                    'must_change_password' => 0,
                    'failed_attempts'      => 0,
                    'locked_until'         => null,
                    'updated_at'           => $now,
                ]);
                continue;
            }

            $cuBuilder->insert([
                'client_id'            => (int) $c['id'],
                'name'                 => $name,
                'email'                => $email,
                'mobile'               => '981000' . str_pad((string) ($i + 100), 4, '0', STR_PAD_LEFT),
                'password_hash'        => password_hash(self::DEFAULT_PASSWORD, PASSWORD_BCRYPT),
                'portal_role'          => $role,
                'status'               => 1,
                'must_change_password' => 0,
                'notify_whatsapp'      => 1,
                'notify_email'         => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }
    }
}

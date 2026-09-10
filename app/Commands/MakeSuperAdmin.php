<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;

/**
 * Promote an existing user to super-admin, OR create a new super-admin if --create is passed.
 * Super-admin is a flag — never settable through the web UI by design.
 *
 *   php spark tpt:make-super --email=ops@yngmedia.com
 *   php spark tpt:make-super --email=ops@yngmedia.com --create --name="Ops" --password=...
 */
class MakeSuperAdmin extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:make-super';
    protected $description = 'Promote a user to super-admin (or --create one). Super-admin is invisible to tenant admins and bypasses RBAC.';
    protected $usage       = 'php spark tpt:make-super --email=admin@example.com [--create --name="Name" --password=...]';

    public function run(array $params)
    {
        // Robust option parsing: CLI::getOption is unreliable across spark versions
        // on Windows, so also parse "--key=value" / "--flag" out of $params manually.
        $opts = self::parseOpts($params);
        $email   = (string) ($opts['email']    ?? CLI::getOption('email')    ?? $params[0] ?? '');
        $create  = isset($opts['create']) || (bool) (CLI::getOption('create') ?? false);
        $name    = (string) ($opts['name']     ?? CLI::getOption('name')     ?? 'Super Admin');
        $password= (string) ($opts['password'] ?? CLI::getOption('password') ?? '');

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            CLI::error('--email is required and must be a valid email address.');
            return;
        }

        $model = new UserModel();
        $user  = $model->where('email', $email)->first();

        if (!$user && !$create) {
            CLI::error("No user with that email. Pass --create --name=... --password=... to create one.");
            return;
        }

        if (!$user && $create) {
            if (strlen($password) < 8) {
                CLI::error('--password is required (min 8 chars) when --create is passed.');
                return;
            }
            $db = \Config\Database::connect();
            // Use the admin role if available, else any role, else create a stub
            $roleId = (int) ($db->table('roles')->where('role_key', 'admin')->get()->getRow('id') ?? 0);
            if (!$roleId) {
                $any = $db->table('roles')->orderBy('id', 'ASC')->get()->getRow();
                $roleId = $any ? (int) $any->id : 1;
            }
            $model->insert([
                'role_id'        => $roleId,
                'name'           => $name,
                'email'          => $email,
                'password_hash'  => password_hash($password, PASSWORD_BCRYPT),
                'status'         => 1,
                'is_super_admin' => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            CLI::write('Super-admin created: ' . $email, 'green');
            return;
        }

        // Promote existing
        $model->update($user['id'], ['is_super_admin' => 1]);
        CLI::write("Promoted to super-admin: {$email} (user #{$user['id']})", 'green');
    }

    /**
     * Parse --key=value tokens. CI4 stores them as ARRAY KEYS in $params
     * (e.g. ['email=foo@bar' => null, 'create' => null]) — so we walk keys.
     */
    public static function parseOpts(array $params): array
    {
        $out = [];
        foreach ($params as $key => $val) {
            if (!is_string($key)) continue;
            if (strpos($key, '=') !== false) {
                [$k, $v] = explode('=', $key, 2);
                $out[$k] = $v;
            } else {
                $out[$key] = $val ?? true;
            }
        }
        return $out;
    }
}

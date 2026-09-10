<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ListSuperAdmins extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:list-super';
    protected $description = 'List all users currently flagged as super-admin.';

    public function run(array $params)
    {
        $rows = \Config\Database::connect()
            ->table('users')
            ->select('id, name, email, status, last_login_at, totp_enabled')
            ->where('is_super_admin', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) {
            CLI::write('No super-admins yet. Use `php spark tpt:make-super --email=...` to create one.', 'yellow');
            return;
        }
        CLI::table([
            ['ID','Name','Email','Active','2FA','Last login'],
            ...array_map(fn ($r) => [
                $r['id'], $r['name'], $r['email'],
                $r['status'] ? 'yes' : 'no',
                $r['totp_enabled'] ? 'yes' : 'no',
                $r['last_login_at'] ?? '—',
            ], $rows),
        ]);
    }
}

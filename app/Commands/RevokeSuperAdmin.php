<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RevokeSuperAdmin extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:revoke-super';
    protected $description = 'Remove the super-admin flag from a user (does NOT delete the user).';
    protected $usage       = 'php spark tpt:revoke-super --email=admin@example.com';

    public function run(array $params)
    {
        $opts  = \App\Commands\MakeSuperAdmin::parseOpts($params);
        $email = strtolower(trim((string) ($opts['email'] ?? CLI::getOption('email') ?? $params[0] ?? '')));
        if ($email === '') {
            CLI::error('--email is required');
            return;
        }
        $db = \Config\Database::connect();
        $row = $db->table('users')->where('email', $email)->where('deleted_at', null)->get()->getRow();
        if (!$row) { CLI::error('No such user.'); return; }
        if ((int) $row->is_super_admin !== 1) {
            CLI::write('Already not a super-admin.', 'yellow');
            return;
        }
        $db->table('users')->where('id', $row->id)->update(['is_super_admin' => 0]);
        CLI::write('Super-admin flag removed for ' . $email, 'green');
    }
}

<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\PiiCrypto;

class PiiEncryptExisting extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:pii-encrypt';
    protected $description = 'One-shot: encrypt plaintext PII rows in employee_profiles (idempotent — skips already-encrypted values).';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $rows = $db->table('employee_profiles')->get()->getResultArray();
        $updated = 0;
        foreach ($rows as $r) {
            $upd = [];
            foreach (PiiCrypto::EMPLOYEE_PROFILE as $f) {
                $v = (string) ($r[$f] ?? '');
                if ($v !== '' && !str_starts_with($v, 'enc:')) {
                    $upd[$f] = PiiCrypto::encrypt($v);
                }
            }
            if ($upd) {
                $db->table('employee_profiles')->where('id', (int) $r['id'])->update($upd);
                $updated++;
            }
        }
        CLI::write("Encrypted PII on $updated row(s).", 'green');
    }
}

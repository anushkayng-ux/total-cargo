<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\EmailService;

class EmailFlush extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:email-flush';
    protected $description = 'Drain queued emails (Status=Queued, attempts<5) by sending via the configured driver.';

    public function run(array $params)
    {
        $limit = (int) ($params[0] ?? 50);
        if ($limit < 1) $limit = 50;
        if ($limit > 500) $limit = 500;

        $svc = new EmailService();
        $driver = $svc->driver();
        if (!$driver) {
            CLI::write('No email driver is configured. Open Settings → Email and set one.', 'yellow');
            return;
        }
        CLI::write('Using driver: ' . $driver->key(), 'cyan');

        $start = microtime(true);
        $r = $svc->flushQueue($limit);
        $elapsed = round(microtime(true) - $start, 2);

        CLI::write(sprintf(
            'Email flush done in %ss — scanned %d, sent %d, failed %d, reaped %d.',
            $elapsed, $r['scanned'], $r['sent'], $r['failed'], $r['reaped'] ?? 0
        ), $r['failed'] > 0 ? 'yellow' : 'green');
    }
}

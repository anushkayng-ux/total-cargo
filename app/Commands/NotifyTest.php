<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificationService;

class NotifyTest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:notify-test';
    protected $description = 'Smoke-test NotificationService events end-to-end. Usage: tpt:notify-test [rfq=<id>] [trip=<id>] [quote=<id>]';

    public function run(array $params)
    {
        $args = [];
        foreach ($params as $p) {
            if (strpos($p, '=') !== false) { [$k, $v] = explode('=', $p, 2); $args[$k] = $v; }
        }
        $rfqId   = (int) ($args['rfq']   ?? 1);
        $tripId  = (int) ($args['trip']  ?? 8);
        $quoteId = (int) ($args['quote'] ?? 0);

        $svc = new NotificationService();

        CLI::write("─── 1. onRfqDispatched(rfq=$rfqId) ───", 'cyan');
        $r = $svc->onRfqDispatched($rfqId);
        CLI::write('  ' . json_encode($r));

        if ($quoteId > 0) {
            CLI::write("─── 2. onQuoteSelected(rfq=$rfqId, quote=$quoteId) ───", 'cyan');
            $r = $svc->onQuoteSelected($rfqId, $quoteId);
            CLI::write('  ' . json_encode($r));
        }

        CLI::write("─── 3. onTripAssigned(trip=$tripId) ───", 'cyan');
        $r = $svc->onTripAssigned($tripId);
        CLI::write('  ' . json_encode($r));

        CLI::write("─── 4. onDriverDispatchStarted(trip=$tripId) ───", 'cyan');
        $r = $svc->onDriverDispatchStarted($tripId);
        CLI::write('  ' . json_encode($r));

        foreach (['In Transit','Arrived','Delivered'] as $s) {
            CLI::write("─── 5. onTripStatusChanged(trip=$tripId, '$s') ───", 'cyan');
            $r = $svc->onTripStatusChanged($tripId, $s);
            CLI::write('  ' . json_encode($r));
        }

        CLI::write("\n─── email_logs summary ───", 'yellow');
        $db = \Config\Database::connect();
        $rows = $db->query("SELECT id, template_key, to_email, status, LEFT(error, 60) AS err
                             FROM email_logs
                             ORDER BY id DESC LIMIT 15")->getResultArray();
        foreach ($rows as $r) {
            CLI::write(sprintf("  #%-3d %-26s %-32s %-9s %s",
                $r['id'], $r['template_key'] ?? '—', $r['to_email'], $r['status'], $r['err'] ?: ''));
        }
    }
}

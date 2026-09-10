<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\GpsRouter;

class GpsRefresh extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:gps-refresh';
    protected $description = 'Refresh GPS for all active trips via the configured source priority (LocoNav → FastTag). Skips trips with a driver-phone ping in the last 5 min.';

    public function run(array $params)
    {
        $start  = microtime(true);
        $r = (new GpsRouter())->refreshAllActive();
        $elapsed = round(microtime(true) - $start, 2);
        CLI::write(sprintf(
            'GPS refresh done in %ss — scanned %d, loconav %d, fasttag %d, driver-phone-skipped %d, no-fix %d.',
            $elapsed, $r['scanned'], $r['loconav'], $r['fasttag'], $r['skipped_phone'], $r['failed']
        ), 'green');
    }
}

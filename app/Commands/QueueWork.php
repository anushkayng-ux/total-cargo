<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Jobs;

/**
 * Long-running queue worker. Pulls jobs from a queue, runs each handler,
 * and stamps the outcome back to the `jobs` table.
 *
 * Usage:
 *   spark tpt:queue-work                 # default queue, never exits
 *   spark tpt:queue-work --queue=pdf     # specific queue
 *   spark tpt:queue-work --once          # process whatever's queued, then exit
 *   spark tpt:queue-work --max=100       # exit after N jobs (for systemd restarts)
 *
 * Recommended deployment:
 *   - Wrap in `systemd` (Linux) or NSSM (Windows) so a crash auto-restarts
 *   - Run `--max=100 --memory=128` so memory leaks don't pile up
 *   - Touch writable/cron-heartbeat at top of every loop for the /_health endpoint
 */
class QueueWork extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:queue-work';
    protected $description = 'Run the background job worker.';

    public function run(array $params)
    {
        $opts = $this->parseOpts($params);
        $queue = $opts['queue'] ?? 'default';
        $once  = !empty($opts['once']);
        $max   = isset($opts['max']) ? (int) $opts['max'] : 0;
        $maxMb = isset($opts['memory']) ? (int) $opts['memory'] : 128;

        $processed = 0;
        CLI::write("Worker started — queue=$queue once=" . ($once ? 'yes' : 'no') . " max=$max", 'cyan');
        while (true) {
            // Heartbeat for /_health
            @touch(WRITEPATH . 'cron-heartbeat');

            $r = Jobs::reserveAndRun($queue);
            if (!$r['picked']) {
                if ($once) { CLI::write('Queue empty — exiting.'); break; }
                sleep(2); // idle backoff
                continue;
            }
            $processed++;
            $status = $r['ok'] ? CLI::color('OK', 'green') : CLI::color('FAIL', 'red');
            CLI::write(sprintf('[%s] job %d %s%s',
                date('H:i:s'), $r['id'], $status,
                !$r['ok'] && $r['error'] ? ' — ' . $r['error'] : ''));

            // Memory + max-job guards (prevents leaks/long-lived state issues)
            $mb = round(memory_get_usage(true) / 1048576, 1);
            if ($maxMb && $mb >= $maxMb) {
                CLI::write("Memory $mb MB ≥ {$maxMb} MB — recycling worker.", 'yellow');
                break;
            }
            if ($max && $processed >= $max) {
                CLI::write("Processed $processed jobs — recycling.", 'yellow');
                break;
            }
        }
        CLI::write("Worker exited. Processed $processed jobs.", 'cyan');
    }

    private function parseOpts(array $params): array
    {
        $out = [];
        foreach ($params as $p) {
            if (str_starts_with($p, '--') && str_contains($p, '=')) {
                [$k, $v] = explode('=', substr($p, 2), 2);
                $out[$k] = $v;
            } elseif (str_starts_with($p, '--')) {
                $out[substr($p, 2)] = true;
            }
        }
        return $out;
    }
}

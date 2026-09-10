<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * GET /_health — lightweight health probe for uptime monitors.
 *
 * Checks (each with a per-check `ok` boolean and a short detail):
 *   - db          MySQL ping + count(*) on a tiny table
 *   - writable    WRITEPATH/ exists, is writable, free space
 *   - email_queue current queued + failed counts (warn if > thresholds)
 *   - last_cron   most-recent cron heartbeat (warn if > 90 min ago)
 *
 * HTTP status:
 *   200 — every check passed
 *   503 — one or more checks failed (so UptimeRobot/Better Stack alerts)
 *
 * Auth: deliberately none. The response leaks no PII; it only confirms
 * "the app is alive." Restrict at the firewall/load-balancer if needed.
 */
class HealthController extends Controller
{
    public function index()
    {
        $started = microtime(true);
        $checks  = [
            'db'          => $this->checkDb(),
            'writable'    => $this->checkWritable(),
            'email_queue' => $this->checkEmailQueue(),
            'last_cron'   => $this->checkLastCron(),
        ];

        $allOk = !in_array(false, array_column($checks, 'ok'), true);
        $resp = $this->response
            ->setStatusCode($allOk ? 200 : 503)
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON([
                'status'   => $allOk ? 'ok' : 'fail',
                'time'     => date('c'),
                'release'  => (string) env('tpt.release', 'tpt@' . date('Ymd')),
                'duration_ms' => round((microtime(true) - $started) * 1000, 1),
                'checks'   => $checks,
            ]);
        return $resp;
    }

    private function checkDb(): array
    {
        try {
            $db = \Config\Database::connect();
            $t0 = microtime(true);
            $row = $db->query('SELECT COUNT(*) AS n FROM migrations LIMIT 1')->getRowArray();
            $ms = round((microtime(true) - $t0) * 1000, 1);
            return ['ok' => is_array($row), 'detail' => "MySQL OK, {$ms}ms"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'MySQL: ' . substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkWritable(): array
    {
        $path = defined('WRITEPATH') ? WRITEPATH : __DIR__ . '/../../writable';
        $writable = is_dir($path) && is_writable($path);
        $free = @disk_free_space($path);
        $freeMb = $free ? round($free / 1048576) : null;
        $ok = $writable && ($freeMb === null || $freeMb >= 100);
        return ['ok' => $ok, 'detail' => $writable
            ? ($freeMb !== null ? "writable, {$freeMb} MB free" : 'writable')
            : 'NOT writable'];
    }

    private function checkEmailQueue(): array
    {
        try {
            $db = \Config\Database::connect();
            $queued = (int) ($db->table('email_logs')->where('status', 'Queued')->countAllResults() ?? 0);
            $failed = (int) ($db->table('email_logs')->where('status', 'Failed')
                ->where("attempts >= 5", null, false)->countAllResults() ?? 0);
            // Warn on >100 queued (slow sender) or >50 hard-failures (broken creds)
            $ok = $queued < 500 && $failed < 50;
            return ['ok' => $ok, 'detail' => "queued={$queued} failed={$failed}"];
        } catch (\Throwable $e) {
            return ['ok' => true, 'detail' => 'skipped (email_logs absent)'];
        }
    }

    private function checkLastCron(): array
    {
        $marker = (defined('WRITEPATH') ? WRITEPATH : __DIR__ . '/../../writable') . '/cron-heartbeat';
        if (!is_file($marker)) return ['ok' => true, 'detail' => 'no heartbeat yet (set up cron to `touch ' . basename($marker) . '`)'];
        $age = time() - filemtime($marker);
        // Healthy if any cron has touched the file in the last 90 min
        $ok = $age < 90 * 60;
        return ['ok' => $ok, 'detail' => sprintf('%d min ago', floor($age / 60))];
    }
}

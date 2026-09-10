<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Sentry;

/**
 * Fire one synthetic exception at Sentry to confirm the DSN is wired up.
 * Usage:
 *   php spark tpt:sentry-test
 *
 * Open sentry.io after running this — the event should appear within ~10s.
 * If nothing shows up:
 *   - Check sentry.dsn in .env is the project's "DSN" (not a public key alone)
 *   - Verify outbound https connectivity from this host
 *   - Check writable/logs/log-*.log for "Sentry forwarder failed" hints
 */
class SentryTest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:sentry-test';
    protected $description = 'Send a test exception to Sentry to verify the DSN is wired.';

    public function run(array $params)
    {
        $dsn = (string) env('sentry.dsn', '');
        if ($dsn === '') {
            CLI::error('sentry.dsn is empty in .env — nothing to test.');
            CLI::write('Paste your DSN from sentry.io → Settings → Client Keys (DSN) into .env first.');
            return 1;
        }
        CLI::write('Sending test event…', 'cyan');

        try {
            throw new \RuntimeException('TPT Sentry smoke test — ' . date('c') . ' from ' . gethostname());
        } catch (\Throwable $e) {
            Sentry::capture($e, ['test' => true, 'release' => env('tpt.release', '')]);
        }

        CLI::write('Sent. Check sentry.io within ~10s for the event.', 'green');
        return 0;
    }
}

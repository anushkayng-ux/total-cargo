<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\SettingModel;

class BrevoCheck extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:brevo-check';
    protected $description = 'Hit Brevo API with the decrypted key: account info, verified senders, recent events.';

    public function run(array $params)
    {
        $key = (string) (new SettingModel())->get('brevo_api_key', '');
        if ($key === '') { CLI::error('No Brevo API key configured.'); return; }
        CLI::write('Key length: ' . strlen($key) . ' chars · prefix: ' . substr($key, 0, 12) . '…', 'cyan');

        $get = function (string $path) use ($key): array {
            $ch = curl_init('https://api.brevo.com/v3' . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['accept: application/json', 'api-key: ' . $key],
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ['code' => $code, 'body' => json_decode((string) $body, true) ?: []];
        };

        CLI::write("\n1) /account", 'yellow');
        $r = $get('/account');
        if ($r['code'] === 200) {
            CLI::write('  ok — ' . ($r['body']['email'] ?? '?') . ' · plan: ' . json_encode($r['body']['plan'][0] ?? []));
        } else {
            CLI::write("  HTTP {$r['code']} — " . json_encode($r['body']), 'red');
        }

        CLI::write("\n2) /senders", 'yellow');
        $r = $get('/senders');
        if ($r['code'] === 200) {
            foreach (($r['body']['senders'] ?? []) as $s) {
                $active = !empty($s['active']) ? 'YES' : 'no';
                CLI::write(sprintf('  %-40s active=%s', $s['email'] ?? '-', $active));
            }
        } else {
            CLI::write("  HTTP {$r['code']} — " . json_encode($r['body']), 'red');
        }

        CLI::write("\n3) /smtp/statistics/events (last 15, sorted desc)", 'yellow');
        $r = $get('/smtp/statistics/events?limit=15&sort=desc');
        if ($r['code'] === 200) {
            $events = $r['body']['events'] ?? [];
            if (empty($events)) CLI::write('  (no events yet)');
            foreach ($events as $e) {
                CLI::write(sprintf('  %-10s %-30s from=%-22s to=%-25s reason=%s',
                    $e['event'] ?? '-',
                    $e['date'] ?? '-',
                    substr((string) ($e['from'] ?? '-'), 0, 22),
                    substr((string) ($e['email'] ?? '-'), 0, 25),
                    substr((string) ($e['reason'] ?? '-'), 0, 50)));
            }
        } else {
            CLI::write("  HTTP {$r['code']} — " . json_encode($r['body']), 'red');
        }
    }
}

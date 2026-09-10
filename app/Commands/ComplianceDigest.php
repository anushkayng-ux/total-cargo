<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ComplianceTracker;
use App\Libraries\EmailService;

/**
 * Weekly compliance digest. Mails every admin user (role_key='admin') a
 * summary of documents expiring in the next 30 days. Cron-friendly:
 * exits 0 quietly when there is nothing to report, so cron alerts only
 * trigger on real failures.
 *
 * Wire to cron (Windows Task Scheduler / Linux cron):
 *   0 9 * * 1  cd /path/to/tpt && php spark tpt:compliance-digest
 */
class ComplianceDigest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:compliance-digest';
    protected $description = 'Email admins the weekly document-expiry digest.';

    public function run(array $params)
    {
        $summary = (new ComplianceTracker())->summary(30);
        $rows    = $summary['rows'];
        if (empty($rows)) {
            CLI::write('No expiries in the next 30 days — skipping digest.', 'green');
            return;
        }

        $db = \Config\Database::connect();
        $admins = $db->table('users')
            ->select('users.email, users.name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('roles.role_key', 'admin')
            ->where('users.status', 1)
            ->where('users.deleted_at', null)
            ->where('users.email IS NOT NULL', null, false)
            ->get()->getResultArray();
        if (empty($admins)) {
            CLI::error('No active admin users with email — nothing to send.');
            return;
        }

        $subject = '[TPT] Compliance digest — ' . count($rows) . ' document' . (count($rows) === 1 ? '' : 's') . ' expiring soon';
        $html    = $this->renderHtml($summary);
        $text    = $this->renderText($summary);

        $svc = new EmailService();
        $ok  = 0; $fail = 0;
        foreach ($admins as $a) {
            $r = $svc->sendRaw($a['email'], $subject, $html, $text, [
                'recipient_name' => $a['name'] ?? null,
                'related_module' => 'compliance',
                'related_table'  => 'digest',
            ]);
            if (!empty($r['ok'])) $ok++; else $fail++;
        }
        CLI::write("Compliance digest: $ok sent · $fail failed · " . count($rows) . ' rows', $fail ? 'yellow' : 'green');
    }

    private function renderHtml(array $summary): string
    {
        $rows  = $summary['rows'];
        $count = $summary['counts'];
        $head  = sprintf(
            '<p style="font-family:Arial,sans-serif;font-size:14px;">Document expiry summary for the next 30 days: '
            . '<strong style="color:#dc2626;">%d expired</strong>, <strong style="color:#dc2626;">%d critical (≤7d)</strong>, '
            . '<strong style="color:#d97706;">%d warning (≤30d)</strong>.</p>',
            $count['expired'] ?? 0, $count['critical'] ?? 0, $count['warning'] ?? 0
        );
        $tableRows = '';
        foreach ($rows as $r) {
            $colour = $r['bucket'] === 'expired' || $r['bucket'] === 'critical' ? '#dc2626' : ($r['bucket'] === 'warning' ? '#d97706' : '#6b7280');
            $tableRows .= sprintf(
                '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;">%s</td><td style="padding:6px 10px;border-bottom:1px solid #eee;">%s</td><td style="padding:6px 10px;border-bottom:1px solid #eee;">%s</td><td style="padding:6px 10px;border-bottom:1px solid #eee;color:%s;font-weight:600;">%s</td></tr>',
                esc($r['kind'] === 'driver' ? 'Driver' : 'Vehicle'),
                esc($r['entity_label']) . ' · ' . esc($r['label']),
                esc(date('d M Y', strtotime($r['expiry']))),
                $colour,
                $r['days'] < 0 ? 'Expired ' . abs($r['days']) . 'd' : $r['days'] . 'd left'
            );
        }
        $link = site_url('compliance');
        return $head
            . '<table style="border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:13px;">'
            . '<thead><tr style="background:#f3f4f6;"><th align="left" style="padding:6px 10px;">Type</th><th align="left" style="padding:6px 10px;">Document</th><th align="left" style="padding:6px 10px;">Expiry</th><th align="left" style="padding:6px 10px;">Status</th></tr></thead>'
            . '<tbody>' . $tableRows . '</tbody></table>'
            . '<p style="font-family:Arial,sans-serif;font-size:13px;margin-top:18px;"><a href="' . esc($link) . '" style="color:#3730a3;">Open the compliance page →</a></p>';
    }

    private function renderText(array $summary): string
    {
        $out = "TPT Compliance digest — " . date('d M Y') . "\n\n";
        foreach ($summary['rows'] as $r) {
            $status = $r['days'] < 0 ? 'EXPIRED ' . abs($r['days']) . 'd ago' : $r['days'] . 'd left';
            $out .= sprintf("  - [%s] %s — %s — %s — %s\n",
                strtoupper($r['kind']),
                $r['entity_label'],
                $r['label'],
                date('d M Y', strtotime($r['expiry'])),
                $status
            );
        }
        return $out;
    }
}

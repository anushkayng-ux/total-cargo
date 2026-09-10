<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\EmailService;

class EmailTest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:email-test';
    protected $description = 'Queue a test email and report the email_logs status. Usage: tpt:email-test you@example.com [template_key]';

    public function run(array $params)
    {
        $to  = (string) ($params[0] ?? '');
        $tpl = (string) ($params[1] ?? '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Usage: php spark tpt:email-test you@example.com [template_key]');
            return;
        }

        $svc    = new EmailService();
        $driver = $svc->driver();
        CLI::write('Driver: ' . ($driver ? $driver->key() : '(none configured)'), 'cyan');

        $vars = [
            'company_name' => 'TPT Logistics',
            'name'         => 'Test User',
            'route'        => 'Mumbai → Chennai',
            'rate'         => '45,000',
            'invoice_no'   => 'INV-TEST-001',
            'booking_no'   => 'BK-TEST-001',
            'trip_no'      => 'TR-TEST-001',
            'due_date'     => date('d M Y', strtotime('+15 days')),
            'balance'      => '45,000',
            'total'        => '45,000',
        ];

        if ($tpl !== '') {
            CLI::write("Sending template '$tpl'  →  $to ...", 'yellow');
            $r = $svc->sendTemplate($tpl, $to, $vars);
        } else {
            CLI::write("Sending raw test email  →  $to ...", 'yellow');
            $r = $svc->sendRaw($to,
                'TPT email pipeline test',
                '<p>Hello — this is a smoke-test email from the TPT Aggregator app.</p>'
                . '<p>If you can read this, the email pipeline is wired correctly.</p>',
                'Hello — this is a smoke-test email from the TPT Aggregator app.'
            );
        }

        CLI::write('Result: ' . json_encode($r, JSON_PRETTY_PRINT), $r['ok'] ? 'green' : 'yellow');

        if (!empty($r['log_id'])) {
            $row = (new \App\Models\EmailLogModel())->find((int) $r['log_id']);
            CLI::write("\nemail_logs row $r[log_id]:", 'cyan');
            foreach (['template_key','to_email','subject','status','provider','provider_msg_id','error','attempts','queued_at','sent_at'] as $k) {
                CLI::write(sprintf('  %-18s %s', $k, $row[$k] ?? '—'));
            }
        }
    }
}

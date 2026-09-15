<?php

namespace App\Controllers;

use App\Models\SettingModel;

class SettingsController extends BaseController
{
    public function index()
    {
        $alertRules = \Config\Database::connect()
            ->table('alert_rules')
            ->orderBy('event_key', 'ASC')
            ->orderBy('audience', 'ASC')
            ->get()->getResultArray();
        return $this->render('settings/index', [
            'pageTitle'  => 'Settings [Administration]',
            'grouped'    => (new SettingModel())->getAllGrouped(),
            'alertRules' => $alertRules,
        ], retroFixedShell: true);
    }

    /** Save the alert-rules matrix (event × audience × channel → enabled). */
    public function saveAlerts()
    {
        $rules = $this->request->getPost('alert') ?? [];
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        // Treat as "every checkbox not present = disabled". For each known rule, set state.
        $all = $db->table('alert_rules')->get()->getResultArray();
        foreach ($all as $r) {
            $key     = $r['event_key'] . '|' . $r['audience'] . '|' . $r['channel'];
            $enabled = !empty($rules[$key]) ? 1 : 0;
            $db->table('alert_rules')->where('id', (int) $r['id'])
                ->update(['enabled' => $enabled, 'updated_at' => $now]);
        }
        return redirect()->to(site_url('settings') . '#alerts')->with('success', 'Alert rules saved.');
    }

    public function save()
    {
        $posted = $this->request->getPost('s') ?? [];
        $model  = new SettingModel();
        $userId = $this->auth->id();
        $changes = [];
        foreach ($posted as $group => $pairs) {
            if (!is_array($pairs)) continue;
            foreach ($pairs as $key => $value) {
                $old = $model->get((string) $key, null);
                if ((string) $old !== (string) $value) {
                    // Don't log secrets verbatim — keep a redacted shape
                    $isSecret = preg_match('/(api_key|secret|password|token)/i', (string) $key);
                    $changes[(string) $key] = $isSecret
                        ? ['old' => '«hidden»', 'new' => '«hidden»']
                        : ['old' => $old, 'new' => $value];
                }
                $model->put((string) $key, (string) $value, (string) $group, $userId);
            }
        }
        if (!empty($changes)) {
            \App\Libraries\AuditLogger::log('settings', null, 'updated',
                count($changes) . ' setting(s) changed',
                array_map(fn($c) => $c['old'], $changes),
                array_map(fn($c) => $c['new'], $changes));
        }
        return redirect()->to(site_url('settings'))->with('success', 'Settings saved.');
    }

    /** Upload a company logo (PNG/JPG/SVG). Stored under public/uploads/branding/. */
    public function uploadLogo()
    {
        $file = $this->request->getFile('logo');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Please choose a valid image file.');
        }
        $ext = strtolower($file->getExtension() ?: $file->getClientExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return redirect()->back()->with('error', 'Only PNG / JPG / WEBP files are allowed.');
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Logo must be 2 MB or smaller.');
        }
        // Verify that the bytes really are an image of the claimed type
        $info = @getimagesize($file->getTempName());
        if ($info === false) {
            return redirect()->back()->with('error', 'File is not a valid image.');
        }
        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array((string) $info['mime'], $allowedMimes, true)) {
            return redirect()->back()->with('error', 'File MIME does not match an allowed image type.');
        }

        $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'branding';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        // Random filename to bust caches; keep extension
        $name = 'logo-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move($dir, $name, true);

        // Persist relative web path
        (new SettingModel())->put('company_logo_path', 'uploads/branding/' . $name, 'company', $this->auth->id());

        return redirect()->to(site_url('settings'))->with('success', 'Logo updated.');
    }

    public function removeLogo()
    {
        $cur = (new SettingModel())->get('company_logo_path');
        if ($cur) {
            $abs = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, (string) $cur);
            if (is_file($abs)) @unlink($abs);
        }
        (new SettingModel())->put('company_logo_path', '', 'company', $this->auth->id());
        return redirect()->to(site_url('settings'))->with('success', 'Logo removed.');
    }

    /** Save the email settings card (driver + creds) without disturbing other groups. */
    public function saveEmail()
    {
        $allowed = [
            'email_driver', 'from_email', 'from_name',
            'brevo_api_key', 'brevo_webhook_secret',
            'ses_region', 'ses_access_key', 'ses_secret_key', 'ses_configuration_set',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_crypto',
        ];
        $model = new SettingModel();
        $userId = $this->auth->id();
        foreach ($allowed as $k) {
            $v = $this->request->getPost($k);
            if ($v === null) continue;
            $model->put($k, (string) $v, 'email', $userId);
        }
        return redirect()->to(site_url('settings'))->with('success', 'Email settings saved.');
    }

    /** Save the GPS Sources card (priority + FastTag credentials). */
    public function saveGpsSources()
    {
        $allowed = ['gps_priority', 'fasttag_provider', 'fasttag_endpoint', 'fasttag_api_key'];
        $model   = new SettingModel();
        $userId  = $this->auth->id();
        foreach ($allowed as $k) {
            $v = $this->request->getPost($k);
            if ($v === null) continue;
            $model->put($k, (string) $v, 'gps', $userId);
        }
        return redirect()->to(site_url('settings'))->with('success', 'GPS source settings saved.');
    }

    /** Toggle the CI4 debug toolbar on/off. Admins / super-admins only see it once on. */
    public function saveDebugToolbar()
    {
        $on = $this->request->getPost('debug_toolbar_enabled') ? '1' : '0';
        (new SettingModel())->put('debug_toolbar_enabled', $on, 'system', $this->auth->id());
        return redirect()->to(site_url('settings'))->with('success',
            $on === '1'
              ? 'Debug toolbar enabled. It will appear at the bottom of pages for super-admins and admins.'
              : 'Debug toolbar disabled.'
        );
    }

    /**
     * Send a test email to the supplied address. If a template_key is provided,
     * render that template with sample variables; otherwise send a plain test.
     */
    public function sendTestEmail()
    {
        $to       = trim((string) $this->request->getPost('test_to'));
        $tplKey   = trim((string) $this->request->getPost('template_key'));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to(site_url('settings'))->with('error', 'Please enter a valid test recipient email.');
        }
        $svc    = new \App\Libraries\EmailService();
        $driver = $svc->driver();
        $driverKey = $driver?->key() ?? 'none';

        $opts = ['sent_by_user_id' => $this->auth->id(), 'related_module' => 'settings'];

        if ($tplKey !== '') {
            // Sample variables that cover every template's placeholders
            $vars = [
                'company'           => 'Sample Co.',
                'company_name'      => 'TPT Logistics',
                'name'              => 'Test User',
                'client_name'       => 'Test User',
                'vendor_name'       => 'Sample Carrier Pvt Ltd',
                'driver_name'       => 'Test Driver',
                'driver_mobile'     => '9999999999',
                'contact_phone'     => '9810000000',
                'route'             => 'Mumbai → Chennai',
                'rfq_no'            => 'RFQ-TEST-001',
                'booking_no'        => 'BK-TEST-001',
                'trip_no'           => 'TR-TEST-001',
                'lr_no'             => 'LR-TEST-001',
                'invoice_no'        => 'INV-TEST-001',
                'vehicle_number'    => 'MH12AB1234',
                'vehicle_type'      => '32ft SXL',
                'material'          => 'General cargo',
                'weight'            => '10 TON',
                'loading_date'      => date('d M Y', strtotime('+2 days')),
                'loading_point'     => 'Bhiwandi warehouse',
                'rate'              => '45,000',
                'quote_amount'      => '45,000',
                'transit_days'      => '3',
                'remarks'           => 'Test remarks',
                'due_date'          => date('d M Y', strtotime('+15 days')),
                'balance'           => '45,000',
                'total'             => '45,000',
                'arrived_at'        => date('d M Y H:i'),
                'consignee_name'    => 'Test Consignee',
                'consignee_address' => 'Test address, Chennai',
                'consignee_mobile'  => '9999999999',
                'vendor_company'    => 'Sample Carrier Pvt Ltd',
                'rfq_link'          => site_url('rfq/1'),
                'quote_link'        => site_url('quote/sample-token'),
                'driver_link'       => site_url('d/sample-token'),
            ];
            $res = $svc->sendTemplate($tplKey, $to, $vars, $opts);
            $where = "template '$tplKey'";
        } else {
            $html = '<h2>Test email</h2>'
                  . '<p>This is a test email from <strong>' . esc(env('tpt.appName', 'TPT Aggregator')) . '</strong> using the <code>' . esc($driverKey) . '</code> driver at ' . date('Y-m-d H:i:s') . '.</p>'
                  . '<p>If you received this, your transactional email pipeline is working correctly.</p>';
            $res = $svc->sendRaw($to, 'Test email · ' . env('tpt.appName', 'TPT'), $html, null, $opts);
            $where = 'plain test';
        }

        if (!empty($res['ok'])) {
            $msg = "$where sent via $driverKey (log #" . ($res['log_id'] ?? '?') . ").";
            return redirect()->to(site_url('settings'))->with('success', $msg);
        }
        if (!empty($res['queued'])) {
            $msg = "$where queued (log #" . ($res['log_id'] ?? '?') . "). " . ($driver ? "Driver: $driverKey." : 'No driver configured — paste credentials and run tpt:email-flush.');
            return redirect()->to(site_url('settings'))->with('success', $msg);
        }
        return redirect()->to(site_url('settings'))->with('error', 'Test failed: ' . ($res['error'] ?? 'unknown'));
    }
}

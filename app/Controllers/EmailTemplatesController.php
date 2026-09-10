<?php

namespace App\Controllers;

use App\Models\EmailTemplateModel;
use App\Libraries\EmailService;

class EmailTemplatesController extends BaseController
{
    public function index()
    {
        $rows = (new EmailTemplateModel())->orderBy('audience_type')->orderBy('template_key')->find();
        return $this->render('email_templates/index', [
            'pageTitle' => 'Email Templates',
            'rows'      => $rows,
        ]);
    }

    public function create()
    {
        return $this->render('email_templates/form', [
            'pageTitle' => 'New Email Template',
            'row'       => null,
        ]);
    }

    public function store()
    {
        $model = new EmailTemplateModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $data['template_key'] = preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $data['template_key']));
        $data['status']       = !empty($data['status']) ? 1 : 0;
        if ((new EmailTemplateModel())->where('template_key', $data['template_key'])->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'A template with that key already exists.');
        }
        $model->insert($data);
        return redirect()->to(site_url('email-templates'))->with('success', 'Template created.');
    }

    public function edit(int $id)
    {
        $row = (new EmailTemplateModel())->find($id);
        if (!$row) return redirect()->to(site_url('email-templates'))->with('error', 'Not found.');
        return $this->render('email_templates/form', [
            'pageTitle' => 'Edit ' . $row['template_key'],
            'row'       => $row,
        ]);
    }

    public function update(int $id)
    {
        $model = new EmailTemplateModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $data['template_key'] = preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $data['template_key']));
        $data['status']       = !empty($data['status']) ? 1 : 0;
        $model->update($id, $data);
        return redirect()->to(site_url('email-templates'))->with('success', 'Template updated.');
    }

    public function delete(int $id)
    {
        (new EmailTemplateModel())->delete($id);
        return redirect()->to(site_url('email-templates'))->with('success', 'Template removed.');
    }

    /** Render-preview the template body with sample variables. */
    public function preview(int $id)
    {
        $row = (new EmailTemplateModel())->find($id);
        if (!$row) return redirect()->to(site_url('email-templates'))->with('error', 'Not found.');

        $sample = [];
        if (!empty($row['variables_json'])) {
            $vars = json_decode($row['variables_json'], true);
            if (is_array($vars)) {
                foreach ($vars as $i => $v) {
                    $sample[(int) $i] = is_string($v) ? $v : ('Sample ' . ($i + 1));
                }
            }
        }
        $svc = new EmailService();
        $subject = $svc->interpolate((string) $row['subject'],   $sample, 'text');
        $body    = $svc->interpolate((string) $row['body_html'], $sample, 'html');

        helper('branding');
        $logoUrl = function_exists('tpt_logo_url') ? tpt_logo_url() : null;
        $appName = (string) ((new \App\Models\SettingModel())->get('company_name', 'TPT Logistics') ?: 'TPT Logistics');
        $companyAddr = (string) ((new \App\Models\SettingModel())->get('company_address', '') ?: '');

        $html = view('email/_layout', [
            'logoUrl'    => $logoUrl,
            'appName'    => $appName,
            'companyAddr'=> $companyAddr,
            'content'    => $body,
            'unsubUrl'   => '#',
            'viewUrl'    => '#',
            'toEmail'    => 'preview@example.com',
        ]);

        // Sandbox the rendered email in a srcdoc iframe so any residual scripts
        // cannot touch the staff app cookie. CSP also forbids inline scripts.
        $shell = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src https: data:; style-src \'unsafe-inline\';">'
            . '<title>Preview</title>'
            . '<style>body{margin:0;font-family:system-ui;}.bar{background:#666;padding:6px 12px;color:#fff;font-size:.85rem;}iframe{width:100%;height:calc(100vh - 32px);border:0;background:#f4f4f4;}</style>'
            . '</head><body>'
            . '<div class="bar">PREVIEW · ' . esc($row['template_key']) . ' · Subject: ' . esc($subject) . '</div>'
            . '<iframe sandbox="allow-same-origin" srcdoc="' . htmlspecialchars($html, ENT_QUOTES, 'UTF-8') . '"></iframe>'
            . '</body></html>';

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($shell);
    }
}

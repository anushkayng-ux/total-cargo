<?php

namespace App\Controllers;

use App\Models\EmailEventModel;
use App\Models\EmailLogModel;
use App\Models\EmailUnsubscribeModel;
use App\Libraries\EmailService;

class EmailLogsController extends BaseController
{
    public function index()
    {
        $req    = $this->request;
        $status = (string) $req->getGet('status');
        $search = trim((string) $req->getGet('q'));
        $tpl    = (string) $req->getGet('template');

        $model = new EmailLogModel();
        $q = $model->orderBy('id', 'DESC');
        if ($status !== '') $q->where('status', $status);
        if ($tpl !== '')    $q->where('template_key', $tpl);
        if ($search !== '') {
            $q->groupStart()
                ->like('to_email', $search)
                ->orLike('subject', $search)
                ->orLike('provider_msg_id', $search)
                ->groupEnd();
        }

        return $this->render('email_logs/index', [
            'pageTitle' => 'Email Logs',
            'rows'      => $q->paginate(40),
            'pager'     => $model->pager,
            'status'    => $status,
            'search'    => $search,
            'tpl'       => $tpl,
            'unsubs'    => (new EmailUnsubscribeModel())->orderBy('id', 'DESC')->limit(20)->find(),
        ]);
    }

    public function show(int $id)
    {
        $log = (new EmailLogModel())->find($id);
        if (!$log) return redirect()->to(site_url('email-logs'))->with('error', 'Not found.');
        // Strip the open-tracking pixel + unwrap click-tracking links so this
        // admin preview doesn't trigger false-positive open / click events.
        if (!empty($log['body_html'])) {
            $log['body_html'] = EmailService::sanitizeForPreview((string) $log['body_html']);
        }
        $events = (new EmailEventModel())->forLog($id);
        return $this->render('email_logs/show', [
            'pageTitle' => 'Email Log #' . $id,
            'row'       => $log,
            'events'    => $events,
        ]);
    }

    public function retry(int $id)
    {
        $svc = new EmailService();
        $res = $svc->dispatch($id);
        if (!empty($res['ok'])) {
            return redirect()->to(site_url('email-logs/' . $id))->with('success', 'Retry succeeded.');
        }
        return redirect()->to(site_url('email-logs/' . $id))->with('error', 'Retry failed: ' . ($res['error'] ?? 'unknown'));
    }

    public function unsubscribeAdd()
    {
        $email = trim((string) $this->request->getPost('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            (new EmailUnsubscribeModel())->add($email, 'manual', 'added by staff');
        }
        return redirect()->to(site_url('email-logs'))->with('success', 'Suppression added.');
    }

    public function unsubscribeRemove(int $id)
    {
        $row = (new EmailUnsubscribeModel())->find($id);
        if ($row) (new EmailUnsubscribeModel())->delete($id);
        return redirect()->to(site_url('email-logs'))->with('success', 'Suppression removed.');
    }
}

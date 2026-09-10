<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\EmailEventModel;
use App\Models\EmailLogModel;
use App\Models\EmailUnsubscribeModel;

/**
 * Public no-auth handlers for open pixel, click wrapper, unsubscribe link,
 * and "view in browser" fallback. All keyed by tracking_token in URL.
 */
class EmailTrackController extends Controller
{
    private EmailLogModel $logs;
    private EmailEventModel $events;
    protected $helpers = ['url'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->logs   = new EmailLogModel();
        $this->events = new EmailEventModel();
    }

    /** 1×1 transparent GIF + record open. */
    public function open(string $token)
    {
        $log = $this->logs->findByToken($token);
        if ($log) {
            $now = date('Y-m-d H:i:s');
            $update = [
                'open_count'    => (int) $log['open_count'] + 1,
                'last_event_at' => $now,
            ];
            // Always record opened_at if not yet set, but only PROMOTE the status
            // when current state is Sent or Delivered. Never downgrade Bounced /
            // Complained / Clicked back to Opened.
            if (empty($log['opened_at'])) {
                $update['opened_at'] = $now;
            }
            if (in_array($log['status'], ['Sent','Delivered'], true)) {
                $update['status'] = 'Opened';
            }
            $this->logs->update($log['id'], $update);
            $this->events->record((int) $log['id'], 'open');
        }

        // 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return $this->response
            ->setHeader('Content-Type', 'image/gif')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($gif);
    }

    /** Click-tracker: record + 302 to original URL. */
    public function click(string $token)
    {
        $u = (string) $this->request->getGet('u');
        if ($u === '') return redirect()->to(base_url());
        $b64 = strtr($u, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) $b64 .= str_repeat('=', 4 - $pad);
        $target = base64_decode($b64, true);
        if ($target === false || !preg_match('#^https?://#i', $target)) {
            return redirect()->to(base_url());
        }

        $log = $this->logs->findByToken($token);
        if ($log) {
            $now = date('Y-m-d H:i:s');
            $update = [
                'click_count'   => (int) $log['click_count'] + 1,
                'last_event_at' => $now,
            ];
            if (empty($log['first_clicked_at'])) {
                $update['first_clicked_at'] = $now;
            }
            // Only PROMOTE to Clicked from Sent/Delivered/Opened. Never downgrade
            // Bounced or Complained.
            if (in_array($log['status'], ['Sent','Delivered','Opened'], true)) {
                $update['status'] = 'Clicked';
            }
            $this->logs->update($log['id'], $update);
            $this->events->record((int) $log['id'], 'click', ['url' => $target]);
        }
        return redirect()->to($target);
    }

    /** GET shows confirmation page; POST processes one-click unsub.
     *  Requires ?sig=<hmac> parameter — prevents drive-by unsubscribe via stolen token. */
    public function unsubscribe(string $token)
    {
        $log = $this->logs->findByToken($token);
        if (!$log) return $this->respondText('Invalid or expired link.');

        $sig         = (string) $this->request->getGet('sig');
        $expectedSig = \App\Libraries\EmailService::unsubSig($token);
        if ($sig === '' || !hash_equals($expectedSig, $sig)) {
            return $this->respondText('This link is invalid. Please contact support if you wish to unsubscribe.');
        }

        $email = strtolower(trim($log['to_email']));

        if (strtoupper($this->request->getMethod()) === 'POST') {
            (new EmailUnsubscribeModel())->add($email, 'link', 'one-click unsubscribe');
            $this->logs->update($log['id'], [
                'unsubscribed_at' => date('Y-m-d H:i:s'),
                'last_event_at'   => date('Y-m-d H:i:s'),
            ]);
            $this->events->record((int) $log['id'], 'unsubscribe');
            return $this->respondText('You have been unsubscribed. We will not send further emails to ' . esc($email) . '.');
        }

        return view('email/unsubscribe', [
            'token' => $token,
            'email' => $email,
            'sig'   => $sig,
        ]);
    }

    /** "View in browser" fallback — renders the stored body inside a sandboxed shell. */
    public function webView(string $token)
    {
        $log = $this->logs->findByToken($token);
        if (!$log) return $this->respondText('Email not found.');

        // Wrap stored body in a srcdoc iframe so any residual scripts/HTML in
        // user-supplied template variables cannot touch this origin's cookies.
        $body = (string) ($log['body_html'] ?? '');
        $shell = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="robots" content="noindex">'
            . '<title>Email</title>'
            . '<style>html,body{margin:0;height:100%;background:#f4f4f4;}iframe{width:100%;height:100%;border:0;background:#fff;}</style>'
            . '</head><body>'
            . '<iframe sandbox="allow-same-origin" srcdoc="' . htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . '"></iframe>'
            . '</body></html>';

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setHeader('Content-Security-Policy', "default-src 'none'; img-src https: data:; style-src 'unsafe-inline'; frame-src 'self'")
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody($shell);
    }

    private function respondText(string $msg): ResponseInterface
    {
        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody('<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;padding:2rem;text-align:center;color:#333;">'
                . esc($msg) . '</body>');
    }
}

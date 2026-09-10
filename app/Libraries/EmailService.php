<?php

namespace App\Libraries;

use App\Libraries\Email\BrevoDriver;
use App\Libraries\Email\SesDriver;
use App\Libraries\Email\SmtpDriver;
use App\Libraries\Email\DriverInterface;
use App\Models\EmailEventModel;
use App\Models\EmailLogModel;
use App\Models\EmailTemplateModel;
use App\Models\EmailUnsubscribeModel;
use App\Models\SettingModel;

/**
 * Multi-driver, queue-safe transactional email service.
 *
 * Pipeline:
 *   1. Render template with variables → subject + html (+ text)
 *   2. Wrap in base layout (logo / footer / unsubscribe)
 *   3. Insert tracking pixel + rewrite outbound links through click-tracker
 *   4. Suppression check
 *   5. Insert email_logs row (status=Queued)
 *   6. Send via configured driver; update log to Sent/Failed
 *   7. Increment attempts; retry on next flush if Failed and attempts<5
 */
class EmailService
{
    public const DEFAULT_FROM_NAME = 'TPT Logistics';

    /**
     * Returns the persistent HMAC secret used to sign unsubscribe links.
     * Auto-generated on first call, stored in settings.
     */
    public static function trackingSecret(): string
    {
        $s = new \App\Models\SettingModel();
        $secret = (string) ($s->get('email_track_secret', '') ?? '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
            $s->put('email_track_secret', $secret, 'email');
        }
        return $secret;
    }

    /** HMAC signature for one-click unsubscribe URLs. */
    public static function unsubSig(string $token): string
    {
        return substr(hash_hmac('sha256', 'unsub:' . $token, self::trackingSecret()), 0, 32);
    }

    /** Send a transactional email by template_key with substitution variables. */
    public function sendTemplate(
        string $templateKey,
        string $toEmail,
        array  $vars = [],
        array  $opts = []
    ): array {
        $tpl = (new EmailTemplateModel())->findByKey($templateKey);
        if (!$tpl) {
            return $this->logFailure(null, $toEmail, $opts, "template '$templateKey' not found");
        }

        // Subjects render in mail clients as plain text; body_html escapes; body_text doesn't.
        $subject = $this->interpolate((string) $tpl['subject'],   $vars, 'text');
        $html    = $this->interpolate((string) $tpl['body_html'], $vars, 'html');
        $text    = !empty($tpl['body_text']) ? $this->interpolate((string) $tpl['body_text'], $vars, 'text') : null;

        return $this->sendRaw($toEmail, $subject, $html, $text, $opts + ['template_key' => $templateKey]);
    }

    /** Send a fully-formed email (no template lookup). */
    public function sendRaw(string $toEmail, string $subject, string $html, ?string $text = null, array $opts = []): array
    {
        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->logFailure($opts['template_key'] ?? null, $toEmail, $opts, 'invalid email address');
        }

        // DEV MAIL-TRAP: if settings.test_email_override is set, reroute every
        // outgoing email there. Keeps the original recipient visible in the
        // subject + an in-body banner so testers can see who would have got it.
        $override = trim((string) ((new \App\Models\SettingModel())->get('test_email_override', '') ?? ''));
        if ($override !== '' && filter_var($override, FILTER_VALIDATE_EMAIL) && strcasecmp($override, $toEmail) !== 0) {
            $origTo  = $toEmail;
            $origCc  = $opts['cc']  ?? null;
            $origBcc = $opts['bcc'] ?? null;
            $toEmail = $override;
            $subject = '[→ ' . $origTo . '] ' . $subject;
            unset($opts['cc'], $opts['bcc']);
            $banner = '<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;margin:0 0 16px;font-family:Arial,sans-serif;font-size:13px;color:#92400e;">'
                    . '<strong>TEST MODE.</strong> Original recipient: <code style="background:#fff;padding:1px 4px;border-radius:3px;">' . htmlspecialchars($origTo, ENT_QUOTES) . '</code>'
                    . ($origCc  ? '<br>Original CC: <code style="background:#fff;padding:1px 4px;border-radius:3px;">' . htmlspecialchars(is_array($origCc)  ? implode(',', $origCc)  : (string) $origCc,  ENT_QUOTES) . '</code>' : '')
                    . ($origBcc ? '<br>Original BCC: <code style="background:#fff;padding:1px 4px;border-radius:3px;">' . htmlspecialchars(is_array($origBcc) ? implode(',', $origBcc) : (string) $origBcc, ENT_QUOTES) . '</code>' : '')
                    . '</div>';
            $html = $banner . $html;
            if ($text !== null) {
                $text = "[TEST MODE — original recipient: $origTo]\n\n" . $text;
            }
        }

        // Suppression check (unless explicitly bypassed for transactional like password reset)
        $bypassSuppression = !empty($opts['bypass_suppression']);
        if (!$bypassSuppression && (new EmailUnsubscribeModel())->isSuppressed($toEmail)) {
            return $this->logSuppressed($opts['template_key'] ?? null, $toEmail, $subject, $opts);
        }

        $token = bin2hex(random_bytes(16));

        // Wrap in branded layout, insert tracking pixel + rewrite links
        $wrappedHtml = $this->wrapInLayout($html, $token, $toEmail, $opts);
        $wrappedHtml = $this->rewriteLinks($wrappedHtml, $token);
        $wrappedHtml = $this->injectPixel($wrappedHtml, $token);

        $textBody = $text ?: $this->htmlToText($html);

        // Insert log row first (status=Queued)
        $logModel = new EmailLogModel();
        $logId = $logModel->insert([
            'tracking_token'    => $token,
            'template_key'      => $opts['template_key'] ?? null,
            'to_email'          => $toEmail,
            'to_name'           => $opts['to_name'] ?? null,
            'cc'                => !empty($opts['cc'])  ? implode(',', (array) $opts['cc'])  : null,
            'bcc'               => !empty($opts['bcc']) ? implode(',', (array) $opts['bcc']) : null,
            'reply_to'          => $opts['reply_to'] ?? null,
            'subject'           => $subject,
            'body_html'         => $wrappedHtml,
            'body_text'         => $textBody,
            'attachments_json'  => !empty($opts['attachments']) ? json_encode($opts['attachments']) : null,
            'status'            => 'Queued',
            'attempts'          => 0,
            'queued_at'         => date('Y-m-d H:i:s'),
            'related_module'    => $opts['related_module'] ?? null,
            'related_id'        => $opts['related_id'] ?? null,
            'related_client_id' => $opts['related_client_id'] ?? null,
            'sent_by_user_id'   => $opts['sent_by_user_id'] ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
        ], true);

        if (empty($logId)) {
            return ['ok' => false, 'error' => 'Failed to log email'];
        }

        // Try to send immediately
        $sendResult = $this->dispatch((int) $logId);
        $sendResult['log_id'] = (int) $logId;
        return $sendResult;
    }

    /** Take one queued log row and send via the configured driver. */
    public function dispatch(int $logId): array
    {
        $logModel = new EmailLogModel();
        $log = $logModel->find($logId);
        if (!$log) return ['ok' => false, 'error' => 'log not found'];
        if (in_array($log['status'], EmailLogModel::TERMINAL, true)) {
            return ['ok' => true, 'note' => 'already terminal'];
        }

        $driver = $this->driver();
        if (!$driver) {
            $logModel->update($logId, [
                'status'   => 'Queued',
                'attempts' => (int) $log['attempts'] + 1,
                'error'    => 'No driver configured — message will retry on next flush',
            ]);
            return ['ok' => false, 'queued' => true, 'error' => 'No driver configured'];
        }

        // Claim this row atomically — if it was already grabbed by another worker, skip.
        $workerId = 'inline.' . bin2hex(random_bytes(4));
        $db = \Config\Database::connect();
        $db->query(
            "UPDATE email_logs
                SET status='Sending', worker_id=?, worker_locked_at=?, attempts = attempts + 1, provider = ?
              WHERE id = ? AND status IN ('Queued')",
            [$workerId, date('Y-m-d H:i:s'), $driver->key(), $logId]
        );
        if ($db->affectedRows() === 0) {
            // Already claimed by another process or already terminal
            $current = $logModel->find($logId);
            return [
                'ok'   => in_array(($current['status'] ?? ''), ['Sent','Delivered','Opened','Clicked'], true),
                'note' => 'concurrent claim',
            ];
        }
        // Refresh row after claim
        $log = $logModel->find($logId);

        $cred = (new SettingModel())->getAllGrouped();
        $fromEmail = $cred['email']['from_email'] ?? 'no-reply@example.com';
        $fromName  = $cred['email']['from_name']  ?? self::DEFAULT_FROM_NAME;

        $atts = [];
        if (!empty($log['attachments_json'])) {
            $decoded = json_decode($log['attachments_json'], true);
            if (is_array($decoded)) $atts = $decoded;
        }

        $result = $driver->send([
            'from_email'  => $fromEmail,
            'from_name'   => $fromName,
            'to_email'    => $log['to_email'],
            'to_name'     => $log['to_name'] ?? '',
            'subject'     => $log['subject'],
            'html'        => $log['body_html'],
            'text'        => $log['body_text'],
            'reply_to'    => $log['reply_to'],
            'cc'          => !empty($log['cc'])  ? array_filter(array_map('trim', explode(',', $log['cc']))) : [],
            'bcc'         => !empty($log['bcc']) ? array_filter(array_map('trim', explode(',', $log['bcc']))) : [],
            'attachments' => $atts,
            'headers'     => [
                'List-Unsubscribe'      => '<' . site_url('email-track/unsub/' . $log['tracking_token']) . '?sig=' . self::unsubSig($log['tracking_token']) . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                // Custom header lets us correlate ESP webhooks even when the
                // SMTP relay rewrites Message-ID (e.g. Gmail / Postfix).
                'X-TPT-Log-Id'          => (string) $log['id'],
                'X-TPT-Token'           => (string) $log['tracking_token'],
            ],
        ]);

        $now = date('Y-m-d H:i:s');
        if (!empty($result['ok'])) {
            $logModel->update($logId, [
                'status'          => 'Sent',
                'provider_msg_id' => $result['message_id'] ?? null,
                'sent_at'         => $now,
                'last_event_at'   => $now,
                'error'           => null,
                'worker_id'       => null,
                'worker_locked_at'=> null,
            ]);
            (new EmailEventModel())->record($logId, 'sent', ['provider' => $driver->key(), 'message_id' => $result['message_id'] ?? null]);
            return ['ok' => true, 'message_id' => $result['message_id'] ?? null];
        }

        // Failure — leave Queued for retry if attempts < 5; otherwise Failed
        $finalStatus = (int) $log['attempts'] >= 5 ? 'Failed' : 'Queued';
        $logModel->update($logId, [
            'status'           => $finalStatus,
            'error'            => substr((string) ($result['error'] ?? 'unknown'), 0, 500),
            'last_event_at'    => $now,
            'worker_id'        => null,
            'worker_locked_at' => null,
        ]);
        (new EmailEventModel())->record($logId, 'send_error', ['error' => $result['error'] ?? null]);
        return ['ok' => false, 'error' => $result['error'] ?? 'send failed'];
    }

    /** Drain N queued rows; called by spark tpt:email-flush. */
    public function flushQueue(int $limit = 50): array
    {
        $logModel = new EmailLogModel();
        // Reap rows stuck in 'Sending' for >5min (worker died mid-dispatch)
        $reaped = $logModel->reapStuck(300);

        $driver = $this->driver();
        if (!$driver) {
            return ['scanned' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'reaped' => $reaped, 'error' => 'no driver'];
        }

        $workerId = gethostname() . '.' . getmypid() . '.' . bin2hex(random_bytes(3));
        $rows = $logModel->claimBatch($workerId, $limit);

        $sent = 0; $failed = 0;
        foreach ($rows as $r) {
            // Re-fetch to get the freshly-claimed row state
            $log = $logModel->find($r['id']);
            if (!$log || $log['status'] !== 'Sending') continue;

            // Compose the message payload from the stored row
            $cred = (new \App\Models\SettingModel())->getAllGrouped();
            $fromEmail = $cred['email']['from_email'] ?? 'no-reply@example.com';
            $fromName  = $cred['email']['from_name']  ?? self::DEFAULT_FROM_NAME;
            $atts = !empty($log['attachments_json']) ? (json_decode($log['attachments_json'], true) ?: []) : [];

            $result = $driver->send([
                'from_email'  => $fromEmail,
                'from_name'   => $fromName,
                'to_email'    => $log['to_email'],
                'to_name'     => $log['to_name'] ?? '',
                'subject'     => $log['subject'],
                'html'        => $log['body_html'],
                'text'        => $log['body_text'],
                'reply_to'    => $log['reply_to'],
                'cc'          => !empty($log['cc'])  ? array_filter(array_map('trim', explode(',', $log['cc']))) : [],
                'bcc'         => !empty($log['bcc']) ? array_filter(array_map('trim', explode(',', $log['bcc']))) : [],
                'attachments' => $atts,
                'headers'     => [
                    'List-Unsubscribe'      => '<' . site_url('email-track/unsub/' . $log['tracking_token']) . '?sig=' . self::unsubSig($log['tracking_token']) . '>',
                    'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                    'X-TPT-Log-Id'          => (string) $log['id'],
                    'X-TPT-Token'           => (string) $log['tracking_token'],
                ],
            ]);

            $now = date('Y-m-d H:i:s');
            if (!empty($result['ok'])) {
                $logModel->update($log['id'], [
                    'status'           => 'Sent',
                    'provider'         => $driver->key(),
                    'provider_msg_id'  => $result['message_id'] ?? null,
                    'sent_at'          => $now,
                    'last_event_at'    => $now,
                    'error'            => null,
                    'worker_id'        => null,
                    'worker_locked_at' => null,
                ]);
                (new \App\Models\EmailEventModel())->record((int) $log['id'], 'sent', [
                    'provider' => $driver->key(),
                    'message_id' => $result['message_id'] ?? null,
                ]);
                $sent++;
            } else {
                $logModel->update($log['id'], [
                    'status'           => (int) $log['attempts'] >= 5 ? 'Failed' : 'Queued',
                    'error'            => substr((string) ($result['error'] ?? 'unknown'), 0, 500),
                    'last_event_at'    => $now,
                    'worker_id'        => null,
                    'worker_locked_at' => null,
                ]);
                (new \App\Models\EmailEventModel())->record((int) $log['id'], 'send_error', [
                    'error' => $result['error'] ?? null,
                ]);
                $failed++;
            }
        }
        return ['scanned' => count($rows), 'sent' => $sent, 'failed' => $failed, 'skipped' => 0, 'reaped' => $reaped];
    }

    /** Returns the active driver (based on settings.email.driver) or null if not configured. */
    public function driver(): ?DriverInterface
    {
        $setting = new SettingModel();
        $key = (string) ($setting->get('email_driver', 'smtp') ?: 'smtp');
        $d = $this->driverByKey($key);
        if ($d && $d->isConfigured()) return $d;
        // Fallback: pick the first configured driver
        foreach (['brevo','ses','smtp'] as $k) {
            $alt = $this->driverByKey($k);
            if ($alt && $alt->isConfigured()) return $alt;
        }
        return null;
    }

    public function driverByKey(string $key): ?DriverInterface
    {
        return match ($key) {
            'brevo' => new BrevoDriver(),
            'ses'   => new SesDriver(),
            'smtp'  => new SmtpDriver(),
            default => null,
        };
    }

    // ────────────── helpers ──────────────

    /**
     * Replace {{var}}, {{1}}, {{client.name}} (limited dot path).
     * $escape='html' (default) escapes for HTML body; 'text' for plain text;
     * 'raw' for trusted internal use (e.g. building URL inside hrefs we control).
     * Caller passes escape per render-context. Templates render with 'html' for
     * subject/body_html and 'text' for body_text.
     */
    public function interpolate(string $tpl, array $vars, string $escape = 'html'): string
    {
        return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function ($m) use ($vars, $escape) {
            $key = $m[1];
            $val = '';
            if (ctype_digit($key)) {
                $idx = (int) $key - 1;
                if (isset($vars[$idx])) $val = (string) $vars[$idx];
            } elseif (array_key_exists($key, $vars)) {
                $val = (string) $vars[$key];
            } elseif (strpos($key, '.') !== false) {
                $parts = explode('.', $key);
                $cur = $vars;
                foreach ($parts as $p) {
                    if (is_array($cur) && array_key_exists($p, $cur)) $cur = $cur[$p];
                    else { $cur = ''; break; }
                }
                if (is_scalar($cur)) $val = (string) $cur;
            }
            return match ($escape) {
                'html' => htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
                'text' => $val,
                'raw'  => $val,
                default => htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
            };
        }, $tpl) ?? $tpl;
    }

    private function wrapInLayout(string $contentHtml, string $token, string $toEmail, array $opts): string
    {
        helper('branding');
        $logoUrl    = function_exists('tpt_logo_url') ? tpt_logo_url() : null;
        $appName    = (string) ((new SettingModel())->get('company_name', 'TPT Logistics') ?: 'TPT Logistics');
        $companyAddr= (string) ((new SettingModel())->get('company_address', '') ?: '');
        $unsubUrl   = site_url('email-track/unsub/' . $token) . '?sig=' . self::unsubSig($token);
        $viewUrl    = site_url('email-track/web/'   . $token);

        return view('email/_layout', [
            'logoUrl'    => $logoUrl,
            'appName'    => $appName,
            'companyAddr'=> $companyAddr,
            'content'    => $contentHtml,
            'unsubUrl'   => $unsubUrl,
            'viewUrl'    => $viewUrl,
            'toEmail'    => $toEmail,
        ]);
    }

    /** Rewrite all <a href="http..."> to /email-track/click/{token}?u=base64 */
    private function rewriteLinks(string $html, string $token): string
    {
        $base = site_url('email-track/click/' . $token);
        return preg_replace_callback('/<a\s+([^>]*?)href\s*=\s*([\'"])(https?:\/\/[^\'"]+)\2/i', function ($m) use ($base) {
            $before = $m[1];
            $quote  = $m[2];
            $url    = $m[3];
            // Skip our own tracking + unsubscribe links
            if (strpos($url, '/email-track/') !== false) {
                return '<a ' . $before . 'href=' . $quote . $url . $quote;
            }
            $wrapped = $base . '?u=' . rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
            return '<a ' . $before . 'href=' . $quote . $wrapped . $quote;
        }, $html) ?? $html;
    }

    private function injectPixel(string $html, string $token): string
    {
        $px = '<img src="' . site_url('email-track/open/' . $token) . '" width="1" height="1" alt="" style="display:block;border:0;outline:none;text-decoration:none;">';
        if (stripos($html, '</body>') !== false) {
            return preg_replace('#</body>#i', $px . '</body>', $html, 1);
        }
        return $html . $px;
    }

    /**
     * Make a body_html safe to render in the admin preview: strip the open-
     * tracking pixel and unwrap click-tracking links to their original URLs,
     * so staff browsing the log doesn't trigger false open/click events.
     */
    public static function sanitizeForPreview(string $html): string
    {
        if ($html === '') return $html;

        // 1) Strip any <img ...> whose src points at /email-track/open/...
        $html = preg_replace(
            '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*\/email-track\/open\/[^"\']*["\'][^>]*>#i',
            '',
            (string) $html
        ) ?? $html;

        // 2) Unwrap click-tracking links: replace the wrapped href with the
        //    decoded original URL so clicks in the preview go to the real
        //    destination (no click event recorded).
        $html = preg_replace_callback(
            '#href\s*=\s*(["\'])([^"\']*\/email-track\/click\/[^"\']*?\?u=([^"\'&]+)[^"\']*)\1#i',
            static function ($m) {
                $b64 = strtr($m[3], '-_', '+/');
                $pad = (4 - strlen($b64) % 4) % 4;
                $b64 .= str_repeat('=', $pad);
                $decoded = base64_decode($b64, true);
                if ($decoded === false) return $m[0];
                return 'href=' . $m[1] . htmlspecialchars($decoded, ENT_QUOTES) . $m[1];
            },
            (string) $html
        ) ?? $html;

        // 3) Force every <a> to open in a new tab. The iframe is sandboxed so
        //    same-tab navigation is blocked; `target="_blank"` + popups lets
        //    the admin still inspect link destinations.
        $html = preg_replace_callback(
            '#<a\b([^>]*)>#i',
            static function ($m) {
                $attrs = $m[1];
                // Skip anchor links (e.g. <a href="#foo">)
                if (preg_match('/href\s*=\s*(["\'])#/i', $attrs)) return $m[0];
                // Strip any existing target / rel
                $attrs = preg_replace('/\btarget\s*=\s*(["\'])[^"\']*\1/i', '', $attrs);
                $attrs = preg_replace('/\brel\s*=\s*(["\'])[^"\']*\1/i', '', $attrs);
                return '<a' . rtrim($attrs) . ' target="_blank" rel="noopener noreferrer">';
            },
            (string) $html
        ) ?? $html;

        return (string) $html;
    }

    private function htmlToText(string $html): string
    {
        $t = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);
        $t = preg_replace('#<script[^>]*>.*?</script>#is', '', $t);
        $t = preg_replace('#<br\s*/?>#i', "\n", $t);
        $t = preg_replace('#</p>|</div>|</li>|</tr>|</h[1-6]>#i', "\n", $t);
        $t = strip_tags((string) $t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\n{3,}/', "\n\n", $t));
    }

    private function logFailure(?string $tpl, string $to, array $opts, string $err): array
    {
        (new EmailLogModel())->insert([
            'tracking_token'    => bin2hex(random_bytes(16)),
            'template_key'      => $tpl,
            'to_email'          => $to,
            'to_name'           => $opts['to_name'] ?? null,
            'subject'           => $opts['subject'] ?? '(no subject)',
            'status'            => 'Failed',
            'error'             => substr($err, 0, 500),
            'attempts'          => 0,
            'queued_at'         => date('Y-m-d H:i:s'),
            'related_module'    => $opts['related_module'] ?? null,
            'related_id'        => $opts['related_id'] ?? null,
            'related_client_id' => $opts['related_client_id'] ?? null,
            'sent_by_user_id'   => $opts['sent_by_user_id'] ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => false, 'error' => $err];
    }

    private function logSuppressed(?string $tpl, string $to, string $subject, array $opts): array
    {
        (new EmailLogModel())->insert([
            'tracking_token'    => bin2hex(random_bytes(16)),
            'template_key'      => $tpl,
            'to_email'          => $to,
            'to_name'           => $opts['to_name'] ?? null,
            'subject'           => $subject,
            'status'            => 'Suppressed',
            'error'             => 'Recipient is on suppression list',
            'attempts'          => 0,
            'queued_at'         => date('Y-m-d H:i:s'),
            'related_module'    => $opts['related_module'] ?? null,
            'related_id'        => $opts['related_id'] ?? null,
            'related_client_id' => $opts['related_client_id'] ?? null,
            'sent_by_user_id'   => $opts['sent_by_user_id'] ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => false, 'suppressed' => true, 'error' => 'recipient suppressed'];
    }
}

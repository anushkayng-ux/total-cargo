<?php

namespace App\Libraries\Email;

use App\Models\SettingModel;

/**
 * Generic SMTP driver — works with Google Workspace SMTP, Brevo SMTP relay,
 * SES SMTP, Office 365, or any vanilla SMTP server. Uses CodeIgniter's
 * built-in Email service so we don't pull in an extra dependency.
 *
 * Settings (group=email):
 *   smtp_host      — e.g. smtp.gmail.com
 *   smtp_port      — 587 (TLS) or 465 (SSL)
 *   smtp_user
 *   smtp_pass
 *   smtp_crypto    — tls (default) | ssl | (empty)
 */
class SmtpDriver implements DriverInterface
{
    public function key(): string { return 'smtp'; }

    private function setting(string $k, string $default = ''): string
    {
        return (string) ((new SettingModel())->get($k, $default) ?? $default);
    }

    public function isConfigured(): bool
    {
        return $this->setting('smtp_host') !== '' && $this->setting('smtp_user') !== '';
    }

    public function send(array $m): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'SMTP not configured'];
        }

        $email = service('email', null, false);
        $email->initialize([
            'protocol'    => 'smtp',
            'SMTPHost'    => $this->setting('smtp_host'),
            'SMTPUser'    => $this->setting('smtp_user'),
            'SMTPPass'    => $this->setting('smtp_pass'),
            'SMTPPort'    => (int) ($this->setting('smtp_port', '587') ?: 587),
            'SMTPCrypto'  => $this->setting('smtp_crypto', 'tls') ?: '',
            'mailType'    => 'html',
            'charset'     => 'UTF-8',
            'wordWrap'    => true,
            'newline'     => "\r\n",
            'CRLF'        => "\r\n",
        ]);

        $email->setFrom($m['from_email'], $m['from_name'] ?? '');
        $email->setTo($m['to_email']);
        if (!empty($m['cc']))       $email->setCC(implode(',', (array) $m['cc']));
        if (!empty($m['bcc']))      $email->setBCC(implode(',', (array) $m['bcc']));
        if (!empty($m['reply_to'])) $email->setReplyTo($m['reply_to']);
        $email->setSubject((string) $m['subject']);
        $email->setMessage((string) ($m['html'] ?? ''));
        if (!empty($m['text'])) $email->setAltMessage((string) $m['text']);

        if (!empty($m['headers']) && is_array($m['headers'])) {
            foreach ($m['headers'] as $h => $v) $email->setHeader((string) $h, (string) $v);
        }
        if (!empty($m['attachments'])) {
            foreach ((array) $m['attachments'] as $a) {
                if (!empty($a['path']) && is_file($a['path'])) {
                    $email->attach($a['path'], 'attachment', $a['name'] ?? null);
                }
            }
        }

        $ok = $email->send(false);
        if ($ok) {
            // CI4's Email lib doesn't expose a Message-Id, but we can sniff one from the printed debugger if needed.
            // Use a synthetic id so we can correlate retries; ESPs (gmail/o365) don't always echo Message-ID via SMTP cleanly.
            $messageId = 'smtp.' . bin2hex(random_bytes(8)) . '@' . parse_url(base_url(), PHP_URL_HOST);
            return ['ok' => true, 'message_id' => $messageId];
        }
        return ['ok' => false, 'error' => 'SMTP send failed: ' . substr((string) $email->printDebugger(['headers']), 0, 400)];
    }
}

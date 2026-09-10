<?php

namespace App\Libraries\Email;

use App\Models\SettingModel;

/**
 * Brevo (formerly Sendinblue) transactional email driver.
 * Docs: https://developers.brevo.com/reference/sendtransacemail
 *
 * Settings consumed (group=email):
 *   brevo_api_key — xkeysib-XXXX
 */
class BrevoDriver implements DriverInterface
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function key(): string { return 'brevo'; }

    private function apiKey(): string
    {
        return (string) ((new SettingModel())->get('brevo_api_key', '') ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function send(array $m): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Brevo API key not configured'];
        }

        // Brevo rejects empty `name` on sender/to/reply-to. Fall back to the
        // local-part of the email address so the payload always validates.
        $nameOrLocal = static function (string $email, ?string $name): string {
            $name = trim((string) $name);
            if ($name !== '') return $name;
            $local = strstr($email, '@', true);
            return $local !== false && $local !== '' ? $local : 'Recipient';
        };
        $fromEmail = (string) $m['from_email'];
        $toEmail   = (string) $m['to_email'];

        $payload = [
            'sender'      => ['email' => $fromEmail, 'name' => $nameOrLocal($fromEmail, $m['from_name'] ?? null)],
            'to'          => [['email' => $toEmail, 'name' => $nameOrLocal($toEmail, $m['to_name'] ?? null)]],
            'subject'     => (string) $m['subject'],
            'htmlContent' => (string) ($m['html'] ?? ''),
        ];
        if (!empty($m['text']))       $payload['textContent'] = (string) $m['text'];
        if (!empty($m['reply_to']))   $payload['replyTo'] = ['email' => $m['reply_to'], 'name' => $nameOrLocal($m['reply_to'], null)];
        if (!empty($m['cc']))         $payload['cc']  = array_map(fn ($e) => ['email' => $e, 'name' => $nameOrLocal($e, null)], (array) $m['cc']);
        if (!empty($m['bcc']))        $payload['bcc'] = array_map(fn ($e) => ['email' => $e, 'name' => $nameOrLocal($e, null)], (array) $m['bcc']);
        if (!empty($m['headers']))    $payload['headers'] = (array) $m['headers'];

        if (!empty($m['attachments'])) {
            $atts = [];
            foreach ((array) $m['attachments'] as $a) {
                if (empty($a['path']) || !is_file($a['path'])) continue;
                $atts[] = [
                    'name'    => $a['name'] ?? basename($a['path']),
                    'content' => base64_encode((string) file_get_contents($a['path'])),
                ];
            }
            if ($atts) $payload['attachment'] = $atts;
        }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->apiKey(),
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            return ['ok' => false, 'error' => 'cURL error: ' . $err];
        }
        $body = json_decode((string) $res, true);

        if ($code >= 200 && $code < 300 && !empty($body['messageId'])) {
            return ['ok' => true, 'message_id' => (string) $body['messageId']];
        }
        $emsg = is_array($body) ? ($body['message'] ?? $body['code'] ?? json_encode($body)) : substr((string) $res, 0, 400);
        return ['ok' => false, 'error' => "Brevo HTTP $code: " . $emsg];
    }
}

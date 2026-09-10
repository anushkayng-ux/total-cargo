<?php

namespace App\Libraries\Email;

use App\Models\SettingModel;

/**
 * Amazon SES v2 driver, signed with AWS Signature V4 (no SDK dependency).
 * Endpoint:   https://email.{region}.amazonaws.com/v2/email/outbound-emails
 *
 * Settings (group=email):
 *   ses_region        — e.g. us-east-1
 *   ses_access_key    — AKIA...
 *   ses_secret_key    — secret access key
 *   ses_configuration_set (optional) — applies engagement / event publishing
 */
class SesDriver implements DriverInterface
{
    public function key(): string { return 'ses'; }

    private function setting(string $k, string $default = ''): string
    {
        return (string) ((new SettingModel())->get($k, $default) ?? $default);
    }

    private function region(): string  { return $this->setting('ses_region', 'us-east-1') ?: 'us-east-1'; }
    private function accessKey(): string { return $this->setting('ses_access_key'); }
    private function secretKey(): string { return $this->setting('ses_secret_key'); }
    private function configurationSet(): string { return $this->setting('ses_configuration_set'); }

    public function isConfigured(): bool
    {
        return $this->accessKey() !== '' && $this->secretKey() !== '';
    }

    public function send(array $m): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'SES credentials not configured'];
        }

        $body = [
            'FromEmailAddress' => $this->formatFrom($m),
            'Destination'      => array_filter([
                'ToAddresses'  => [$m['to_email']],
                'CcAddresses'  => !empty($m['cc'])  ? array_values((array) $m['cc'])  : null,
                'BccAddresses' => !empty($m['bcc']) ? array_values((array) $m['bcc']) : null,
            ]),
            'Content' => [
                'Simple' => [
                    'Subject' => ['Data' => (string) $m['subject'], 'Charset' => 'UTF-8'],
                    'Body'    => array_filter([
                        'Html' => !empty($m['html']) ? ['Data' => (string) $m['html'], 'Charset' => 'UTF-8'] : null,
                        'Text' => !empty($m['text']) ? ['Data' => (string) $m['text'], 'Charset' => 'UTF-8'] : null,
                    ]),
                ],
            ],
        ];
        if (!empty($m['reply_to']))            $body['ReplyToAddresses']   = [(string) $m['reply_to']];
        if ($this->configurationSet() !== '')  $body['ConfigurationSetName'] = $this->configurationSet();

        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $host    = 'email.' . $this->region() . '.amazonaws.com';
        $path    = '/v2/email/outbound-emails';

        $signed = $this->sign('POST', $host, $path, '', $payload);

        $ch = curl_init('https://' . $host . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $signed,
            CURLOPT_POSTFIELDS     => $payload,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) return ['ok' => false, 'error' => 'cURL error: ' . $err];
        $b = json_decode((string) $res, true);
        if ($code >= 200 && $code < 300 && !empty($b['MessageId'])) {
            return ['ok' => true, 'message_id' => (string) $b['MessageId']];
        }
        $emsg = is_array($b) ? ($b['message'] ?? $b['Message'] ?? json_encode($b)) : substr((string) $res, 0, 400);
        return ['ok' => false, 'error' => "SES HTTP $code: " . $emsg];
    }

    private function formatFrom(array $m): string
    {
        $email = (string) $m['from_email'];
        $name  = (string) ($m['from_name'] ?? '');
        if ($name === '') return $email;
        // RFC2822: Name <email>
        return sprintf('"%s" <%s>', addcslashes($name, '"\\'), $email);
    }

    /**
     * AWS SigV4 signing for SES v2 JSON requests.
     * Returns array of HTTP headers including Authorization.
     */
    private function sign(string $method, string $host, string $path, string $query, string $payload): array
    {
        $service   = 'ses';
        $region    = $this->region();
        $algo      = 'AWS4-HMAC-SHA256';
        $amzDate   = gmdate('Ymd\THis\Z');
        $shortDate = gmdate('Ymd');
        $payloadHash = hash('sha256', $payload);

        $canonicalHeaders = "content-type:application/json\nhost:" . $host . "\nx-amz-content-sha256:" . $payloadHash . "\nx-amz-date:" . $amzDate . "\n";
        $signedHeaders    = 'content-type;host;x-amz-content-sha256;x-amz-date';

        $canonicalRequest = implode("\n", [
            $method, $path, $query,
            $canonicalHeaders, $signedHeaders, $payloadHash,
        ]);

        $credentialScope = "$shortDate/$region/$service/aws4_request";
        $stringToSign    = "$algo\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

        $kDate    = hash_hmac('sha256', $shortDate, 'AWS4' . $this->secretKey(), true);
        $kRegion  = hash_hmac('sha256', $region,    $kDate,   true);
        $kService = hash_hmac('sha256', $service,   $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $algo, $this->accessKey(), $credentialScope, $signedHeaders, $signature
        );

        // libcurl supplies Host: from the URL — sending it twice with different
        // casing causes some PHP/libcurl combos to silently produce a 403.
        return [
            'Content-Type: application/json',
            'X-Amz-Content-Sha256: ' . $payloadHash,
            'X-Amz-Date: ' . $amzDate,
            'Authorization: ' . $authorization,
        ];
    }
}

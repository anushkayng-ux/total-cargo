<?php

namespace App\Libraries;

use App\Models\WhatsappLogModel;
use App\Models\WhatsappTemplateModel;

/**
 * Meta WhatsApp Cloud API adapter.
 *
 * If access token is empty, messages are queued (logged as "Queued") and returned
 * without hitting the network — lets us build/test without live credentials.
 */
class WhatsAppService
{
    private string $graphBase;
    private string $apiVersion;
    private string $phoneNumberId;
    private string $accessToken;
    private WhatsappLogModel $logs;

    public function __construct()
    {
        $this->graphBase     = (string) env('whatsapp.graphBase', 'https://graph.facebook.com');
        $this->apiVersion    = (string) env('whatsapp.apiVersion', 'v21.0');
        $this->phoneNumberId = (string) env('whatsapp.phoneNumberId', '');
        $this->accessToken   = (string) env('whatsapp.accessToken', '');
        $this->logs          = new WhatsappLogModel();
    }

    public function isConfigured(): bool
    {
        return $this->accessToken !== '' && $this->phoneNumberId !== '';
    }

    /** Normalize Indian mobile numbers to E.164 (91XXXXXXXXXX) for WA. */
    public static function normalizeNumber(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') return '';
        if (strlen($digits) === 10) return '91' . $digits;           // 10-digit Indian
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) return '91' . substr($digits, 1);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) return $digits;
        return $digits;
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $variables,
        string $languageCode = 'en',
        array $context = []
    ): array {
        $to = self::normalizeNumber($to);

        $components = [];
        if (!empty($variables)) {
            $params = [];
            foreach ($variables as $v) {
                $params[] = ['type' => 'text', 'text' => (string) $v];
            }
            $components[] = ['type' => 'body', 'parameters' => $params];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->dispatch($to, $payload, array_merge($context, [
            'template_key' => $context['template_key'] ?? $templateName,
            'message_type' => 'template',
        ]));
    }

    public function sendText(string $to, string $body, array $context = []): array
    {
        $to = self::normalizeNumber($to);
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $body, 'preview_url' => false],
        ];
        return $this->dispatch($to, $payload, array_merge($context, ['message_type' => 'text']));
    }

    public function sendMediaUrl(string $to, string $mediaType, string $url, ?string $caption = null, array $context = []): array
    {
        $to = self::normalizeNumber($to);
        $body = [$mediaType => array_filter([
            'link'    => $url,
            'caption' => $caption,
        ])];
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => $mediaType,
        ] + $body;
        return $this->dispatch($to, $payload, array_merge($context, ['message_type' => $mediaType]));
    }

    /**
     * Render a local template row's body with {{N}} variables for preview / 24-hour-window text sends.
     */
    public function renderLocalTemplate(string $templateKey, array $variables): ?string
    {
        $t = (new WhatsappTemplateModel())->getByKey($templateKey);
        if (!$t) return null;
        $body = (string) $t['body_text'];
        foreach ($variables as $i => $v) {
            $body = str_replace('{{' . ($i + 1) . '}}', (string) $v, $body);
        }
        return $body;
    }

    public function downloadMedia(string $mediaId): ?string
    {
        if (!$this->isConfigured()) return null;

        $meta = $this->curl('GET', $this->graphBase . '/' . $this->apiVersion . '/' . $mediaId, null, [
            self::authHeader($this->accessToken),
        ]);
        if (empty($meta['ok']) || empty($meta['body']['url'])) return null;

        $bin = $this->curl('GET', $meta['body']['url'], null, [
            self::authHeader($this->accessToken),
        ], /*raw*/ true);

        return !empty($bin['ok']) ? (string) $bin['raw'] : null;
    }

    private function dispatch(string $to, array $payload, array $context): array
    {
        $logId = (int) $this->logs->insert([
            'module_name'     => $context['module_name'] ?? null,
            'module_ref_id'   => $context['module_ref_id'] ?? null,
            'audience_type'   => $context['audience_type'] ?? null,
            'recipient_no'    => $to,
            'template_key'    => $context['template_key'] ?? null,
            'message_type'    => $context['message_type'] ?? 'text',
            'delivery_status' => 'Queued',
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'retry_count'     => 0,
            'sent_at'         => null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        if (!$this->isConfigured()) {
            return [
                'ok'        => true,
                'queued'    => true,
                'log_id'    => $logId,
                'message_id'=> null,
                'note'      => 'WhatsApp credentials not configured — message queued for later delivery.',
            ];
        }

        $endpoint = $this->graphBase . '/' . $this->apiVersion . '/' . $this->phoneNumberId . '/messages';
        $result   = $this->curl('POST', $endpoint, $payload, [
            self::authHeader($this->accessToken),
            'Content-Type: application/json',
        ]);

        $msgId = $result['body']['messages'][0]['id'] ?? null;

        // Always log the full curl context — HTTP code, curl error, parsed
        // body, raw response. Lets us diagnose any send failure without having
        // to re-run with logging hacks. Truncate raw to keep the column sane.
        $diag = [
            'endpoint'    => $endpoint,
            'http_status' => $result['status'] ?? null,
            'curl_error'  => $result['error'] ?? null,
            'body'        => $result['body'] ?? null,
            'raw'         => is_string($result['raw'] ?? null) ? mb_substr((string) $result['raw'], 0, 4000) : null,
        ];
        $upd = ['response_payload' => json_encode($diag, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)];

        if (!empty($result['ok']) && $msgId) {
            $upd['provider_message_id'] = $msgId;
            $upd['delivery_status']     = 'Sent';
            $upd['sent_at']             = date('Y-m-d H:i:s');
        } else {
            $upd['delivery_status'] = 'Failed';
            $upd['failed_at']       = date('Y-m-d H:i:s');
            // Build a human-readable error string — HTTP code prefixes the
            // most-likely-useful detail so the log list view tells the story.
            $detail = $result['body']['error']['message']
                ?? $result['body']['message']
                ?? $result['body']['error']
                ?? ($result['error'] !== '' ? $result['error'] : null)
                ?? (is_string($result['raw'] ?? null) ? mb_substr((string) $result['raw'], 0, 200) : null)
                ?? 'No response from server';
            $code = $result['status'] ?? 0;
            $upd['error_message'] = substr('[HTTP ' . $code . '] ' . (is_string($detail) ? $detail : json_encode($detail)), 0, 250);
        }
        $this->logs->update($logId, $upd);

        return [
            'ok'         => !empty($result['ok']) && $msgId !== null,
            'queued'     => false,
            'log_id'     => $logId,
            'message_id' => $msgId,
            'raw'        => $result['body'] ?? null,
            'error'      => $upd['error_message'] ?? null,
        ];
    }

    private function curl(string $method, string $url, ?array $body, array $headers, bool $raw = false): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false, // XAMPP local often lacks CA bundle; prod should set cacert
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($raw) {
            return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'raw' => $response, 'error' => $err];
        }
        $decoded = null;
        if (is_string($response) && $response !== '') {
            $decoded = json_decode($response, true);
        }
        return [
            'ok'     => $status >= 200 && $status < 300 && !empty($decoded),
            'status' => $status,
            'body'   => $decoded,
            'raw'    => $response,
            'error'  => $err,
        ];
    }

    /**
     * Builds the request's Authorization header in the format the provider
     * expects. Default = Meta-style "Bearer <token>". Override via .env:
     *   whatsapp.authFormat = 'bearer'    →  Authorization: Bearer <key>
     *   whatsapp.authFormat = 'apikey'    →  Authorization: ApiKey <key>
     *   whatsapp.authFormat = 'token'     →  Authorization: Token <key>
     *   whatsapp.authFormat = 'raw'       →  Authorization: <key>
     *   whatsapp.authFormat = 'x-api-key' →  X-API-Key: <key>
     *   whatsapp.authFormat = 'api-key'   →  api-key: <key>
     *   whatsapp.authFormat = 'apikey-q'  →  Authorization: ApiKey=<key>
     */
    public static function authHeader(string $token, ?string $format = null): string
    {
        $format = strtolower((string) ($format ?? env('whatsapp.authFormat', 'bearer')));
        return match ($format) {
            'apikey'    => 'Authorization: ApiKey ' . $token,
            'token'     => 'Authorization: Token ' . $token,
            'raw'       => 'Authorization: ' . $token,
            'x-api-key' => 'X-API-Key: ' . $token,
            'api-key'   => 'api-key: ' . $token,
            'apikey-q'  => 'Authorization: ApiKey=' . $token,
            default     => 'Authorization: Bearer ' . $token,
        };
    }
}

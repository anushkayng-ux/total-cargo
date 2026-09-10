<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\WhatsappIncomingMessageModel;
use App\Models\WhatsappLogModel;
use App\Models\RfqModel;
use App\Models\RfqVendorModel;
use App\Models\QuotationModel;
use App\Models\VendorModel;

class WebhookController extends Controller
{
    /**
     * GET /webhooks/whatsapp  — Meta verification handshake
     */
    public function whatsappVerify()
    {
        $mode      = $this->request->getGet('hub_mode');
        $token     = $this->request->getGet('hub_verify_token');
        $challenge = $this->request->getGet('hub_challenge');
        $expected  = (string) env('whatsapp.verifyToken', '');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, (string) $token)) {
            return $this->response->setStatusCode(200)->setBody((string) $challenge);
        }
        return $this->response->setStatusCode(403)->setBody('forbidden');
    }

    /**
     * POST /webhooks/whatsapp — events (messages, status, etc.)
     */
    public function whatsappReceive()
    {
        $raw  = (string) $this->request->getBody();

        // Spy: write every inbound POST to a dedicated file BEFORE any parsing,
        // signature check, or 4xx rejection. This way we can diagnose new BSPs
        // whose payload format differs from Meta's — the bytes are captured
        // even if our parser ignores them. Best-effort; never blocks delivery.
        try {
            $dir = WRITEPATH . 'logs';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $headers = [];
            foreach ($this->request->headers() as $name => $h) {
                $headers[$name] = is_object($h) ? $h->getValueLine() : (string) $h;
            }
            $line = json_encode([
                'ts'      => date('Y-m-d H:i:s'),
                'ip'      => $this->request->getIPAddress(),
                'method'  => $this->request->getMethod(),
                'headers' => $headers,
                'body'    => mb_substr($raw, 0, 8000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            @file_put_contents($dir . DIRECTORY_SEPARATOR . 'webhook-spy.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Don't let logging failures break webhook acceptance.
        }

        // Verify Meta's HMAC-SHA256 signature using whatsapp.appSecret. Reject if invalid.
        // If appSecret is not set we still accept (dev mode) but log a warning.
        $appSecret = (string) env('whatsapp.appSecret', '');
        if ($appSecret !== '') {
            $sig = (string) $this->request->getHeaderLine('X-Hub-Signature-256');
            $expected = 'sha256=' . hash_hmac('sha256', $raw, $appSecret);
            if ($sig === '' || !hash_equals($expected, $sig)) {
                log_message('warning', 'WA webhook signature mismatch — rejected.');
                return $this->response->setStatusCode(403)->setBody('forbidden');
            }
        } else {
            log_message('warning', 'WA webhook appSecret not configured — accepting unverified payload.');
        }

        $data = json_decode($raw, true) ?? [];
        log_message('info', 'WA webhook: ' . substr($raw, 0, 2000));

        $handled = false;

        $entries = $data['entry'] ?? [];
        foreach ($entries as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];

                // Delivery statuses
                foreach (($value['statuses'] ?? []) as $st) {
                    $this->handleStatusUpdate($st);
                    $handled = true;
                }

                // Inbound messages (Meta-standard shape)
                foreach (($value['messages'] ?? []) as $msg) {
                    $this->handleInboundMessage($msg, $raw);
                    $handled = true;
                }
            }
        }

        // BSP-specific adapter: NextBigBox / messaginghub sends incoming
        // messages with a flat shape {action:"userReply", from, text, type, ...}
        // and sometimes invalid JSON (unquoted timestamp). Translate to a
        // Meta-shape message so handleInboundMessage can process it unchanged.
        if (!$handled || $this->looksLikeNbbReply($raw, $data)) {
            $adapted = $this->adaptNextBigBoxPayload($raw, $data);
            if ($adapted !== null) {
                $this->handleInboundMessage($adapted, $raw);
            }
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    /** Heuristic: payload is a NextBigBox-flavoured reply event. */
    private function looksLikeNbbReply(string $raw, array $data): bool
    {
        if (!empty($data['action']) && (string) $data['action'] === 'userReply') return true;
        return stripos($raw, '"action"') !== false
            && stripos($raw, 'userReply') !== false;
    }

    /**
     * Translates a NextBigBox payload into Meta-shape message[] entry.
     * Works even when the JSON is invalid (unquoted Date timestamp) by
     * extracting fields via regex from the raw body as a fallback.
     */
    private function adaptNextBigBoxPayload(string $raw, array $data): ?array
    {
        // Try strict-decoded array first.
        $action = is_array($data) ? (string) ($data['action'] ?? '') : '';
        $from   = is_array($data) ? (string) ($data['from']   ?? '') : '';
        $text   = is_array($data) ? (string) ($data['text']   ?? '') : '';
        $type   = is_array($data) ? (string) ($data['type']   ?? '') : '';
        $eid    = is_array($data) ? (string) ($data['eventId'] ?? '') : '';

        // Regex fallback if strict decode missed fields (malformed JSON).
        $extract = static function (string $field) use ($raw): string {
            if (preg_match('/"' . preg_quote($field, '/') . '"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $raw, $m)) {
                return (string) $m[1];
            }
            return '';
        };
        if ($action === '') $action = $extract('action');
        if ($from   === '') $from   = $extract('from');
        if ($text   === '') $text   = $extract('text');
        if ($type   === '') $type   = $extract('type');
        if ($eid    === '') $eid    = $extract('eventId');

        // Only adapt explicit incoming events.
        if (!in_array($action, ['userReply', 'incoming', 'message', 'message_received'], true)) {
            return null;
        }
        if ($from === '' && $text === '') return null;

        // Normalise number: strip leading + and any non-digits.
        $from = preg_replace('/\D+/', '', $from);
        $type = $type !== '' ? strtolower($type) : 'text';

        // Aliases: BSPs sometimes shorten "document" to "doc", etc.
        $typeAliases = [
            'doc'   => 'document',
            'pdf'   => 'document',
            'img'   => 'image',
            'photo' => 'image',
            'vid'   => 'video',
            'aud'   => 'audio',
        ];
        if (isset($typeAliases[$type])) $type = $typeAliases[$type];

        $msg = [
            'from'      => $from,
            'id'        => $eid !== '' ? $eid : ('nbb_' . bin2hex(random_bytes(6))),
            'timestamp' => (string) time(),
            'type'      => $type,
        ];

        // Forward an optional reply-context so handleInboundMessage can infer
        // which RFQ this is for, when the vendor used the "Reply" gesture.
        $ctxMid = $extract('replyTo');
        if ($ctxMid === '') $ctxMid = $extract('inReplyTo');
        if ($ctxMid === '') $ctxMid = $extract('context_id');
        if ($ctxMid !== '') $msg['context'] = ['id' => $ctxMid];

        if ($type === 'text' || $type === '') {
            $msg['type'] = 'text';
            $msg['text'] = ['body' => $text];
        } elseif (in_array($type, ['image', 'document', 'audio', 'video', 'voice'], true)) {
            $msg[$type] = ['caption' => $text, 'id' => $extract('mediaId')];
        } else {
            // Unknown type — fall back to text so we at least capture it.
            $msg['type'] = 'text';
            $msg['text'] = ['body' => $text !== '' ? $text : ('Unhandled type: ' . $type)];
        }
        return $msg;
    }

    private function handleStatusUpdate(array $st): void
    {
        $mid    = (string) ($st['id'] ?? '');
        $status = (string) ($st['status'] ?? '');
        if ($mid === '' || $status === '') return;

        $logs  = new WhatsappLogModel();
        $row   = $logs->where('provider_message_id', $mid)->first();
        if (!$row) return;

        $update = ['delivery_status' => ucfirst($status)];
        $ts     = date('Y-m-d H:i:s');
        if ($status === 'delivered') $update['delivered_at'] = $ts;
        if ($status === 'read')      $update['read_at']      = $ts;
        if ($status === 'failed')    $update['failed_at']    = $ts;
        $logs->update($row['id'], $update);
    }

    private function handleInboundMessage(array $msg, string $raw): void
    {
        $from        = (string) ($msg['from'] ?? '');
        $type        = (string) ($msg['type'] ?? 'text');
        $providerMid = (string) ($msg['id'] ?? '');
        $text        = '';
        $mediaUrl    = null;
        $mediaType   = null;
        $mediaId     = null;

        if ($type === 'text') {
            $text = (string) ($msg['text']['body'] ?? '');
        } elseif (in_array($type, ['image','document','audio','video','voice'], true)) {
            $mediaType = $type;
            $mediaId   = (string) ($msg[$type]['id'] ?? '');
            $text      = (string) ($msg[$type]['caption'] ?? '');
        } elseif ($type === 'button') {
            $text = (string) ($msg['button']['text'] ?? '');
        } elseif ($type === 'interactive') {
            $text = json_encode($msg['interactive'] ?? []);
        }

        // Match context: vendor by WA/mobile, RFQ by masked_reference in body
        $vendorId = null;
        $fromCandidates = [$from];
        if (str_starts_with($from, '91') && strlen($from) >= 12) {
            $fromCandidates[] = substr($from, 2); // strip country code prefix
        }
        $fromCandidates[] = preg_replace('/\D+/', '', $from);

        $ven = (new VendorModel())
            ->groupStart()
                ->whereIn('whatsapp_no', $fromCandidates)
                ->orWhereIn('mobile', $fromCandidates)
            ->groupEnd()
            ->first();
        if ($ven) $vendorId = (int) $ven['id'];

        $rfqId = null;
        if ($text !== '' && preg_match('/(REF-[A-F0-9]{8})/i', $text, $m)) {
            $r = (new RfqModel())->where('masked_reference', strtoupper($m[1]))->first();
            if ($r) $rfqId = (int) $r['id'];
        }

        // No explicit ref in the body? Try inferring from conversation context:
        //   1) WhatsApp reply-to (context.id) → exact lookup via whatsapp_logs
        //   2) Most recent open RFQ dispatched to this vendor (last 7 days)
        // This handles vendors who reply with just "56k" instead of quoting REF.
        // Wrapped in try/catch so any DB hiccup never blocks message ingestion.
        if ($rfqId === null && $vendorId !== null) {
            try {
                $inferred = $this->inferRfqFromContext($msg, $vendorId);
                if ($inferred !== null) $rfqId = $inferred;
            } catch (\Throwable $e) {
                log_message('warning', 'inferRfqFromContext failed: ' . $e->getMessage());
            }
        }

        // Persist incoming
        $inId = (new WhatsappIncomingMessageModel())->insert([
            'sender_no'           => $from,
            'provider_message_id' => $providerMid,
            'message_type'        => $type,
            'text_body'           => $text,
            'media_type'          => $mediaType,
            'media_id'            => $mediaId,
            'rfq_id'              => $rfqId,
            'vendor_id'           => $vendorId,
            'is_processed'        => 0,
            'raw_payload'         => $raw,
            'received_at'         => date('Y-m-d H:i:s'),
        ]);

        // Auto-capture a quotation if we have rfq + vendor + a rate in the text.
        // Safeguard: don't double-create if a quotation from this vendor on this
        // RFQ was already recorded in the last hour (handles vendor sending two
        // messages back-to-back like "56k" then "transit 3 days").
        if ($rfqId && $vendorId && $text !== '') {
            $amount = self::extractAmount($text);
            if ($amount !== null) {
                $recentDup = (new QuotationModel())
                    ->where('rfq_id', $rfqId)->where('vendor_id', $vendorId)
                    ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                    ->countAllResults();
                if ($recentDup === 0) {
                    $rv = (new RfqVendorModel())->where('rfq_id', $rfqId)->where('vendor_id', $vendorId)->first();
                    $minutes = null;
                    if ($rv && !empty($rv['sent_at'])) {
                        $minutes = max(0, (int) round((strtotime(date('Y-m-d H:i:s')) - strtotime($rv['sent_at'])) / 60));
                    }
                    (new QuotationModel())->insert([
                        'rfq_id'                => $rfqId,
                        'vendor_id'             => $vendorId,
                        'quote_amount'          => $amount,
                        'response_source'       => 'whatsapp',
                        'response_time_minutes' => $minutes,
                        'remarks'               => $text,
                    ]);
                    if ($rv) {
                        (new RfqVendorModel())->update($rv['id'], [
                            'response_status' => 'Received',
                            'responded_at'    => date('Y-m-d H:i:s'),
                        ]);
                    }
                    (new WhatsappIncomingMessageModel())->update($inId, ['is_processed' => 1]);

                    // Notify the RFQ owner + Purchase team that a quote landed.
                    try {
                        $rfqRow = (new RfqModel())->find($rfqId);
                        $title  = 'Quote received — ' . ($rfqRow['rfq_no'] ?? ('RFQ #' . $rfqId));
                        $venRow = (new VendorModel())->find($vendorId);
                        $body   = ($venRow['company_name'] ?? 'Vendor') . ' quoted ₹' . number_format($amount, 2);
                        $link   = site_url('rfq/' . $rfqId);
                        if (!empty($rfqRow['created_by'])) {
                            \App\Libraries\Notify::toUser((int) $rfqRow['created_by'], $title, $body, $link,
                                ['type' => 'quote_received', 'icon' => 'cash-coin', 'actor_id' => 0]);
                        }
                        \App\Libraries\Notify::toPermission('quotations', 'can_view', $title, $body, $link,
                            ['type' => 'quote_received', 'icon' => 'cash-coin', 'actor_id' => 0]);
                    } catch (\Throwable $e) {
                        log_message('warning', 'Quote notify failed: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Best-effort RFQ inference when a vendor replies without the masked REF.
     *
     *   1. WhatsApp "Reply" gesture: $msg['context']['id'] holds the original
     *      message_id. Look that up in whatsapp_logs.provider_message_id and
     *      return module_ref_id (the rfq_id) if the original was an RFQ.
     *   2. Otherwise: pick the most recently dispatched RFQ this vendor was put
     *      on within the last 7 days, that they haven't already quoted, and
     *      whose RFQ status is still Open/In Progress.
     *
     * Returns null when no confident match — falls back to manual handling.
     */
    private function inferRfqFromContext(array $msg, int $vendorId): ?int
    {
        $db = \Config\Database::connect();

        // (1) Reply-to context: exact match via the original message id.
        $ctxMid = (string) ($msg['context']['id'] ?? '');
        if ($ctxMid !== '') {
            $log = $db->table('whatsapp_logs')
                ->where('provider_message_id', $ctxMid)
                ->where('module_name', 'rfq')
                ->orderBy('id', 'DESC')->limit(1)
                ->get()->getRowArray();
            if ($log && !empty($log['module_ref_id'])) {
                return (int) $log['module_ref_id'];
            }
        }

        // (2) Most-recent-open-RFQ fallback. rfq_vendors only has `sent_at` —
        // no `created_at` column. If a row has no sent_at yet (RFQ was created
        // but never actually dispatched), fall back to the parent RFQ's
        // created_at via the join so we still catch fresh queries.
        $cutoff = date('Y-m-d H:i:s', strtotime('-7 days'));
        $row = $db->table('rfq_vendors rv')
            ->select('rv.rfq_id, rv.sent_at, r.created_at AS rfq_created_at')
            ->join('rfq_master r', 'r.id = rv.rfq_id')
            ->where('rv.vendor_id', $vendorId)
            ->whereIn('rv.response_status', ['Pending', 'Sent', 'Queued'])
            ->whereIn('r.status', ['Open', 'In Progress'])
            ->groupStart()
                ->where('rv.sent_at >=', $cutoff)
                ->orWhere('r.created_at >=', $cutoff)
            ->groupEnd()
            ->orderBy('rv.sent_at', 'DESC')
            ->orderBy('r.created_at', 'DESC')
            ->limit(1)
            ->get()->getRowArray();
        return $row ? (int) $row['rfq_id'] : null;
    }

    /** Grab INR amount from free text. Very tolerant: "Rs 45000", "₹45,000", "45k", "45000/-" */
    private static function extractAmount(string $text): ?float
    {
        // Strip commas inside numbers (45,000 -> 45000) but keep whitespace so numbers stay separated.
        $t = preg_replace('/(\d),(?=\d)/', '$1', $text);

        // Find all numeric tokens with optional currency prefix / k/l suffix; pick the first plausible one.
        $pattern = '/(?:rs\.?|inr|₹)?\s*(\d+(?:\.\d+)?)\s*(k|l|lac|lakh)?\b/i';
        if (preg_match_all($pattern, $t, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $n = (float) $m[1];
                $suffix = strtolower($m[2] ?? '');
                if ($suffix === 'k')                               $n *= 1000;
                if (in_array($suffix, ['l','lac','lakh'], true))   $n *= 100000;
                // Require plausible freight rate range and exclude obvious non-money (dates, pincode, small counts)
                if ($n >= 1000 && $n <= 100000000) return round($n, 2);
            }
        }
        return null;
    }

    /**
     * POST /webhooks/brevo — Brevo event webhook.
     * Configure in Brevo dashboard: Transactional → Settings → Webhooks.
     * Brevo POSTs JSON like: { event: "delivered"|"hard_bounce"|"opened"|"click"|"spam"|"unsubscribed",
     *                          message-id, email, ts, ... }
     */
    public function brevoEvents()
    {
        $raw  = (string) $this->request->getBody();
        $data = json_decode($raw, true) ?? [];
        log_message('info', 'Brevo webhook: ' . substr($raw, 0, 1000));

        // Shared secret REQUIRED — refuse the webhook if not configured. Saves
        // the operator from accidentally exposing an open status-update endpoint.
        $expected = (string) ((new \App\Models\SettingModel())->get('brevo_webhook_secret', '') ?? '');
        if ($expected === '') {
            log_message('warning', 'Brevo webhook rejected: brevo_webhook_secret not configured.');
            return $this->response->setStatusCode(503)->setBody('webhook secret not configured');
        }
        $given = (string) ($this->request->getHeaderLine('X-Mailin-Custom') ?: $data['secret'] ?? '');
        if (!hash_equals($expected, $given)) {
            return $this->response->setStatusCode(403)->setBody('forbidden');
        }

        $events = isset($data[0]) ? $data : [$data];
        foreach ($events as $ev) {
            $msgId = (string) ($ev['message-id'] ?? $ev['messageId'] ?? '');
            $event = strtolower((string) ($ev['event'] ?? ''));
            if ($event === '') continue;

            // Prefer custom log id passed via X-TPT-Log-Id header (carried in Brevo "X-Tag")
            $log = null;
            $tag = (string) ($ev['tag'] ?? $ev['X-Tag'] ?? '');
            if ($tag !== '' && ctype_digit($tag)) {
                $log = (new \App\Models\EmailLogModel())->find((int) $tag);
            }
            if (!$log && $msgId !== '') {
                $log = (new \App\Models\EmailLogModel())->findByProviderId('brevo', $msgId);
            }
            if (!$log) continue;
            $this->applyEvent((int) $log['id'], $event, (string) ($ev['email'] ?? $log['to_email']), $ev);
        }

        return $this->response->setStatusCode(200)->setJSON(['ok' => true]);
    }

    /**
     * POST /webhooks/ses — SES event publishing via SNS.
     * Receives SubscriptionConfirmation (auto-confirms) + Notification (Bounce|Complaint|Delivery|Open|Click).
     */
    public function sesEvents()
    {
        $raw  = (string) $this->request->getBody();
        $data = json_decode($raw, true) ?? [];
        log_message('info', 'SES webhook: ' . substr($raw, 0, 1000));

        $type = (string) ($this->request->getHeaderLine('x-amz-sns-message-type') ?: $data['Type'] ?? '');

        // Auto-confirm subscription on first hit — but ONLY if URLs come from a real
        // AWS SNS endpoint AND the message signature is valid AND the topic ARN
        // matches an allowlist saved in settings.
        if ($type === 'SubscriptionConfirmation' && !empty($data['SubscribeURL'])) {
            if (!$this->verifySnsMessage($data)) {
                return $this->response->setStatusCode(403)->setBody('rejected: sns verification failed');
            }
            $confirmUrl = (string) $data['SubscribeURL'];
            $host = strtolower((string) parse_url($confirmUrl, PHP_URL_HOST));
            if (!$this->isSnsAwsHost($host)) {
                log_message('warning', 'SES webhook: rejected non-AWS SubscribeURL host=' . $host);
                return $this->response->setStatusCode(403)->setBody('rejected: bad host');
            }
            $allowedArn = (string) ((new \App\Models\SettingModel())->get('ses_topic_arn', '') ?? '');
            if ($allowedArn !== '' && !empty($data['TopicArn']) && $data['TopicArn'] !== $allowedArn) {
                log_message('warning', 'SES webhook: TopicArn mismatch ' . $data['TopicArn']);
                return $this->response->setStatusCode(403)->setBody('rejected: topic mismatch');
            }
            $ch = curl_init($confirmUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            curl_exec($ch); curl_close($ch);
            return $this->response->setStatusCode(200)->setBody('subscribed');
        }
        if ($type !== 'Notification') {
            return $this->response->setStatusCode(200)->setBody('ignored');
        }
        if (!$this->verifySnsMessage($data)) {
            return $this->response->setStatusCode(403)->setBody('rejected: sns verification failed');
        }

        $msg = json_decode((string) ($data['Message'] ?? ''), true) ?? [];
        $kind = (string) ($msg['eventType'] ?? $msg['notificationType'] ?? '');
        $headers = $msg['mail']['headers'] ?? [];
        $msgId = (string) ($msg['mail']['messageId'] ?? '');

        // Prefer custom X-TPT-Log-Id header — SMTP relays may rewrite Message-ID
        $log = null;
        foreach ((array) $headers as $h) {
            $name  = strtolower((string) ($h['name']  ?? ''));
            $value = (string) ($h['value'] ?? '');
            if ($name === 'x-tpt-log-id' && ctype_digit($value)) {
                $log = (new \App\Models\EmailLogModel())->find((int) $value);
                break;
            }
        }
        if (!$log && $msgId !== '') {
            $log = (new \App\Models\EmailLogModel())->findByProviderId('ses', $msgId);
        }
        if (!$log && !empty($msg['mail']['commonHeaders']['messageId'])) {
            $log = (new \App\Models\EmailLogModel())->findByProviderId('ses', (string) $msg['mail']['commonHeaders']['messageId']);
        }
        if (!$log) return $this->response->setStatusCode(200)->setBody('no_match');

        $emailTo = (string) ($msg['mail']['destination'][0] ?? $log['to_email']);
        $event = match (strtolower($kind)) {
            'delivery'  => 'delivered',
            'bounce'    => 'hard_bounce',
            'complaint' => 'spam',
            'open'      => 'opened',
            'click'     => 'click',
            'reject'    => 'send_error',
            default     => strtolower($kind),
        };
        $this->applyEvent((int) $log['id'], $event, $emailTo, $msg);

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    /** Allowlist for SNS-issued URLs. */
    private function isSnsAwsHost(string $host): bool
    {
        if ($host === '') return false;
        // Accept sns.<region>.amazonaws.com and signing-cert hosts
        if (preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $host)) return true;
        return false;
    }

    /**
     * Verifies an SNS message signature per AWS spec.
     * Returns true if signature OK or if signing cert URL is wrong (and we should reject).
     */
    private function verifySnsMessage(array $msg): bool
    {
        $required = ['Message','MessageId','Timestamp','TopicArn','Type','Signature','SigningCertURL'];
        foreach ($required as $k) {
            if (!isset($msg[$k])) return false;
        }
        $certUrl = (string) $msg['SigningCertURL'];
        $host = strtolower((string) parse_url($certUrl, PHP_URL_HOST));
        if (!preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $host)) {
            log_message('warning', 'SES SNS rejected — bad SigningCertURL host: ' . $host);
            return false;
        }

        // Build the canonical string-to-sign per SNS docs
        if ($msg['Type'] === 'Notification') {
            $fields = ['Message','MessageId','Subject','Timestamp','TopicArn','Type'];
        } else {
            // SubscriptionConfirmation / UnsubscribeConfirmation
            $fields = ['Message','MessageId','SubscribeURL','Timestamp','Token','TopicArn','Type'];
        }
        $stringToSign = '';
        foreach ($fields as $f) {
            if (!isset($msg[$f])) continue;
            $stringToSign .= $f . "\n" . $msg[$f] . "\n";
        }

        // Cache the signing cert briefly to avoid repeated network hits
        $cache = service('cache');
        $cacheKey = 'sns_cert_' . md5($certUrl);
        $cert = $cache->get($cacheKey);
        if ($cert === null) {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $cert = @file_get_contents($certUrl, false, $ctx);
            if ($cert === false || $cert === '') {
                log_message('warning', 'SES SNS — failed to fetch signing cert');
                return false;
            }
            $cache->save($cacheKey, $cert, 3600);
        }

        $pubkey = openssl_pkey_get_public($cert);
        if ($pubkey === false) return false;

        $algo = (($msg['SignatureVersion'] ?? '1') === '2') ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $sig  = base64_decode((string) $msg['Signature'], true);
        if ($sig === false) return false;

        $ok = openssl_verify($stringToSign, $sig, $pubkey, $algo) === 1;
        if (PHP_VERSION_ID < 80000 && function_exists('openssl_free_key')) {
            @openssl_free_key($pubkey);
        }
        return $ok;
    }

    /** Map normalized event names → email_logs status updates. */
    private function applyEvent(int $logId, string $event, string $email, array $payload): void
    {
        $now = date('Y-m-d H:i:s');
        $logModel = new \App\Models\EmailLogModel();
        $log = $logModel->find($logId);
        if (!$log) return;

        $update = ['last_event_at' => $now];
        switch ($event) {
            case 'delivered':
                $update['delivered_at'] = $now;
                if (!in_array($log['status'], ['Opened','Clicked','Bounced','Complained'], true)) {
                    $update['status'] = 'Delivered';
                }
                break;
            case 'opened':
            case 'open':
                $update['open_count'] = (int) $log['open_count'] + 1;
                if (empty($log['opened_at'])) $update['opened_at'] = $now;
                if (in_array($log['status'], ['Sent','Delivered'], true)) $update['status'] = 'Opened';
                break;
            case 'click':
            case 'clicked':
                $update['click_count'] = (int) $log['click_count'] + 1;
                if (empty($log['first_clicked_at'])) $update['first_clicked_at'] = $now;
                $update['status'] = 'Clicked';
                break;
            case 'hard_bounce':
            case 'soft_bounce':
            case 'bounced':
                $update['bounced_at']  = $now;
                $update['bounce_type'] = $event;
                $update['status']      = 'Bounced';
                if (in_array($event, ['hard_bounce','bounced'], true)) {
                    (new \App\Models\EmailUnsubscribeModel())->add($email, 'bounce', 'hard bounce');
                }
                break;
            case 'spam':
            case 'complaint':
            case 'complained':
                $update['complained_at'] = $now;
                $update['status']        = 'Complained';
                (new \App\Models\EmailUnsubscribeModel())->add($email, 'complaint', 'spam complaint');
                break;
            case 'unsubscribed':
                $update['unsubscribed_at'] = $now;
                (new \App\Models\EmailUnsubscribeModel())->add($email, 'link', 'esp unsubscribe');
                break;
            case 'send_error':
                $update['status'] = 'Failed';
                $update['error']  = substr(json_encode($payload), 0, 500);
                break;
        }
        $logModel->update($logId, $update);
        (new \App\Models\EmailEventModel())->record($logId, $event, $payload);
    }
}

<?php

namespace App\Controllers;

use App\Models\WhatsappLogModel;
use App\Models\WhatsappIncomingMessageModel;
use App\Models\WhatsappTemplateModel;
use App\Libraries\WhatsAppService;

class WhatsAppController extends BaseController
{
    public function logs()
    {
        $model = new WhatsappLogModel();
        $rows  = $model->orderBy('id', 'DESC')->paginate(30);
        return $this->render('whatsapp/logs', [
            'pageTitle' => 'WhatsApp [Communication] — Logs',
            'rows'      => $rows,
            'pager'     => $model->pager,
            'service'   => new WhatsAppService(),
        ], retroFixedShell: true);
    }

    public function inbox()
    {
        $model = new WhatsappIncomingMessageModel();
        $rows  = $model->orderBy('id', 'DESC')->paginate(30);
        return $this->render('whatsapp/inbox', [
            'pageTitle' => 'WhatsApp [Communication] — Inbox',
            'rows'      => $rows,
            'pager'     => $model->pager,
        ], retroFixedShell: true);
    }

    public function templates()
    {
        return $this->render('whatsapp/templates', [
            'pageTitle' => 'WhatsApp [Communication] — Templates',
            'rows'      => (new WhatsappTemplateModel())->orderBy('audience_type')->orderBy('template_key')->findAll(),
        ], retroFixedShell: true);
    }

    public function saveTemplate()
    {
        $id       = (int) $this->request->getPost('id');
        $data     = [
            'template_key'  => $this->request->getPost('template_key'),
            'template_name' => $this->request->getPost('template_name'),
            'audience_type' => $this->request->getPost('audience_type'),
            'language_code' => $this->request->getPost('language_code') ?: 'en',
            'body_text'     => $this->request->getPost('body_text'),
            'variables'     => $this->request->getPost('variables'),
            'status'        => $this->request->getPost('status') ? 1 : 0,
        ];
        $model = new WhatsappTemplateModel();
        if ($id > 0) {
            $model->update($id, $data);
        } else {
            $model->insert($data);
        }
        return redirect()->to(site_url('whatsapp/templates'))->with('success', 'Template saved.');
    }

    public function deleteTemplate(int $id)
    {
        (new WhatsappTemplateModel())->delete($id);
        return redirect()->to(site_url('whatsapp/templates'))->with('success', 'Template removed.');
    }

    /**
     * Send a one-off plaintext WhatsApp message to verify credentials.
     * Doesn't use a Meta template — just hits sendText() so any template-
     * approval issues are isolated from API-credential issues.
     */
    public function testSend()
    {
        $to   = trim((string) $this->request->getPost('to'));
        $body = trim((string) $this->request->getPost('body'));
        if ($to === '' || $body === '') {
            return redirect()->back()->with('error', 'Recipient and message are required.');
        }

        $svc = new WhatsAppService();
        if (!$svc->isConfigured()) {
            return redirect()->back()->with('error', 'WhatsApp Cloud API is not configured. Set whatsapp.phoneNumberId and whatsapp.accessToken in .env first.');
        }
        $res = $svc->sendText($to, $body, [
            'sent_by_user_id' => $this->auth->id(),
            'related_module'  => 'settings',
            'related_table'   => 'test',
        ]);
        if (!empty($res['ok'])) {
            $mid = $res['message_id'] ?? '(no id)';
            return redirect()->to(site_url('whatsapp/logs'))->with('success', "Test message sent. Provider message_id: $mid");
        }
        $err = $res['error'] ?? 'Unknown error';
        return redirect()->back()->with('error', "Test send failed: $err");
    }

    /**
     * GET /whatsapp/debug-send?to=919876543210
     *
     * Diagnostic helper for verifying a new provider/relay end-to-end. Sends a
     * plain test message via WhatsAppService::sendText() then returns the full
     * curl diagnostics (endpoint, http status, curl error, raw + decoded body)
     * as JSON directly in the browser so we don't have to query the DB.
     *
     * Only callable by users with WhatsApp module access (auth:whatsapp).
     */
    public function debugSend()
    {
        $to = trim((string) $this->request->getGet('to'));
        if ($to === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Pass ?to=<E164 number> e.g. ?to=919876543210']);
        }

        $svc = new WhatsAppService();
        $configured = $svc->isConfigured();

        // Pre-flight: surface the resolved config (token masked) so the caller
        // can confirm the .env values actually got picked up.
        $token = (string) env('whatsapp.accessToken', '');
        $config = [
            'configured'      => $configured,
            'graphBase'       => env('whatsapp.graphBase', ''),
            'apiVersion'      => env('whatsapp.apiVersion', ''),
            'phoneNumberId'   => env('whatsapp.phoneNumberId', ''),
            'accessToken_len' => strlen($token),
            'accessToken_head'=> $token !== '' ? substr($token, 0, 8) . '...' : '',
            'endpoint_built'  => rtrim((string) env('whatsapp.graphBase', ''), '/') . '/'
                              . trim((string) env('whatsapp.apiVersion', ''), '/') . '/'
                              . (string) env('whatsapp.phoneNumberId', '') . '/messages',
        ];

        if (!$configured) {
            return $this->response->setJSON([
                'ok'     => false,
                'config' => $config,
                'error'  => 'WhatsApp is not configured — phoneNumberId or accessToken is blank in .env.',
            ]);
        }

        $body = 'TPT debug test ' . date('H:i:s') . '. Reply OK if you got this.';
        $res  = $svc->sendText($to, $body, [
            'sent_by_user_id' => $this->auth->id(),
            'module_name'     => 'debug',
            'module_ref_id'   => 0,
        ]);

        // Pull the log row we just created to get the rich diagnostics that
        // dispatch() now writes to response_payload.
        $logId = (int) ($res['log_id'] ?? 0);
        $row   = $logId
            ? \Config\Database::connect()->table('whatsapp_logs')->where('id', $logId)->get()->getRowArray()
            : null;
        $diag  = null;
        if ($row && !empty($row['response_payload'])) {
            $diag = json_decode((string) $row['response_payload'], true) ?: ['raw' => $row['response_payload']];
        }

        return $this->response->setJSON([
            'ok'             => !empty($res['ok']),
            'sent_to'        => $to,
            'config'         => $config,
            'service_result' => $res,
            'log_row'        => $row ? [
                'id'                => $row['id'],
                'delivery_status'   => $row['delivery_status'],
                'error_message'     => $row['error_message'],
                'provider_message_id' => $row['provider_message_id'],
                'request_payload'   => json_decode((string) ($row['request_payload'] ?? '[]'), true),
            ] : null,
            'curl_diagnostics' => $diag,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /whatsapp/debug-probe?to=919876543210
     *
     * Diagnostic for cracking the auth header format on a new BSP relay. Hits
     * the configured endpoint with 7 common Authorization header shapes and
     * reports the HTTP status + body for each, so you can see at a glance
     * which format the relay accepts. Once you know the winner, set
     *   whatsapp.authFormat = '<winning_key>'
     * in .env and the live send path will use it.
     */
    public function debugProbe()
    {
        $to = trim((string) $this->request->getGet('to'));
        if ($to === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Pass ?to=<E164 number>']);
        }
        $token = (string) env('whatsapp.accessToken', '');
        if ($token === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'whatsapp.accessToken is blank in .env']);
        }

        $endpoint = rtrim((string) env('whatsapp.graphBase', ''), '/') . '/'
                  . trim((string) env('whatsapp.apiVersion', ''), '/') . '/'
                  . (string) env('whatsapp.phoneNumberId', '') . '/messages';
        $to       = WhatsAppService::normalizeNumber($to);
        $payload  = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => 'probe ' . date('His'), 'preview_url' => false],
        ];

        $formats = ['bearer', 'apikey', 'token', 'raw', 'x-api-key', 'api-key', 'apikey-q'];
        $results = [];
        foreach ($formats as $f) {
            $hdr = WhatsAppService::authHeader($token, $f);
            $ch  = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_HTTPHEADER     => [$hdr, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            $results[$f] = [
                'header_sent_as' => preg_replace('/(' . preg_quote(substr($token, 4), '/') . ')/', '***', $hdr),
                'http_status'    => $code,
                'curl_error'     => $err ?: null,
                'body_excerpt'   => mb_substr((string) $resp, 0, 400),
            ];
        }

        // Try to surface the obvious winner.
        $winner = null;
        foreach ($results as $k => $r) {
            if ($r['http_status'] >= 200 && $r['http_status'] < 300) { $winner = $k; break; }
        }

        return $this->response->setJSON([
            'endpoint'  => $endpoint,
            'recipient' => $to,
            'winner'    => $winner,
            'hint'      => $winner
                ? "Set: whatsapp.authFormat = '$winner' in .env, then clear writable/cache/."
                : 'No format returned 2xx — paste the body_excerpt back, the relay docs (or a 403/404 page) will tell us the right header.',
            'results'   => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /whatsapp/debug-webhook[?selftest=1]
     *
     * Diagnostic for the INCOMING side. Reports:
     *   1. The webhook URL + verify token (what to paste into messaginghub).
     *   2. Verification handshake test — fakes Meta's GET probe internally and
     *      confirms WebhookController::whatsappVerify replies with the expected
     *      challenge.
     *   3. The 10 most-recent incoming messages (so after you send a real WA
     *      message TO the relay number, you can refresh and see if it landed).
     *   4. If ?selftest=1, also fakes a Meta-shaped POST with a sample text
     *      message to verify whatsappReceive parses + stores it correctly.
     */
    public function debugWebhook()
    {
        $verifyToken = (string) env('whatsapp.verifyToken', '');
        $webhookUrl  = site_url('webhooks/whatsapp');
        $db          = \Config\Database::connect();

        $recent = [];
        if ($db->tableExists('whatsapp_incoming_messages')) {
            $recent = $db->table('whatsapp_incoming_messages')
                ->select('id, sender_no, message_type, text_body, rfq_id, vendor_id, is_processed, received_at')
                ->orderBy('id', 'DESC')->limit(10)
                ->get()->getResultArray();
        }

        // 1) Confirm the verify endpoint responds correctly to Meta's handshake.
        $verifyOk = null;
        $verifyDetail = '—';
        $ch = curl_init($webhookUrl . '?hub_mode=subscribe&hub_verify_token='
            . urlencode($verifyToken) . '&hub_challenge=PROBE12345');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $vResp = (string) curl_exec($ch);
        $vCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $vErr  = curl_error($ch);
        curl_close($ch);
        if ($vErr) {
            $verifyDetail = 'curl error: ' . $vErr;
        } elseif ($vCode === 200 && trim($vResp) === 'PROBE12345') {
            $verifyOk = true; $verifyDetail = 'Echoed challenge correctly (HTTP 200 → PROBE12345)';
        } else {
            $verifyOk = false; $verifyDetail = "HTTP $vCode, body: " . mb_substr($vResp, 0, 200);
        }

        // 2) Optional self-test: POST a fake Meta-format inbound payload to
        //    verify the controller parses + stores it. Mark the synthetic row.
        $selftest = null;
        if ((int) $this->request->getGet('selftest') === 1) {
            $payload = [
                'object' => 'whatsapp_business_account',
                'entry'  => [[
                    'changes' => [[
                        'field' => 'messages',
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => ['phone_number_id' => (string) env('whatsapp.phoneNumberId', '')],
                            'messages' => [[
                                'from' => '919999000099',
                                'id'   => 'selftest_' . bin2hex(random_bytes(6)),
                                'timestamp' => (string) time(),
                                'type' => 'text',
                                'text' => ['body' => 'SELFTEST inbound — please ignore'],
                            ]],
                        ],
                    ]],
                ]],
            ];
            $ch2 = curl_init($webhookUrl);
            curl_setopt_array($ch2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $sResp = (string) curl_exec($ch2);
            $sCode = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            // See if a new row appeared from this self-test sender.
            $newRow = $db->table('whatsapp_incoming_messages')
                ->where('sender_no', '919999000099')
                ->orderBy('id', 'DESC')->limit(1)
                ->get()->getRowArray();
            $selftest = [
                'post_http_status' => $sCode,
                'response_body'    => mb_substr($sResp, 0, 200),
                'row_created'      => $newRow ? true : false,
                'row_details'      => $newRow,
            ];
        }

        return $this->response->setJSON([
            'instructions' => [
                'In your messaginghub.solutions dashboard, set:',
                '  Webhook URL   = ' . $webhookUrl,
                '  Verify Token  = ' . $verifyToken,
                'Then send a WhatsApp message FROM any phone TO your relay number and refresh this page.',
                'Append ?selftest=1 to confirm the controller parses inbound payloads end-to-end without messaginghub.',
            ],
            'verify_endpoint_ok' => $verifyOk,
            'verify_endpoint_detail' => $verifyDetail,
            'webhook_url'      => $webhookUrl,
            'verify_token'     => $verifyToken,
            'recent_incoming'  => $recent,
            'selftest'         => $selftest,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /whatsapp/debug-spy[?clear=1]
     *
     * Returns the last 20 raw webhook POSTs captured by WebhookController's
     * spy. Lets us see exactly what the BSP is sending — headers + body — even
     * when our parser doesn't recognize the payload. Add ?clear=1 to wipe the
     * spy log before listening fresh.
     */
    public function debugSpy()
    {
        $file = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'webhook-spy.log';

        if ((int) $this->request->getGet('clear') === 1) {
            @file_put_contents($file, '');
            return $this->response->setJSON(['ok' => true, 'message' => 'Spy log cleared. Send a WhatsApp message now and refresh.']);
        }

        if (!is_file($file)) {
            return $this->response->setJSON([
                'ok'       => true,
                'entries'  => [],
                'message'  => 'No webhook POSTs received yet. Send a WhatsApp message to your relay number, then refresh.',
                'log_file' => $file,
            ]);
        }

        // Read last 20 lines without loading the whole file. Empty-file case
        // is common right after ?clear=1, so short-circuit before fread() which
        // throws ValueError on zero-length reads in PHP 8.
        $size = (int) filesize($file);
        $tail = '';
        if ($size > 0) {
            $chunk = min($size, 200_000);
            $fh    = fopen($file, 'rb');
            if ($fh !== false) {
                fseek($fh, -$chunk, SEEK_END);
                $tail = (string) fread($fh, $chunk);
                fclose($fh);
            }
        }
        $lines = $tail === '' ? [] : array_values(array_filter(array_map('trim', explode("\n", $tail))));
        $lines = array_slice($lines, -20);

        $entries = [];
        foreach (array_reverse($lines) as $ln) {
            $parsed = json_decode($ln, true);
            $entries[] = $parsed ?: ['raw_line' => mb_substr($ln, 0, 1000)];
        }

        return $this->response->setJSON([
            'ok'              => true,
            'log_file'        => $file,
            'log_size_bytes'  => $size,
            'count_shown'     => count($entries),
            'entries'         => $entries,
            'hint'            => count($entries) === 0
                ? 'File exists but no entries — your BSP is not POSTing to /webhooks/whatsapp. Re-check panel + event subscription.'
                : 'Look at entries[0] — that\'s the most recent webhook. Check body field to see the payload shape.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

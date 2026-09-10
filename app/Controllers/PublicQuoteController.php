<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Public, token-gated vendor quote-submission flow.
 *
 *   GET  /quote/{token}       — render the quote form (or "thank you" if already submitted / expired)
 *   POST /quote/{token}       — save the quote against this (rfq, vendor) pair
 *
 * No auth filter: the unguessable 64-char token IS the credential.
 * Each rfq_vendors row gets its own token at RFQ dispatch time.
 */
class PublicQuoteController extends Controller
{
    protected $helpers = ['url', 'form'];

    /** Resolve a token to an [{rfq_vendor}, {rfq}, {vendor}] tuple, or null. */
    private function resolve(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $db = \Config\Database::connect();
        $rv = $db->table('rfq_vendors')->where('quote_token', $token)->get()->getRowArray();
        if (!$rv) return null;
        $rfq    = $db->table('rfq_master')->where('id', (int) $rv['rfq_id'])->get()->getRowArray();
        $vendor = $db->table('vendors')->where('id', (int) $rv['vendor_id'])->get()->getRowArray();
        if (!$rfq || !$vendor) return null;
        // existing quote (vendor may have already submitted)
        $existing = $db->table('quotations')
            ->where('rfq_id', (int) $rfq['id'])
            ->where('vendor_id', (int) $vendor['id'])
            ->orderBy('id', 'DESC')
            ->limit(1)->get()->getRowArray();
        return ['rv' => $rv, 'rfq' => $rfq, 'vendor' => $vendor, 'existing' => $existing ?: null];
    }

    public function form(string $token)
    {
        $ctx = $this->resolve($token);
        if (!$ctx) return $this->response->setStatusCode(404)->setBody(view('public/quote_invalid'));
        $expired = !empty($ctx['rv']['quote_token_expires_at']) && strtotime($ctx['rv']['quote_token_expires_at']) < time();
        $closed  = in_array(($ctx['rfq']['status'] ?? ''), ['Closed','Cancelled'], true);
        return view('public/quote_form', [
            'token'    => $token,
            'rfq'      => $ctx['rfq'],
            'vendor'   => $ctx['vendor'],
            'existing' => $ctx['existing'],
            'expired'  => $expired,
            'closed'   => $closed,
            'company'  => (new \App\Models\SettingModel())->get('company_name', 'TPT') ?: 'TPT',
            'error'    => session()->getFlashdata('error'),
            'success'  => session()->getFlashdata('success'),
        ]);
    }

    public function submit(string $token)
    {
        $ctx = $this->resolve($token);
        if (!$ctx) return $this->response->setStatusCode(404)->setBody(view('public/quote_invalid'));
        $expired = !empty($ctx['rv']['quote_token_expires_at']) && strtotime($ctx['rv']['quote_token_expires_at']) < time();
        $closed  = in_array(($ctx['rfq']['status'] ?? ''), ['Closed','Cancelled'], true);
        if ($expired || $closed) {
            return redirect()->to(site_url('quote/' . $token))->with('error', 'This RFQ is no longer accepting quotes.');
        }

        $amount = (float) str_replace([',', ' '], '', (string) $this->request->getPost('quote_amount'));
        if ($amount <= 0) {
            return redirect()->to(site_url('quote/' . $token))->with('error', 'Please enter a valid rate.');
        }
        $transit = (int) $this->request->getPost('transit_days');
        $valid   = (string) $this->request->getPost('quote_valid_till');
        $remarks = trim((string) $this->request->getPost('remarks'));
        $availNotes = trim((string) $this->request->getPost('availability_notes'));

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Upsert quotation: if vendor already quoted, update; else insert
        $existing = $db->table('quotations')
            ->where('rfq_id', (int) $ctx['rfq']['id'])
            ->where('vendor_id', (int) $ctx['vendor']['id'])
            ->orderBy('id', 'DESC')->limit(1)
            ->get()->getRowArray();

        $row = [
            'rfq_id'             => (int) $ctx['rfq']['id'],
            'vendor_id'          => (int) $ctx['vendor']['id'],
            'quote_amount'       => $amount,
            'availability_notes' => $availNotes ?: null,
            'transit_days'       => $transit ?: null,
            'quote_valid_till'   => $valid ? date('Y-m-d', strtotime($valid)) : null,
            'response_source'    => 'email',
            'remarks'            => $remarks ?: null,
            'updated_at'         => $now,
        ];

        if ($existing) {
            $db->table('quotations')->where('id', (int) $existing['id'])->update($row);
            $quotationId = (int) $existing['id'];
        } else {
            // calculate response time
            $sentAt = $ctx['rv']['sent_at'] ?? null;
            if ($sentAt) {
                $row['response_time_minutes'] = max(0, (int) round((time() - strtotime($sentAt)) / 60));
            }
            $row['created_at'] = $now;
            $db->table('quotations')->insert($row);
            $quotationId = (int) $db->insertID();
        }

        $db->table('rfq_vendors')
            ->where('id', (int) $ctx['rv']['id'])
            ->update([
                'response_status'    => 'Quoted',
                'responded_at'       => $sentAt ?? $now,
                'quote_submitted_at' => $now,
            ]);

        // Fire "vendor_quote_received" alert to the agency (Phase D wires this in;
        // here we fire it directly so the basic flow works even without the
        // alerts matrix layer).
        $this->notifyAgency($ctx, $row, $quotationId);

        // In-app notification: RFQ owner + purchase managers.
        $rfqId    = (int) $ctx['rfq']['id'];
        $ownerId  = (int) ($ctx['rfq']['created_by'] ?? 0);
        $title    = 'Quote received — ' . ($ctx['rfq']['rfq_no'] ?? ('RFQ #' . $rfqId));
        $body     = ($ctx['vendor']['company_name'] ?? 'Vendor') . ' quoted ₹' . number_format($amount, 2);
        $link     = site_url('rfq/' . $rfqId);
        if ($ownerId > 0) {
            \App\Libraries\Notify::toUser($ownerId, $title, $body, $link, ['type' => 'quote_received', 'icon' => 'cash-coin', 'actor_id' => 0]);
        }
        // Everyone who works with quotations (Purchase team), role-name agnostic.
        \App\Libraries\Notify::toPermission('quotations', 'can_view', $title, $body, $link, ['type' => 'quote_received', 'icon' => 'cash-coin', 'actor_id' => 0]);

        return redirect()->to(site_url('quote/' . $token))
            ->with('success', 'Thank you — your rate has been recorded. We will reach out if your quote is selected.');
    }

    private function notifyAgency(array $ctx, array $quote, int $quotationId): void
    {
        $setting = new \App\Models\SettingModel();
        $to = (string) $setting->get('company_email', '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;
        $vars = [
            'rfq_no'        => $ctx['rfq']['rfq_no'] ?? '',
            'route'         => trim(($ctx['rfq']['pickup_city'] ?? '') . ' → ' . ($ctx['rfq']['drop_city'] ?? ''), ' →'),
            'vendor_name'   => $ctx['vendor']['company_name'] ?? '',
            'quote_amount'  => number_format($quote['quote_amount'], 0),
            'transit_days'  => $quote['transit_days'] ?? '—',
            'remarks'       => $quote['remarks'] ?? '—',
            'rfq_link'      => site_url('rfq/' . $ctx['rfq']['id']),
        ];
        (new \App\Libraries\EmailService())->sendTemplate('vendor_quote_received', $to, $vars, [
            'related_module' => 'rfq',
            'related_id'     => (int) $ctx['rfq']['id'],
        ]);
    }
}

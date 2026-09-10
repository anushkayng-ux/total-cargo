<?php

namespace App\Controllers;

use App\Models\QuotationModel;
use App\Models\QuotationComparisonLogModel;
use App\Models\RfqModel;
use App\Models\RfqVendorModel;
use App\Models\VendorModel;
use App\Models\LeadModel;

class QuotationsController extends BaseController
{
    public function index()
    {
        $search        = trim((string) $this->request->getGet('q'));
        $rfqNo         = trim((string) $this->request->getGet('rfq_no'));
        $vendorId      = (int) $this->request->getGet('vendor_id');
        $selected      = (string) $this->request->getGet('selected'); // 'final' | 'shortlisted' | ''
        $minAmt        = (string) $this->request->getGet('min_amount');
        $maxAmt        = (string) $this->request->getGet('max_amount');
        $perPage       = max(10, min(100, (int) ($this->request->getGet('per_page') ?? 25)));

        $model = new QuotationModel();
        $q = $model->select('quotations.*, r.rfq_no, r.masked_reference, r.pickup_city, r.drop_city, r.vehicle_type, v.company_name AS vendor_name, v.is_preferred, v.is_blacklisted, v.rating')
            ->join('rfq_master r', 'r.id = quotations.rfq_id')
            ->join('vendors v', 'v.id = quotations.vendor_id')
            ->orderBy('quotations.id', 'DESC');

        if ($search !== '') {
            $q->groupStart()
                ->like('v.company_name', $search)
                ->orLike('r.rfq_no', $search)
                ->orLike('r.masked_reference', $search)
                ->orLike('r.pickup_city', $search)
                ->orLike('r.drop_city', $search)
                ->orLike('quotations.remarks', $search)
              ->groupEnd();
        }
        if ($rfqNo !== '')   $q->like('r.rfq_no', $rfqNo);
        if ($vendorId)       $q->where('quotations.vendor_id', $vendorId);
        if ($selected === 'final')        $q->where('quotations.is_final_selected', 1);
        elseif ($selected === 'shortlisted') $q->where('quotations.is_shortlisted',  1);
        if (is_numeric($minAmt)) $q->where('quotations.quote_amount >=', (float) $minAmt);
        if (is_numeric($maxAmt)) $q->where('quotations.quote_amount <=', (float) $maxAmt);

        // Ownership scope — quotations link to RFQs → leads → clients; keep
        // non-admins to quotations whose lead's client belongs to them.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            $q->join('leads l', 'l.id = r.lead_id', 'left')
              ->whereIn('l.client_id', $ownedIds ?: [0]);
        }

        $rows  = $q->paginate($perPage);
        $pager = $model->pager;

        // Lightweight vendor list for the filter dropdown — only those who have quoted
        $vendors = \Config\Database::connect()->table('quotations q')
            ->select('v.id, v.company_name')->distinct()
            ->join('vendors v', 'v.id = q.vendor_id')
            ->orderBy('v.company_name', 'ASC')
            ->get()->getResultArray();

        return $this->render('quotations/index', [
            'pageTitle' => 'Quotations',
            'rows'      => $rows,
            'pager'     => $pager,
            'vendors'   => $vendors,
            'filters'   => compact('search','rfqNo','vendorId','selected','minAmt','maxAmt','perPage'),
        ]);
    }

    /** Manually enter a quotation against an RFQ (for phone/email responses). */
    public function storeManual(int $rfqId)
    {
        $rfq = (new RfqModel())->find($rfqId);
        if (!$rfq) return redirect()->to(site_url('rfq'))->with('error', 'RFQ not found.');

        $vendorId = (int) $this->request->getPost('vendor_id');
        $amount   = (float) $this->request->getPost('quote_amount');
        if ($vendorId <= 0 || $amount <= 0) {
            return redirect()->back()->with('error', 'Vendor and quote amount required.');
        }

        // Auto-add vendor to rfq_vendors if not already on it
        $rv = new RfqVendorModel();
        $already = $rv->where('rfq_id', $rfqId)->where('vendor_id', $vendorId)->first();
        if (!$already) {
            $rv->insert(['rfq_id' => $rfqId, 'vendor_id' => $vendorId, 'response_status' => 'Received', 'responded_at' => date('Y-m-d H:i:s')]);
        } else {
            $rv->update($already['id'], ['response_status' => 'Received', 'responded_at' => date('Y-m-d H:i:s')]);
        }

        (new QuotationModel())->insert([
            'rfq_id'             => $rfqId,
            'vendor_id'          => $vendorId,
            'quote_amount'       => $amount,
            'availability_notes' => $this->request->getPost('availability_notes') ?: null,
            'transit_days'       => $this->request->getPost('transit_days') ?: null,
            'quote_valid_till'   => $this->request->getPost('quote_valid_till') ?: null,
            'response_source'    => 'manual',
            'remarks'            => $this->request->getPost('remarks') ?: null,
        ]);

        (new RfqModel())->update($rfqId, ['status' => 'Quote Received']);

        // Notify the RFQ owner (unless they're the one entering it).
        if (!empty($rfq['created_by'])) {
            $vendor = (new VendorModel())->find($vendorId);
            \App\Libraries\Notify::toUser((int) $rfq['created_by'],
                'Quote recorded — ' . ($rfq['rfq_no'] ?? ('RFQ #' . $rfqId)),
                ($vendor['company_name'] ?? 'Vendor') . ' — ₹' . number_format($amount, 2),
                site_url('rfq/' . $rfqId),
                ['type' => 'quote_received', 'icon' => 'cash-coin']);
        }

        return redirect()->to(site_url('rfq/' . $rfqId))->with('success', 'Quotation saved.');
    }

    public function shortlist(int $rfqId, int $quoteId)
    {
        $q = (new QuotationModel())->find($quoteId);
        if (!$q || (int) $q['rfq_id'] !== $rfqId) return redirect()->to(site_url('rfq/' . $rfqId))->with('error', 'Not found.');
        (new QuotationModel())->update($quoteId, ['is_shortlisted' => 1]);
        (new RfqModel())->update($rfqId, ['status' => 'Shortlisted']);
        \App\Libraries\AuditLogger::log('quotations', $quoteId, 'shortlist', "Quote #$quoteId shortlisted for RFQ #$rfqId");
        return redirect()->to(site_url('rfq/' . $rfqId))->with('success', 'Quotation shortlisted.');
    }

    public function unshortlist(int $rfqId, int $quoteId)
    {
        (new QuotationModel())->update($quoteId, ['is_shortlisted' => 0]);
        \App\Libraries\AuditLogger::log('quotations', $quoteId, 'unshortlist', "Quote #$quoteId removed from shortlist (RFQ #$rfqId)");
        return redirect()->to(site_url('rfq/' . $rfqId))->with('success', 'Removed from shortlist.');
    }

    public function selectFinal(int $rfqId, int $quoteId)
    {
        $q = (new QuotationModel())->find($quoteId);
        if (!$q || (int) $q['rfq_id'] !== $rfqId) return redirect()->to(site_url('rfq/' . $rfqId))->with('error', 'Not found.');

        $db = \Config\Database::connect();
        $db->table('quotations')->where('rfq_id', $rfqId)->update(['is_final_selected' => 0]);
        (new QuotationModel())->update($quoteId, ['is_final_selected' => 1, 'is_shortlisted' => 1]);
        (new RfqModel())->update($rfqId, ['status' => 'Awarded']);
        \App\Libraries\AuditLogger::log('quotations', $quoteId, 'award',
            "Quote #$quoteId awarded for RFQ #$rfqId — vendor " . ($q['vendor_id'] ?? '?') . " at ₹" . ($q['quote_amount'] ?? '?'));

        (new QuotationComparisonLogModel())->log(
            $rfqId,
            (int) $q['vendor_id'],
            $this->buildComparisonNotes($rfqId, (int) $quoteId),
            $this->auth->id()
        );

        // Notify the winning vendor via email
        $notifyResult = (new \App\Libraries\NotificationService())->onQuoteSelected($rfqId, $quoteId);
        $extra = $notifyResult['sent'] > 0 ? ' · winning vendor notified' : '';

        // In-app: tell the originating CRM/Sales person a vendor is awarded so
        // they can convert to a booking / quote the client.
        $rfq = (new RfqModel())->find($rfqId);
        $crmUserId = 0;
        if (!empty($rfq['lead_id'])) {
            $lead = (new LeadModel())->find((int) $rfq['lead_id']);
            $crmUserId = (int) ($lead['assigned_crm_user_id'] ?? 0);
        }
        if ($crmUserId <= 0) $crmUserId = (int) ($rfq['created_by'] ?? 0);
        if ($crmUserId > 0) {
            $vendor = (new VendorModel())->find((int) $q['vendor_id']);
            \App\Libraries\Notify::toUser($crmUserId,
                'Vendor awarded — ' . ($rfq['rfq_no'] ?? ('RFQ #' . $rfqId)),
                ($vendor['company_name'] ?? 'Vendor') . ' @ ₹' . number_format((float) $q['quote_amount'], 2) . ' — convert to booking',
                site_url('bookings/from-rfq/' . $rfqId),
                ['type' => 'quote_awarded', 'icon' => 'trophy']);
        }

        // Assignment loop: the RFQ's assignee completed their work (awarded a
        // vendor) → notify them + whoever assigned the RFQ to them.
        \App\Libraries\Notify::toUsers(
            [(int) ($rfq['assigned_to'] ?? 0), (int) ($rfq['assigned_by'] ?? 0)],
            'RFQ awarded — ' . ($rfq['rfq_no'] ?? ('#' . $rfqId)),
            'Vendor selected — ready to convert to booking',
            site_url('rfq/' . $rfqId),
            ['type' => 'rfq_progress', 'icon' => 'arrow-repeat']);

        return redirect()->to(site_url('rfq/' . $rfqId))->with('success', 'Final vendor selected.' . $extra);
    }

    private function buildComparisonNotes(int $rfqId, int $quoteId): string
    {
        $rows = (new QuotationModel())->forRfq($rfqId);
        if (empty($rows)) return 'Selected without other quotes.';
        $lines = ['Quotations at time of selection:'];
        foreach ($rows as $r) {
            $mark = ((int) $r['id'] === $quoteId) ? '*SELECTED* ' : '';
            $lines[] = sprintf('%s%s — ₹%s (transit %s days, rating %s, %s)',
                $mark,
                $r['company_name'],
                number_format((float) $r['quote_amount'], 2),
                $r['transit_days'] ?? '?',
                $r['rating'] ?? 0,
                $r['response_source']
            );
        }
        return implode("\n", $lines);
    }

    /**
     * Returns a score breakdown per quotation so CRM can see why the engine ranked them.
     * Lower combined score = better (ranked by price first, adjusted by rating, transit, response speed).
     */
    public static function rankQuotations(array $quotations): array
    {
        if (empty($quotations)) return [];
        $minPrice = min(array_map(fn($q) => (float) $q['quote_amount'], $quotations));
        $out = [];
        foreach ($quotations as $q) {
            $price     = (float) $q['quote_amount'];
            $priceIdx  = $minPrice > 0 ? $price / $minPrice : 1;         // 1.0 = cheapest
            $transit   = (int) ($q['transit_days'] ?? 0);
            $rating    = (float) ($q['rating'] ?? 0);
            $pref      = (int) ($q['is_preferred'] ?? 0);
            $black     = (int) ($q['is_blacklisted'] ?? 0);
            $respMin   = (int) ($q['response_time_minutes'] ?? 0);

            // lower = better. Start with normalized price, add penalties/bonuses.
            $score = $priceIdx;
            if ($rating > 0) $score -= min(0.05 * $rating, 0.25);   // up to -0.25 for 5★
            if ($pref)       $score -= 0.05;
            if ($black)      $score += 1.0;
            if ($transit)    $score += min(0.02 * $transit, 0.30);  // up to +0.30
            if ($respMin)    $score += min(0.00005 * $respMin, 0.10); // tiny push for slow responses

            $out[] = $q + [
                '_score'     => round($score, 4),
                '_price_idx' => round($priceIdx, 3),
            ];
        }
        usort($out, fn($a, $b) => $a['_score'] <=> $b['_score']);
        return $out;
    }
}

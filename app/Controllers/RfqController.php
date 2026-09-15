<?php

namespace App\Controllers;

use App\Models\RfqModel;
use App\Models\RfqVendorModel;
use App\Models\VendorModel;
use App\Models\LeadModel;
use App\Models\LeadStatusHistoryModel;
use App\Models\WhatsappTemplateModel;
use App\Libraries\NumberGenerator;
use App\Libraries\WhatsAppService;

class RfqController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');

        $model = new RfqModel();
        $query = $model->withJoins()->orderBy('rfq_master.id', 'DESC');

        if ($search !== '') {
            $query->groupStart()
                ->like('rfq_master.rfq_no', $search)
                ->orLike('rfq_master.masked_reference', $search)
                ->orLike('rfq_master.pickup_city', $search)
                ->orLike('rfq_master.drop_city', $search)
                ->orLike('leads.lead_no', $search)
                ->groupEnd();
        }
        if ($status !== '') $query->where('rfq_master.status', $status);

        // Ownership scope — non-admins only see RFQs for their clients (via the linked lead).
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            if (empty($ownedIds)) {
                $query->where('1 = 0', null, false);
            } else {
                $query->where('leads.client_id IN (' . implode(',', array_map('intval', $ownedIds)) . ')', null, false);
            }
        }

        return $this->render('rfq/index', [
            'pageTitle' => 'RFQ Master [Sales & Operations] — List',
            'rows'      => $query->paginate($this->perPage()),
            'pager'     => $model->pager,
            'search'    => $search,
            'status'    => $status,
            'statuses'  => RfqModel::STATUSES,
        ], retroFixedShell: true);
    }

    /**
     * GET /rfq/queue — Purchase inbox.
     * Leads that Sales handed over (status "Sent to Purchase") and which don't
     * yet have an RFQ raised. One-click "Create RFQ & Dispatch" per row.
     */
    public function queue()
    {
        $db = \Config\Database::connect();
        $rows = $db->table('leads l')
            ->select('l.id, l.lead_no, l.pickup_city, l.drop_city, l.vehicle_type_required, l.material_type, l.weight, l.weight_unit, l.expected_dispatch_date, l.priority, l.updated_at, u.name AS assigned_name')
            ->join('users u', 'u.id = l.assigned_crm_user_id', 'left')
            ->where('l.current_status', 'Sent to Purchase')
            ->where('l.deleted_at', null)
            ->where("l.id NOT IN (SELECT lead_id FROM rfq_master WHERE lead_id IS NOT NULL)", null, false)
            ->orderBy('l.updated_at', 'DESC')
            ->get()->getResultArray();

        return $this->render('rfq/queue', [
            'pageTitle' => 'RFQ Master [Sales & Operations] — Purchase Inbox',
            'rows'      => $rows,
        ], retroFixedShell: true);
    }

    /**
     * GET /rfq/create  or  /rfq/create?lead_id=:id
     * Shows form and a vendor shortlist (route+vehicle match + preferred).
     */
    public function create()
    {
        $leadId = (int) $this->request->getGet('lead_id');
        $lead   = $leadId ? (new LeadModel())->find($leadId) : null;

        $prefill = [
            'pickup_city'       => $lead['pickup_city'] ?? '',
            'drop_city'         => $lead['drop_city'] ?? '',
            'vehicle_type'      => $lead['vehicle_type_required'] ?? '',
            'material_category' => $lead['material_type'] ?? '',
            'weight'            => $lead['weight'] ?? '',
            'weight_unit'       => $lead['weight_unit'] ?? 'TON',
            'loading_date'      => $lead['expected_dispatch_date'] ?? '',
        ];

        $includeUncovered = (bool) $this->request->getGet('include_uncovered');
        $criteria = $prefill;
        $criteria['include_uncovered'] = $includeUncovered;
        $suggested = $this->suggestVendors($criteria);

        // For the "show all anyway" affordance — if the strict filter produced
        // zero rows AND there's a drop city, count how many vendors exist
        // total so we can guide the user.
        $totalVendors = 0;
        if (!empty($prefill['drop_city']) && !$includeUncovered) {
            $totalVendors = (int) \Config\Database::connect()->table('vendors')
                ->where('status', 1)->where('is_blacklisted', 0)
                ->where('deleted_at', null)->countAllResults();
        }

        return $this->render('rfq/form', [
            'pageTitle'         => 'RFQ Master [Sales & Operations] — New',
            'lead'              => $lead,
            'prefill'           => $prefill,
            'suggested'         => $suggested,
            'includeUncovered'  => $includeUncovered,
            'totalVendors'      => $totalVendors,
            'historyHint'       => $this->historyHint($prefill['pickup_city'], $prefill['drop_city']),
        ], retroFixedShell: true);
    }

    /**
     * Smart defaults: if this pickup→drop has been quoted in the last 90 days,
     * surface the median rate + most-common vehicle as a hint. Empty array if
     * either city is blank or no history exists. Cheap — one indexed query.
     */
    private function historyHint(string $pickup, string $drop): array
    {
        if ($pickup === '' || $drop === '') return [];
        $db = \Config\Database::connect();
        $rows = $db->table('quotations q')
            ->select('q.quote_amount, r.vehicle_type, r.weight_unit')
            ->join('rfq_master r', 'r.id = q.rfq_id')
            ->where('LOWER(r.pickup_city)', strtolower($pickup))
            ->where('LOWER(r.drop_city)',   strtolower($drop))
            ->where('q.quote_amount >', 0)
            ->where('q.created_at >=', date('Y-m-d', strtotime('-90 days')))
            ->orderBy('q.id', 'DESC')
            ->limit(50)
            ->get()->getResultArray();
        if (empty($rows)) return [];

        $rates    = array_map(fn($r) => (float) $r['quote_amount'], $rows);
        $vehicles = array_map(fn($r) => trim((string) $r['vehicle_type']), $rows);
        sort($rates);
        $n = count($rates);
        $median = $n % 2 ? $rates[(int) floor($n / 2)] : ($rates[$n/2 - 1] + $rates[$n/2]) / 2;

        $vCount = array_count_values(array_filter($vehicles));
        arsort($vCount);
        $topVehicle = $vCount ? array_key_first($vCount) : null;

        return [
            'samples'     => $n,
            'median_rate' => $median,
            'min_rate'    => $rates[0],
            'max_rate'    => $rates[$n - 1],
            'top_vehicle' => $topVehicle,
        ];
    }

    public function suggest()
    {
        $criteria = [
            'pickup_city'  => (string) $this->request->getGet('pickup_city'),
            'drop_city'    => (string) $this->request->getGet('drop_city'),
            'vehicle_type' => (string) $this->request->getGet('vehicle_type'),
            // ?include_uncovered=1 ignores route coverage and returns every active
            // vendor — used by staff who want to manually pick vendors that
            // haven't been tagged yet
            'include_uncovered' => $this->request->getGet('include_uncovered') ? true : false,
        ];
        return $this->jsonOk(['vendors' => $this->suggestVendors($criteria)]);
    }

    /**
     * Suggest vendors for an RFQ.
     *
     * Coverage rule: when drop_city is provided AND include_uncovered is false,
     * the vendor must have at least one vendor_routes row whose drop_city
     * either equals the RFQ drop_city or is NULL (vendor declares "any drop").
     * Pickup coverage is enforced too if the vendor has any pickup-specific rows.
     */
    private function suggestVendors(array $c): array
    {
        $db = \Config\Database::connect();

        $dropCity   = trim((string) ($c['drop_city']   ?? ''));
        $pickupCity = trim((string) ($c['pickup_city'] ?? ''));
        $vehicle    = trim((string) ($c['vehicle_type'] ?? ''));
        $includeUncovered = !empty($c['include_uncovered']);

        $b = $db->table('vendors')
            ->select('vendors.id, vendors.vendor_code, vendors.company_name, vendors.owner_name, vendors.whatsapp_no, vendors.mobile, vendors.is_preferred, vendors.rating,
                      (SELECT COUNT(*) FROM vendor_routes vr WHERE vr.vendor_id = vendors.id AND vr.status = 1 AND (vr.drop_city = ' . $db->escape($dropCity) . ' OR vr.drop_city IS NULL) AND (vr.pickup_city = ' . $db->escape($pickupCity) . ' OR vr.pickup_city IS NULL)) AS route_match,
                      (SELECT COUNT(*) FROM vendor_routes vr WHERE vr.vendor_id = vendors.id AND vr.status = 1) AS has_any_routes,
                      (SELECT COUNT(*) FROM vendor_vehicle_types vvt WHERE vvt.vendor_id = vendors.id AND vvt.vehicle_type = ' . $db->escape($vehicle) . ' AND vvt.status = 1) AS vehicle_match')
            ->where('vendors.status', 1)
            ->where('vendors.is_blacklisted', 0)
            ->where('vendors.deleted_at IS NULL');

        // Strict destination filter: only vendors who (a) have a matching route,
        // or (b) have NO routes on file (treat as "not yet tagged" — surfaced
        // only when staff opts in via include_uncovered).
        if ($dropCity !== '' && !$includeUncovered) {
            $b->where(
                "(EXISTS (SELECT 1 FROM vendor_routes vr WHERE vr.vendor_id = vendors.id AND vr.status = 1 AND (vr.drop_city = " . $db->escape($dropCity) . " OR vr.drop_city IS NULL) AND (vr.pickup_city = " . $db->escape($pickupCity) . " OR vr.pickup_city IS NULL OR " . $db->escape($pickupCity) . " = '')))",
                null, false
            );
        }

        $b->orderBy('route_match', 'DESC')
          ->orderBy('vendors.is_preferred', 'DESC')
          ->orderBy('vendors.rating', 'DESC')
          ->orderBy('vendors.company_name', 'ASC')
          ->limit(150);

        return $b->get()->getResultArray();
    }

    public function store()
    {
        $post = $this->request->getPost();
        $vendorIds = array_filter(array_map('intval', (array) ($post['vendor_ids'] ?? [])));
        if (empty($vendorIds)) {
            return redirect()->back()->withInput()->with('error', 'Select at least one vendor.');
        }

        $rfq = new RfqModel();
        $data = [
            'rfq_no'            => NumberGenerator::rfq(),
            'masked_reference'  => NumberGenerator::rfqMaskedRef(),
            'lead_id'           => !empty($post['lead_id']) ? (int) $post['lead_id'] : null,
            'pickup_city'       => $post['pickup_city']       ?? null,
            'drop_city'         => $post['drop_city']         ?? null,
            'vehicle_type'      => $post['vehicle_type']      ?? null,
            'material_category' => $post['material_category'] ?? null,
            'weight'            => !empty($post['weight']) ? (float) $post['weight'] : null,
            'weight_unit'       => $post['weight_unit']       ?? 'TON',
            'loading_date'      => !empty($post['loading_date']) ? $post['loading_date'] : null,
            'status'            => 'Open',
            'created_by'        => $this->auth->id(),
        ];
        $rfqId = (int) $rfq->insert($data);

        $rv = new RfqVendorModel();
        foreach ($vendorIds as $vid) {
            $rv->insert([
                'rfq_id'          => $rfqId,
                'vendor_id'       => $vid,
                'response_status' => 'Pending',
            ]);
        }

        if (!empty($data['lead_id'])) {
            (new LeadModel())->update($data['lead_id'], ['current_status' => 'Sent to Purchase']);
            (new LeadStatusHistoryModel())->log((int) $data['lead_id'], null, 'Sent to Purchase', 'RFQ ' . $data['rfq_no'] . ' created', $this->auth->id());
        }

        // Notify the Purchase team an RFQ is ready to dispatch (+ admin oversight).
        $route = trim(($data['pickup_city'] ?? '') . ' → ' . ($data['drop_city'] ?? ''), ' →');
        \App\Libraries\Notify::toPermission('rfq', 'can_view',
            'RFQ created — ' . $data['rfq_no'],
            trim($route . ' · ' . ($data['vehicle_type'] ?? ''), ' ·') . ' — ' . count($vendorIds) . ' vendor(s), dispatch now',
            site_url('rfq/' . $rfqId),
            ['type' => 'rfq_created', 'icon' => 'file-earmark-plus']);

        return redirect()->to(site_url('rfq/' . $rfqId))->with('success', 'RFQ ' . $data['rfq_no'] . ' created with ' . count($vendorIds) . ' vendor(s). Dispatch now to send WhatsApp.');
    }

    public function show(int $id)
    {
        $row = (new RfqModel())->withJoins()->where('rfq_master.id', $id)->first();
        if (!$row) return redirect()->to(site_url('rfq'))->with('error', 'RFQ not found.');

        $rfqVendors = (new RfqVendorModel())->forRfq($id);
        $quotations = (new \App\Models\QuotationModel())->forRfq($id);

        $db      = \Config\Database::connect();
        $prevRow = $db->table('rfq_master')->select('id')->where('id <', $id)->orderBy('id', 'DESC')->get(1)->getRowArray();
        $nextRow = $db->table('rfq_master')->select('id')->where('id >', $id)->orderBy('id', 'ASC')->get(1)->getRowArray();
        $total   = $db->table('rfq_master')->countAllResults();

        return $this->render('rfq/show', [
            'pageTitle'   => 'RFQ Master [Sales & Operations]',
            'row'         => $row,
            'rfqVendors'  => $rfqVendors,
            'quotations'  => $quotations,
            'staff'       => (new \App\Models\UserModel())->activeList(),
            'wa'          => new WhatsAppService(),
            'template'    => (new WhatsappTemplateModel())->getByKey('rfq_vendor'),
            'prevId'      => $prevRow['id'] ?? null,
            'nextId'      => $nextRow['id'] ?? null,
            'total'       => $total,
        ], retroFixedShell: true);
    }

    /** POST /rfq/:id/assign — give this RFQ to a team member and notify them. */
    public function assign(int $id)
    {
        $rfq = (new RfqModel())->find($id);
        if (!$rfq) return redirect()->to(site_url('rfq'))->with('error', 'RFQ not found.');

        $userId = (int) $this->request->getPost('assigned_to');
        (new RfqModel())->update($id, [
            'assigned_to' => $userId > 0 ? $userId : null,
            'assigned_by' => $userId > 0 ? $this->auth->id() : null,
        ]);

        if ($userId > 0) {
            $route = trim(($rfq['pickup_city'] ?? '') . ' → ' . ($rfq['drop_city'] ?? ''), ' →');
            \App\Libraries\Notify::toUser($userId,
                'RFQ assigned to you — ' . ($rfq['rfq_no'] ?? ('#' . $id)),
                trim($route . ' · ' . ($rfq['vehicle_type'] ?? ''), ' ·') . ' — arrange vendors / quotes',
                site_url('rfq/' . $id),
                ['type' => 'rfq_assigned', 'icon' => 'person-check']);
        }
        return redirect()->to(site_url('rfq/' . $id))->with('success', 'RFQ assignment updated.');
    }

    /**
     * Dispatch masked WA message to all pending vendors on this RFQ.
     */
    public function dispatch(int $id)
    {
        $rfq = (new RfqModel())->find($id);
        if (!$rfq) return redirect()->to(site_url('rfq'))->with('error', 'RFQ not found.');

        $service  = new WhatsAppService();
        $template = (new WhatsappTemplateModel())->getByKey('rfq_vendor');
        if (!$template) {
            return redirect()->back()->with('error', 'WhatsApp template "rfq_vendor" not found.');
        }

        $onlyId = (int) $this->request->getPost('only_vendor_id');
        $rvModel = new RfqVendorModel();
        $vendors = $rvModel->select('rfq_vendors.*, vendors.company_name, vendors.whatsapp_no, vendors.mobile')
            ->join('vendors', 'vendors.id = rfq_vendors.vendor_id')
            ->where('rfq_id', $id)
            ->when($onlyId > 0, fn($q) => $q->where('rfq_vendors.vendor_id', $onlyId))
            ->where('rfq_vendors.response_status', 'Pending')
            ->findAll();

        $sent = 0; $failed = 0; $queued = 0;
        foreach ($vendors as $v) {
            $to = $v['whatsapp_no'] ?: $v['mobile'];
            if (!$to) { $failed++; continue; }

            // MASKED: route, vehicle, material, weight, loading, masked reference. NEVER client identifiers.
            $variables = [
                $rfq['masked_reference'],                                                                // {{1}}
                trim(($rfq['pickup_city'] ?? '') . ' → ' . ($rfq['drop_city'] ?? ''), ' →'),            // {{2}}
                $rfq['vehicle_type']       ?? '-',                                                      // {{3}}
                $rfq['material_category']  ?? '-',                                                      // {{4}}
                trim(($rfq['weight'] ?? '-') . ' ' . ($rfq['weight_unit'] ?? '')),                     // {{5}}
                $rfq['loading_date']       ?? 'TBC',                                                    // {{6}}
            ];

            $result = $service->sendTemplate($to, $template['template_name'], $variables, $template['language_code'], [
                'module_name'   => 'rfq',
                'module_ref_id' => $id,
                'audience_type' => 'vendor',
                'template_key'  => 'rfq_vendor',
            ]);

            if (!empty($result['queued'])) {
                $status = 'Queued';
                $queued++;
            } elseif (!empty($result['ok'])) {
                $status = 'Sent';
                $sent++;
            } else {
                $status = 'Failed';
                $failed++;
            }
            $rvModel->update($v['id'], [
                'whatsapp_message_id' => $result['message_id'],
                'sent_at'             => date('Y-m-d H:i:s'),
                'response_status'     => $status,
            ]);
        }

        (new RfqModel())->update($id, ['status' => 'In Progress']);

        // Assignment loop: tell the RFQ's assignee + assigner that vendors were
        // contacted (the dispatcher themselves is auto-skipped).
        \App\Libraries\Notify::toUsers(
            [(int) ($rfq['assigned_to'] ?? 0), (int) ($rfq['assigned_by'] ?? 0)],
            'RFQ dispatched — ' . ($rfq['rfq_no'] ?? ('#' . $id)),
            sprintf('%d vendor(s) contacted, awaiting quotes', $sent + $queued),
            site_url('rfq/' . $id),
            ['type' => 'rfq_dispatched', 'icon' => 'whatsapp']);

        // Email dispatch: vendor_rfq_invite (parallel to WhatsApp). Each vendor
        // gets a unique tokenized link to the public quote-submission form.
        $emailResult = (new \App\Libraries\NotificationService())->onRfqDispatched($id, $onlyId > 0 ? $onlyId : null);

        $msg = sprintf('Dispatched: %d sent, %d queued, %d failed · %d email invite(s) sent.',
            $sent, $queued, $failed, $emailResult['sent']);
        return redirect()->to(site_url('rfq/' . $id))->with('success', $msg);
    }

    /** Remind vendors who haven't responded. */
    public function remind(int $id)
    {
        $rfq = (new RfqModel())->find($id);
        if (!$rfq) return redirect()->to(site_url('rfq'))->with('error', 'Not found.');

        $service = new WhatsAppService();
        $template = (new WhatsappTemplateModel())->getByKey('rfq_vendor');
        if (!$template) return redirect()->back()->with('error', 'Template missing.');

        $rv = new RfqVendorModel();
        $vendors = $rv->select('rfq_vendors.*, vendors.company_name, vendors.whatsapp_no, vendors.mobile')
            ->join('vendors', 'vendors.id = rfq_vendors.vendor_id')
            ->where('rfq_id', $id)
            ->whereNotIn('rfq_vendors.response_status', ['Received'])
            ->findAll();

        $count = 0;
        foreach ($vendors as $v) {
            $to = $v['whatsapp_no'] ?: $v['mobile'];
            if (!$to) continue;
            $variables = [
                $rfq['masked_reference'],
                trim(($rfq['pickup_city'] ?? '') . ' → ' . ($rfq['drop_city'] ?? ''), ' →'),
                $rfq['vehicle_type'] ?? '-', $rfq['material_category'] ?? '-',
                trim(($rfq['weight'] ?? '-') . ' ' . ($rfq['weight_unit'] ?? '')),
                $rfq['loading_date'] ?? 'TBC',
            ];
            $service->sendTemplate($to, $template['template_name'], $variables, $template['language_code'], [
                'module_name' => 'rfq', 'module_ref_id' => $id, 'audience_type' => 'vendor', 'template_key' => 'rfq_vendor',
            ]);
            $rv->update($v['id'], [
                'reminder_count'   => (int) $v['reminder_count'] + 1,
                'last_reminder_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }
        return redirect()->to(site_url('rfq/' . $id))->with('success', "Reminded $count vendor(s).");
    }

    public function addVendor(int $id)
    {
        $rfq = (new RfqModel())->find($id);
        if (!$rfq) return redirect()->to(site_url('rfq'))->with('error', 'Not found.');
        $vendorId = (int) $this->request->getPost('vendor_id');
        if ($vendorId <= 0) return redirect()->back()->with('error', 'Select a vendor.');

        $rv = new RfqVendorModel();
        $exists = $rv->where('rfq_id', $id)->where('vendor_id', $vendorId)->first();
        if ($exists) return redirect()->back()->with('error', 'Vendor already on this RFQ.');

        $rv->insert(['rfq_id' => $id, 'vendor_id' => $vendorId, 'response_status' => 'Pending']);
        return redirect()->to(site_url('rfq/' . $id))->with('success', 'Vendor added.');
    }

    public function removeVendor(int $id, int $rvId)
    {
        (new RfqVendorModel())->where('rfq_id', $id)->delete($rvId);
        return redirect()->to(site_url('rfq/' . $id))->with('success', 'Vendor removed.');
    }

    public function close(int $id)
    {
        (new RfqModel())->update($id, ['status' => 'Closed']);
        return redirect()->to(site_url('rfq/' . $id))->with('success', 'RFQ closed.');
    }
}

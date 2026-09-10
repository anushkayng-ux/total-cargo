<?php

namespace App\Controllers\Portal;

use App\Models\LeadModel;
use App\Models\ClientModel;
use App\Libraries\NumberGenerator;

class BookingRequestController extends BaseController
{
    public function form()
    {
        $cid    = (int) $this->clientAuth->clientId();
        $client = (new ClientModel())->find($cid);

        $db = \Config\Database::connect();
        $recent = $db->table('leads')
            ->select('id, lead_no, current_status, pickup_city, drop_city, vehicle_type_required, expected_dispatch_date, vehicle_count, created_at')
            ->where('client_id', $cid)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // Pre-fill the date if the user clicked through from the calendar
        $prefillDate = (string) $this->request->getGet('date');
        if ($prefillDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefillDate)) {
            $prefillDate = '';
        }

        // Optional rebook prefill (set as flashdata by BookingsController::rebook)
        $rebook = (array) (session()->getFlashdata('rebook_prefill') ?? []);

        return $this->render('portal/request/form', [
            'pageTitle'   => 'Request a Truck',
            'client'      => $client,
            'recent'      => $recent,
            'prefillDate' => $prefillDate,
            'rebook'      => $rebook,
        ]);
    }

    public function submit()
    {
        if (!$this->clientAuth->can('request_booking')) {
            return redirect()->to(site_url('portal'))->with('error', 'Your role cannot submit booking requests.');
        }

        $rules = [
            'pickup_city'           => 'required|min_length[2]|max_length[80]',
            'drop_city'             => 'required|min_length[2]|max_length[80]',
            'vehicle_type_required' => 'required|max_length[80]',
            'vehicle_count'         => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
            'material_type'         => 'permit_empty|max_length[120]',
            'weight'                => 'permit_empty|decimal',
            'expected_dispatch_date'=> 'permit_empty|valid_date',
            'priority'              => 'permit_empty|in_list[Low,Normal,High,Urgent]',
            'remarks'               => 'permit_empty|max_length[2000]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $cid    = (int) $this->clientAuth->clientId();
        $client = (new ClientModel())->find($cid);

        // Resolve "portal" lead source
        $db       = \Config\Database::connect();
        $sourceId = (int) ($db->table('lead_sources')->where('source_key', 'portal')->get()->getRow('id') ?? 0);
        if (!$sourceId) {
            // fallback: use 'web' if portal source wasn't seeded
            $sourceId = (int) ($db->table('lead_sources')->where('source_key', 'web')->get()->getRow('id') ?? 0) ?: null;
        }

        $leadModel = new LeadModel();
        // Auto-assign portal-originated leads to the client's account manager
        // — but only if they're still active and not soft-deleted.
        $assignee = null;
        if (!empty($client['account_manager_user_id'])) {
            $am = (new \App\Models\UserModel())->find((int) $client['account_manager_user_id']);
            if ($am && (int) $am['status'] === 1) {
                $assignee = (int) $am['id'];
            }
        }

        $leadModel->insert([
            'lead_no'                => NumberGenerator::lead(),
            'lead_datetime'          => date('Y-m-d H:i:s'),
            'source_id'              => $sourceId,
            'client_id'              => $cid,
            'client_name'            => $this->clientAuth->user()['name'] ?? '',
            'company_name'           => $client['company_name'] ?? '',
            'mobile'                 => $client['mobile'] ?? '',
            'alt_mobile'             => $client['alt_mobile'] ?? '',
            'email'                  => $client['email'] ?? '',
            'pickup_city'            => trim((string) $this->request->getPost('pickup_city')),
            'drop_city'              => trim((string) $this->request->getPost('drop_city')),
            'material_type'          => trim((string) $this->request->getPost('material_type')),
            'vehicle_type_required'  => trim((string) $this->request->getPost('vehicle_type_required')),
            'vehicle_count'          => max(1, (int) ($this->request->getPost('vehicle_count') ?: 1)),
            'weight'                 => $this->request->getPost('weight') ?: null,
            'weight_unit'            => 'TON',
            'expected_dispatch_date' => $this->request->getPost('expected_dispatch_date') ?: null,
            'priority'               => $this->request->getPost('priority') ?: 'Normal',
            'assigned_crm_user_id'   => $assignee,
            'current_status'         => 'New',
            'remarks'                => trim((string) $this->request->getPost('remarks')),
            'created_by'             => null,
        ]);
        $leadId = (int) $leadModel->getInsertID();

        $this->audit('lead', $leadId, 'create_via_portal',
            'Booking request submitted via client portal',
            ['source' => 'portal']
        );

        return redirect()->to(site_url('portal/request'))
            ->with('success', 'Request submitted. Our team will get back to you with a quote shortly.');
    }
}

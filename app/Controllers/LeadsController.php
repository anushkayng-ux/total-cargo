<?php

namespace App\Controllers;

use App\Models\LeadModel;
use App\Models\LeadFollowupModel;
use App\Models\LeadStatusHistoryModel;
use App\Models\LeadSourceModel;
use App\Models\ClientModel;
use App\Models\UserModel;
use App\Libraries\NumberGenerator;

class LeadsController extends BaseController
{
    use \App\Traits\ExportsCsv;

    public function index()
    {
        $filters = $this->parseFilters();
        $model   = $this->filteredQuery($filters);

        return $this->render('leads/index', [
            'pageTitle' => 'Lead Master [Sales & Operations] — List',
            'rows'      => $model->paginate($this->perPage()),
            'pager'     => $model->pager,
            'filters'   => $filters,
            'sources'   => (new LeadSourceModel())->where('status', 1)->orderBy('source_name')->findAll(),
            'users'     => (new UserModel())->where('status', 1)->orderBy('name')->findAll(),
            'statuses'  => LeadModel::STATUSES,
        ], retroFixedShell: true);
    }

    public function export()
    {
        $rows = $this->filteredQuery($this->parseFilters())->findAll();
        return $this->streamCsv('leads-' . date('Y-m-d') . '.csv', [
            'lead_no'         => 'Lead No',
            'lead_datetime'   => 'Date',
            'client_name'     => 'Contact',
            'company_name'    => 'Company',
            'mobile'          => 'Mobile',
            'pickup_city'     => 'Pickup',
            'drop_city'       => 'Drop',
            'vehicle_type'    => 'Vehicle Type',
            'current_status'  => 'Status',
            'assigned_to_name'=> 'Assigned To',
            'source_name'     => 'Source',
        ], $rows);
    }

    private function parseFilters(): array
    {
        $req = $this->request;
        return [
            'search'   => trim((string) $req->getGet('q')),
            'status'   => (string) $req->getGet('status'),
            'assignee' => (int) $req->getGet('assignee'),
            'source'   => (int) $req->getGet('source'),
            'from'     => (string) $req->getGet('from'),
            'to'       => (string) $req->getGet('to'),
        ];
    }

    private function filteredQuery(array $f)
    {
        $model = new LeadModel();
        $q     = $model->withJoins()->orderBy('leads.id', 'DESC');
        if ($f['search'] !== '') {
            $q->groupStart()
                ->like('leads.lead_no', $f['search'])
                ->orLike('leads.client_name', $f['search'])
                ->orLike('leads.company_name', $f['search'])
                ->orLike('leads.mobile', $f['search'])
                ->orLike('leads.pickup_city', $f['search'])
                ->orLike('leads.drop_city', $f['search'])
                ->groupEnd();
        }
        if ($f['status'] !== '')   $q->where('leads.current_status', $f['status']);
        if ($f['assignee'] > 0)    $q->where('leads.assigned_crm_user_id', $f['assignee']);
        if ($f['source'] > 0)      $q->where('leads.source_id', $f['source']);
        if ($f['from'] !== '')     $q->where('DATE(leads.lead_datetime) >=', $f['from']);
        if ($f['to'] !== '')       $q->where('DATE(leads.lead_datetime) <=', $f['to']);

        // Ownership scope — non-admins only see leads whose client belongs to them.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) $q->whereIn('leads.client_id', $ownedIds ?: [0]);

        return $q;
    }

    public function create()
    {
        return $this->render('leads/form', [
            'pageTitle' => 'Lead Master [Sales & Operations] — New',
            'row'       => null,
            'sources'   => (new LeadSourceModel())->where('status', 1)->orderBy('source_name')->findAll(),
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'users'     => (new UserModel())->where('status', 1)->orderBy('name')->findAll(),
            'statuses'  => LeadModel::STATUSES,
        ], retroFixedShell: true);
    }

    public function store()
    {
        $model = new LeadModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = $this->request->getPost();
        $data['lead_no']       = NumberGenerator::lead();
        $data['lead_datetime'] = !empty($data['lead_datetime']) ? $data['lead_datetime'] : date('Y-m-d H:i:s');
        $data['current_status']= $data['current_status'] ?? 'New';
        $data['priority']      = $data['priority'] ?? 'Normal';
        $data['created_by']    = $this->auth->id();

        foreach (['source_id', 'client_id', 'assigned_crm_user_id'] as $k) {
            if (empty($data[$k])) $data[$k] = null;
        }
        if (empty($data['expected_dispatch_date'])) $data['expected_dispatch_date'] = null;
        if (empty($data['weight'])) $data['weight'] = null;
        $data['assigned_by'] = !empty($data['assigned_crm_user_id']) ? $this->auth->id() : null;

        $id = $model->insert($data);
        if ($id) {
            (new LeadStatusHistoryModel())->log((int) $id, null, (string) $data['current_status'], 'Lead created', $this->auth->id());

            $route = trim(($data['pickup_city'] ?? '') . ' → ' . ($data['drop_city'] ?? ''), ' →');
            $title = 'New lead — ' . $data['lead_no'];
            $body  = $route !== '' ? $route : ($data['company_name'] ?? null);
            $link  = site_url('leads/' . $id);
            // If assigned, ping that person directly; always inform the CRM team.
            if (!empty($data['assigned_crm_user_id'])) {
                \App\Libraries\Notify::toUser((int) $data['assigned_crm_user_id'], 'Lead assigned to you — ' . $data['lead_no'], $body, $link,
                    ['type' => 'lead_new', 'icon' => 'person-check']);
            }
            \App\Libraries\Notify::toPermission('leads', 'can_view', $title, $body, $link,
                ['type' => 'lead_new', 'icon' => 'person-plus']);
        }
        return redirect()->to(site_url('leads/' . $id))->with('success', 'Lead ' . $data['lead_no'] . ' created.');
    }

    public function show(int $id)
    {
        $row = (new LeadModel())->withJoins()->where('leads.id', $id)->first();
        if (!$row) return redirect()->to(site_url('leads'))->with('error', 'Lead not found.');

        return $this->render('leads/show', [
            'pageTitle' => 'Lead Master [Sales & Operations]',
            'row'       => $row,
            'followups' => (new LeadFollowupModel())->forLead($id),
            'history'   => (new LeadStatusHistoryModel())->forLead($id),
            'statuses'  => LeadModel::STATUSES,
            'users'     => (new UserModel())->where('status', 1)->orderBy('name')->findAll(),
        ], retroFixedShell: true);
    }

    public function edit(int $id)
    {
        $row = (new LeadModel())->find($id);
        if (!$row) return redirect()->to(site_url('leads'))->with('error', 'Lead not found.');

        return $this->render('leads/form', [
            'pageTitle' => 'Lead Master [Sales & Operations] — Edit',
            'row'       => $row,
            'sources'   => (new LeadSourceModel())->where('status', 1)->orderBy('source_name')->findAll(),
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'users'     => (new UserModel())->where('status', 1)->orderBy('name')->findAll(),
            'statuses'  => LeadModel::STATUSES,
        ], retroFixedShell: true);
    }

    public function update(int $id)
    {
        $model = new LeadModel();
        $old   = $model->find($id);
        if (!$old) return redirect()->to(site_url('leads'))->with('error', 'Not found.');

        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = $this->request->getPost();
        $data['updated_by'] = $this->auth->id();
        foreach (['source_id', 'client_id', 'assigned_crm_user_id'] as $k) {
            if (empty($data[$k])) $data[$k] = null;
        }
        if (empty($data['expected_dispatch_date'])) $data['expected_dispatch_date'] = null;
        if (empty($data['weight'])) $data['weight'] = null;

        $newStatus = (string) ($data['current_status'] ?? $old['current_status']);
        $model->update($id, $data);

        if ($newStatus !== $old['current_status']) {
            (new LeadStatusHistoryModel())->log((int) $id, $old['current_status'], $newStatus, 'Updated via edit form', $this->auth->id());
        }
        return redirect()->to(site_url('leads/' . $id))->with('success', 'Lead updated.');
    }

    public function delete(int $id)
    {
        (new LeadModel())->delete($id);
        return redirect()->to(site_url('leads'))->with('success', 'Lead removed.');
    }

    public function changeStatus(int $id)
    {
        $model = new LeadModel();
        $lead  = $model->find($id);
        if (!$lead) return redirect()->to(site_url('leads'))->with('error', 'Not found.');

        $newStatus = (string) $this->request->getPost('new_status');
        if (!in_array($newStatus, LeadModel::STATUSES, true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }
        $remarks    = (string) $this->request->getPost('remarks');
        $lostReason = (string) $this->request->getPost('lost_reason');

        $updates = [
            'current_status' => $newStatus,
            'updated_by'     => $this->auth->id(),
        ];
        if ($newStatus === 'Lost' && $lostReason !== '') {
            $updates['lost_reason'] = $lostReason;
        }
        $model->update($id, $updates);
        (new LeadStatusHistoryModel())->log((int) $id, $lead['current_status'], $newStatus, $remarks ?: null, $this->auth->id());

        // Notify the relevant team when a lead crosses a team boundary.
        $route = trim(($lead['pickup_city'] ?? '') . ' → ' . ($lead['drop_city'] ?? ''), ' →');
        $link  = site_url('leads/' . $id);
        if ($newStatus === 'Sent to Purchase') {
            // Target anyone who can see RFQs (= the Purchase team), no matter
            // what their role is actually named. Using can_view is inclusive so
            // we don't miss purchase staff whose custom role is view-only.
            \App\Libraries\Notify::toPermission('rfq', 'can_view',
                'New query for Purchase — ' . ($lead['lead_no'] ?? ('Lead #' . $id)),
                trim($route . ' · ' . ($lead['vehicle_type_required'] ?? ''), ' ·') . ' — arrange vehicle / quotation',
                site_url('rfq/create?lead_id=' . $id),
                ['type' => 'lead_handoff', 'icon' => 'arrow-right-circle']);
        } elseif ($newStatus === 'Sent to Client') {
            if (!empty($lead['assigned_crm_user_id'])) {
                \App\Libraries\Notify::toUser((int) $lead['assigned_crm_user_id'],
                    'Quote ready to share — ' . ($lead['lead_no'] ?? ('Lead #' . $id)),
                    $route . ' — purchase has rates, quote the client',
                    $link, ['type' => 'lead_status', 'icon' => 'send']);
            }
        }

        // Assignment loop: keep the assignee AND the person who assigned this
        // lead in sync on every status move (each is notified unless they're the
        // one who made the change). Admins always get an oversight copy.
        \App\Libraries\Notify::toUsers(
            [(int) ($lead['assigned_crm_user_id'] ?? 0), (int) ($lead['assigned_by'] ?? 0)],
            ($lead['lead_no'] ?? ('Lead #' . $id)) . ' → ' . $newStatus,
            $route ?: null,
            $link,
            ['type' => 'lead_progress', 'icon' => 'arrow-repeat']);

        return redirect()->to(site_url('leads/' . $id))->with('success', 'Status moved to ' . $newStatus . '.');
    }

    public function assign(int $id)
    {
        $model = new LeadModel();
        $lead  = $model->find($id);
        if (!$lead) return redirect()->to(site_url('leads'))->with('error', 'Not found.');

        $userId = (int) $this->request->getPost('assigned_crm_user_id');
        $model->update($id, [
            'assigned_crm_user_id' => $userId > 0 ? $userId : null,
            'assigned_by'          => $userId > 0 ? $this->auth->id() : null,
            'updated_by' => $this->auth->id(),
        ]);

        // Tell the newly-assigned user they own this lead now.
        if ($userId > 0) {
            $route = trim(($lead['pickup_city'] ?? '') . ' → ' . ($lead['drop_city'] ?? ''), ' →');
            \App\Libraries\Notify::toUser($userId,
                'Lead assigned to you — ' . ($lead['lead_no'] ?? ('Lead #' . $id)),
                $route !== '' ? $route : null,
                site_url('leads/' . $id),
                ['type' => 'lead_assigned', 'icon' => 'person-check']);
        }
        return redirect()->to(site_url('leads/' . $id))->with('success', 'Assignment updated.');
    }

    /** POST /leads/bulk — apply action to a batch of lead IDs */
    public function bulk()
    {
        $ids    = array_map('intval', (array) $this->request->getPost('ids'));
        $action = (string) $this->request->getPost('action');
        if (empty($ids) || $action === '') {
            return redirect()->back()->with('error', 'Select rows and an action.');
        }
        $model  = new LeadModel();
        [$kind, $value] = array_pad(explode('::', $action, 2), 2, '');

        if ($kind === 'assign') {
            $userId = (int) $value;
            $model->whereIn('id', $ids)->set([
                'assigned_crm_user_id' => $userId > 0 ? $userId : null,
                'updated_by'           => $this->auth->id(),
                'updated_at'           => date('Y-m-d H:i:s'),
            ])->update();
            return redirect()->back()->with('success', count($ids) . ' lead(s) reassigned.');
        }
        if ($kind === 'status') {
            if (!in_array($value, LeadModel::STATUSES, true)) return redirect()->back()->with('error', 'Invalid status.');
            $hist = new LeadStatusHistoryModel();
            $now  = date('Y-m-d H:i:s');
            foreach ($ids as $id) {
                $lead = $model->find($id);
                if (!$lead) continue;
                if ($lead['current_status'] === $value) continue;
                $model->update($id, [
                    'current_status' => $value,
                    'updated_by'     => $this->auth->id(),
                ]);
                $hist->insert([
                    'lead_id'    => $id,
                    'old_status' => $lead['current_status'],
                    'new_status' => $value,
                    'changed_by' => $this->auth->id(),
                    'changed_at' => $now,
                    'notes'      => 'Bulk update',
                ]);
            }
            return redirect()->back()->with('success', count($ids) . ' lead(s) updated.');
        }
        return redirect()->back()->with('error', 'Unknown action.');
    }

    public function addFollowup(int $id)
    {
        $lead = (new LeadModel())->find($id);
        if (!$lead) return redirect()->to(site_url('leads'))->with('error', 'Not found.');

        $rules = [
            'followup_datetime' => 'required|valid_date',
            'followup_type'     => 'permit_empty|max_length[40]',
            'discussion_notes'  => 'required|min_length[2]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        (new LeadFollowupModel())->insert([
            'lead_id'                => $id,
            'followup_datetime'      => $this->request->getPost('followup_datetime'),
            'followup_type'          => $this->request->getPost('followup_type'),
            'discussion_notes'       => $this->request->getPost('discussion_notes'),
            'next_followup_datetime' => $this->request->getPost('next_followup_datetime') ?: null,
            'created_by'             => $this->auth->id(),
        ]);

        return redirect()->to(site_url('leads/' . $id))->with('success', 'Follow-up saved.');
    }

    /**
     * Generate a client-facing Quotation PDF for a lead.
     *   GET /leads/:id/quotation.pdf[?rate=xxxxx]
     * Rate defaults to the ?rate= query param, falls back to a placeholder.
     * Matches the TCE Quotation letterhead / layout the client showed us.
     */
    public function quotationPdf(int $id)
    {
        $lead = (new LeadModel())->find($id);
        if (!$lead) return redirect()->to(site_url('leads'))->with('error', 'Lead not found.');

        // Pull client (bill-to) — either linked client or ad-hoc contact on the lead itself.
        $client = null;
        if (!empty($lead['client_id'])) {
            $client = (new ClientModel())->find((int) $lead['client_id']);
        }
        if (!$client) {
            $client = [
                'company_name' => $lead['company_name'] ?? $lead['client_name'] ?? '',
                'contact_name' => $lead['client_name']  ?? '',
                'mobile'       => $lead['mobile']       ?? '',
                'email'        => $lead['email']        ?? '',
                'address'      => trim(($lead['pickup_city'] ?? '') . ', ' . ($lead['pickup_state'] ?? ''), ', '),
                'city'         => $lead['pickup_city']  ?? '',
                'state'        => $lead['pickup_state'] ?? '',
                'gst_no'       => '',
            ];
        }

        $rate = (float) ($this->request->getGet('rate') ?? 0);
        $qty  = 1;
        $items = [[
            'title'  => 'Transportation Services',
            'detail' => trim(
                ($lead['pickup_city'] ?? '') . ' - ' . ($lead['drop_city'] ?? '') . "\n" .
                ($lead['vehicle_type_required'] ?? '') .
                (!empty($lead['weight']) ? ' · Weight: ' . $lead['weight'] . ' ' . ($lead['weight_unit'] ?? '') : ''),
                " \n·-"
            ),
            'qty'    => $qty,
            'price'  => $rate,
            'total'  => $rate * $qty,
        ]];

        $settings = new \App\Models\SettingModel();
        $grouped  = $settings->getAllGrouped();
        $company  = $grouped['company'] ?? [];

        // Quotation T&Cs — operator maintains these on Settings → Quotation.
        // Stored as a single text field with one term per line.  Empty ⇒ view falls back to
        // the built-in defaults so brand-new installs still print a full T&C section.
        $termsRaw   = trim((string) ($grouped['quotation']['quotation_terms'] ?? ''));
        $termsLines = $termsRaw !== ''
            ? array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $termsRaw)), static fn ($l) => $l !== ''))
            : [];

        $html = view('quotations/client_pdf', [
            'lead'           => $lead,
            'client'         => $client,
            'company'        => $company,
            'items'          => $items,
            'quoteNo'        => 'Q-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT),
            'quotationTerms' => $termsLines,
        ]);

        $options = new \Dompdf\Options();
        $options->setIsRemoteEnabled(true);
        $options->setDefaultFont('Helvetica');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="Quotation_' . ($lead['lead_no'] ?? $id) . '.pdf"')
            ->setBody($dompdf->output());
    }
}

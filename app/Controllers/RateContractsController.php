<?php

namespace App\Controllers;

use App\Models\RateContractModel;
use App\Models\RateContractLaneModel;
use App\Models\ClientModel;

/** Long-term rate cards per client + lane. Gated by feature_flag 'rate_contracts'. */
class RateContractsController extends BaseController
{
    public function index()
    {
        $rows = (new RateContractModel())->withClient()
            ->where('rate_contracts.deleted_at', null)
            ->orderBy('rate_contracts.id', 'DESC')
            ->paginate(25);
        return $this->render('rate_contracts/index', [
            'pageTitle' => 'Rate Contracts',
            'rows'      => $rows,
            'pager'     => (new RateContractModel())->pager,
        ]);
    }

    public function create()
    {
        return $this->render('rate_contracts/form', [
            'pageTitle' => 'New Rate Contract',
            'row'       => null,
            'lanes'     => [],
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ]);
    }

    public function store()
    {
        $model = new RateContractModel();
        $data = [
            'contract_no'   => $this->request->getPost('contract_no') ?: $model->nextContractNo(),
            'client_id'     => (int) $this->request->getPost('client_id'),
            'valid_from'    => $this->request->getPost('valid_from'),
            'valid_to'      => $this->request->getPost('valid_to'),
            'status'        => in_array($this->request->getPost('status'), RateContractModel::STATUSES, true) ? $this->request->getPost('status') : 'Active',
            'tds_rate'      => $this->request->getPost('tds_rate') ?: null,
            'gst_treatment' => $this->request->getPost('gst_treatment') ?: 'fcm5',
            'notes'         => $this->request->getPost('notes'),
            'created_by'    => $this->auth->id(),
        ];
        if (!$data['client_id'] || !$data['valid_from'] || !$data['valid_to']) {
            return redirect()->back()->withInput()->with('error', 'Client and valid-from/to dates are required.');
        }
        $id = $model->insert($data);
        $this->saveLanes((int) $id);
        return redirect()->to(site_url('rate-contracts'))->with('success', 'Contract created.');
    }

    public function edit(int $id)
    {
        $row = (new RateContractModel())->find($id);
        if (!$row) return redirect()->to(site_url('rate-contracts'))->with('error', 'Not found.');
        return $this->render('rate_contracts/form', [
            'pageTitle' => 'Edit ' . $row['contract_no'],
            'row'       => $row,
            'lanes'     => (new RateContractLaneModel())->forContract($id),
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $model = new RateContractModel();
        $old   = $model->find($id);
        if (!$old) return redirect()->to(site_url('rate-contracts'))->with('error', 'Not found.');

        $new = [
            'client_id'     => (int) $this->request->getPost('client_id'),
            'valid_from'    => $this->request->getPost('valid_from'),
            'valid_to'      => $this->request->getPost('valid_to'),
            'status'        => $this->request->getPost('status'),
            'tds_rate'      => $this->request->getPost('tds_rate') ?: null,
            'gst_treatment' => $this->request->getPost('gst_treatment') ?: 'fcm5',
            'notes'         => $this->request->getPost('notes'),
        ];
        $model->update($id, $new);
        $this->saveLanes($id);
        \App\Libraries\AuditLogger::log('rate_contracts', $id, 'updated',
            "Contract " . ($old['contract_no'] ?? '#' . $id) . ' updated', $old, array_merge($old, $new));
        return redirect()->to(site_url('rate-contracts'))->with('success', 'Contract updated.');
    }

    public function delete(int $id)
    {
        $row = (new RateContractModel())->find($id);
        (new RateContractModel())->delete($id);
        if ($row) {
            \App\Libraries\AuditLogger::log('rate_contracts', $id, 'deleted', "Contract " . ($row['contract_no'] ?? '#' . $id) . ' deleted');
        }
        return redirect()->to(site_url('rate-contracts'))->with('success', 'Contract removed.');
    }

    private function saveLanes(int $contractId): void
    {
        $lm = new RateContractLaneModel();
        // Wipe and re-insert for simplicity (lanes are small)
        $lm->where('contract_id', $contractId)->delete();
        $pickups  = (array) $this->request->getPost('lane_pickup');
        $drops    = (array) $this->request->getPost('lane_drop');
        $types    = (array) $this->request->getPost('lane_vehicle');
        $rates    = (array) $this->request->getPost('lane_rate');
        $freeLd   = (array) $this->request->getPost('lane_free_loading');
        $freeUl   = (array) $this->request->getPost('lane_free_unloading');
        $detRate  = (array) $this->request->getPost('lane_detention');

        for ($i = 0, $n = count($pickups); $i < $n; $i++) {
            $p = trim((string) $pickups[$i]); $d = trim((string) ($drops[$i] ?? ''));
            $r = (float) ($rates[$i] ?? 0);
            if ($p === '' || $d === '' || $r <= 0) continue;
            $lm->insert([
                'contract_id'        => $contractId,
                'pickup_city'        => $p,
                'drop_city'          => $d,
                'vehicle_type'       => trim((string) ($types[$i] ?? '')) ?: null,
                'rate_inr'           => $r,
                'free_loading_hrs'   => $freeLd[$i] ?? null,
                'free_unloading_hrs' => $freeUl[$i] ?? null,
                'detention_per_hour' => $detRate[$i] ?? null,
            ]);
        }
    }
}

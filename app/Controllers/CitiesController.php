<?php

namespace App\Controllers;

use App\Models\CityModel;

class CitiesController extends BaseController
{
    /** GET /cities — list + filters + pagination */
    public function index()
    {
        $model   = new CityModel();
        $q       = trim((string) $this->request->getGet('q'));
        $state   = trim((string) $this->request->getGet('state'));
        $region  = trim((string) $this->request->getGet('region'));
        $tier    = trim((string) $this->request->getGet('tier'));
        $perPage = max(10, min(100, (int) ($this->request->getGet('per_page') ?: 25)));

        $query = $model->orderBy('tier', 'ASC')->orderBy('name', 'ASC');
        if ($q !== '') {
            $query->groupStart()
                ->like('name', $q)
                ->orLike('aliases', $q)
                ->orLike('state', $q)
                ->groupEnd();
        }
        if ($state !== '')  $query->where('state', $state);
        if ($region !== '') $query->where('region', $region);
        if ($tier !== '')   $query->where('tier', $tier);

        return $this->render('cities/index', [
            'pageTitle' => 'City Master [General Masters] — List',
            'rows'      => $query->paginate($perPage),
            'pager'     => $model->pager,
            'filters'   => compact('q', 'state', 'region', 'tier'),
            'perPage'   => $perPage,
            'states'    => (new CityModel())->statesList(),
            'regions'   => CityModel::REGIONS,
            'tiers'     => CityModel::TIERS,
        ], retroFixedShell: true);
    }

    /** GET /cities/create */
    public function create()
    {
        if (!$this->auth->can('cities', 'can_add')) {
            return redirect()->to(site_url('cities'))->with('error', 'Not allowed.');
        }
        return $this->render('cities/form', [
            'pageTitle' => 'City Master [General Masters] — New',
            'row'       => null,
            'regions'   => CityModel::REGIONS,
            'tiers'     => CityModel::TIERS,
        ], retroFixedShell: true);
    }

    /** POST /cities/store */
    public function store()
    {
        if (!$this->auth->can('cities', 'can_add')) {
            return redirect()->to(site_url('cities'))->with('error', 'Not allowed.');
        }
        $model = new CityModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->payload();
        $data['created_by'] = $this->auth->id();
        $model->insert($data);
        return redirect()->to(site_url('cities'))->with('success', 'City added.');
    }

    /** GET /cities/{id}/edit */
    public function edit(int $id)
    {
        if (!$this->auth->can('cities', 'can_edit')) {
            return redirect()->to(site_url('cities'))->with('error', 'Not allowed.');
        }
        $row = (new CityModel())->find($id);
        if (!$row) return redirect()->to(site_url('cities'))->with('error', 'Not found.');
        return $this->render('cities/form', [
            'pageTitle' => 'City Master [General Masters] — Edit',
            'row'       => $row,
            'regions'   => CityModel::REGIONS,
            'tiers'     => CityModel::TIERS,
        ], retroFixedShell: true);
    }

    /** POST /cities/{id} */
    public function update(int $id)
    {
        if (!$this->auth->can('cities', 'can_edit')) {
            return redirect()->to(site_url('cities'))->with('error', 'Not allowed.');
        }
        $model = new CityModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $model->update($id, $this->payload());
        return redirect()->to(site_url('cities'))->with('success', 'Updated.');
    }

    /** POST /cities/{id}/delete */
    public function delete(int $id)
    {
        if (!$this->auth->can('cities', 'can_delete')) {
            return redirect()->to(site_url('cities'))->with('error', 'Not allowed.');
        }
        (new CityModel())->delete($id);
        return redirect()->to(site_url('cities'))->with('success', 'Removed.');
    }

    /**
     * GET /cities/lookup?q=mum
     * Light JSON endpoint for the autocomplete widget. Returns
     * [{id, name, state, label}]. No CSRF token needed (GET only).
     */
    public function lookup()
    {
        $q     = trim((string) $this->request->getGet('q'));
        $limit = (int) ($this->request->getGet('limit') ?: 12);
        $limit = max(1, min(25, $limit));

        $rows = (new CityModel())->search($q, $limit);
        $out  = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'    => (int) $r['id'],
                'name'  => $r['name'],
                'state' => $r['state'],
                'tier'  => $r['tier'],
                'label' => $r['name'] . ($r['state'] && $r['state'] !== '— Unknown —' ? ', ' . $r['state'] : ''),
            ];
        }
        return $this->response->setJSON(['data' => $out]);
    }

    private function payload(): array
    {
        $p = $this->request->getPost();
        return [
            'name'           => trim((string) ($p['name'] ?? '')),
            'state'          => trim((string) ($p['state'] ?? '')),
            'gst_state_code' => trim((string) ($p['gst_state_code'] ?? '')) ?: null,
            'region'         => in_array($p['region'] ?? '', CityModel::REGIONS, true) ? $p['region'] : null,
            'tier'           => in_array($p['tier'] ?? '', CityModel::TIERS, true) ? $p['tier'] : null,
            'aliases'        => trim((string) ($p['aliases'] ?? '')) ?: null,
            'pincode_prefix' => trim((string) ($p['pincode_prefix'] ?? '')) ?: null,
            'latitude'       => $p['latitude']  !== '' ? (float) $p['latitude']  : null,
            'longitude'      => $p['longitude'] !== '' ? (float) $p['longitude'] : null,
            'status'         => !empty($p['status']) ? 1 : 0,
        ];
    }
}

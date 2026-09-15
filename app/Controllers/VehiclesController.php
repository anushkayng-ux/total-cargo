<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\VendorModel;

class VehiclesController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $model  = new VehicleModel();
        $query  = $model->select('vehicles.*, vendors.company_name AS vendor_name')
            ->join('vendors', 'vendors.id = vehicles.vendor_id', 'left')
            ->orderBy('vehicles.id', 'DESC');
        if ($search !== '') {
            $query->groupStart()
                ->like('vehicles.vehicle_number', $search)
                ->orLike('vehicles.vehicle_type', $search)
                ->orLike('vehicles.rc_no', $search)
                ->groupEnd();
        }
        return $this->render('vehicles/index', [
            'pageTitle' => 'Vehicle Master [General Masters] — List',
            'rows'      => $query->paginate(20),
            'pager'     => $model->pager,
            'search'    => $search,
        ], retroFixedShell: true);
    }

    /** Read-only vehicle detail page, matching the retro demo's record view. */
    public function show(int $id)
    {
        $row = (new VehicleModel())->select('vehicles.*, vendors.company_name AS vendor_name')
            ->join('vendors', 'vendors.id = vehicles.vendor_id', 'left')
            ->find($id);
        if (!$row) return redirect()->to(site_url('vehicles'))->with('error', 'Not found.');

        $db      = \Config\Database::connect();
        $prevRow = $db->table('vehicles')->select('id')->where('id <', $id)->orderBy('id', 'DESC')->get(1)->getRowArray();
        $nextRow = $db->table('vehicles')->select('id')->where('id >', $id)->orderBy('id', 'ASC')->get(1)->getRowArray();
        $total   = $db->table('vehicles')->countAllResults();

        return $this->render('vehicles/show', [
            'pageTitle' => 'Vehicle Master [General Masters]',
            'row'       => $row,
            'prevId'    => $prevRow['id'] ?? null,
            'nextId'    => $nextRow['id'] ?? null,
            'total'     => $total,
        ], retroFixedShell: true);
    }

    public function create()
    {
        return $this->render('vehicles/form', [
            'pageTitle' => 'Vehicle Master [General Masters] — New',
            'row'       => null,
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function store()
    {
        $model = new VehicleModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $data['status'] = !empty($data['status']) ? 1 : 0;
        if (empty($data['vendor_id'])) $data['vendor_id'] = null;
        foreach (['insurance_expiry', 'fitness_expiry'] as $df) {
            if (empty($data[$df])) $data[$df] = null;
        }
        $model->insert($data);
        return redirect()->to(site_url('vehicles'))->with('success', 'Vehicle added.');
    }

    public function edit(int $id)
    {
        $row = (new VehicleModel())->find($id);
        if (!$row) return redirect()->to(site_url('vehicles'))->with('error', 'Not found.');
        return $this->render('vehicles/form', [
            'pageTitle' => 'Vehicle Master [General Masters] — Edit',
            'row'       => $row,
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function update(int $id)
    {
        $model = new VehicleModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $data['status'] = !empty($data['status']) ? 1 : 0;
        if (empty($data['vendor_id'])) $data['vendor_id'] = null;
        foreach (['insurance_expiry', 'fitness_expiry'] as $df) {
            if (empty($data[$df])) $data[$df] = null;
        }
        $model->update($id, $data);
        return redirect()->to(site_url('vehicles'))->with('success', 'Vehicle updated.');
    }

    public function delete(int $id)
    {
        (new VehicleModel())->delete($id);
        return redirect()->to(site_url('vehicles'))->with('success', 'Vehicle removed.');
    }
}

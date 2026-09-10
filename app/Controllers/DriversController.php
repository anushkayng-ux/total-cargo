<?php

namespace App\Controllers;

use App\Models\DriverModel;
use App\Models\VendorModel;

class DriversController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $model  = new DriverModel();
        $query  = $model->select('drivers.*, vendors.company_name AS vendor_name')
            ->join('vendors', 'vendors.id = drivers.vendor_id', 'left')
            ->orderBy('drivers.id', 'DESC');
        if ($search !== '') {
            $query->groupStart()
                ->like('drivers.driver_name', $search)
                ->orLike('drivers.mobile', $search)
                ->orLike('drivers.license_no', $search)
                ->groupEnd();
        }
        return $this->render('drivers/index', [
            'pageTitle' => 'Drivers',
            'rows'      => $query->paginate($this->perPage()),
            'pager'     => $model->pager,
            'search'    => $search,
        ]);
    }

    public function create()
    {
        return $this->render('drivers/form', [
            'pageTitle' => 'Add Driver',
            'row'       => null,
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ]);
    }

    public function store()
    {
        $model = new DriverModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $data['status'] = !empty($data['status']) ? 1 : 0;
        if (empty($data['vendor_id'])) $data['vendor_id'] = null;
        if (empty($data['license_expiry'])) $data['license_expiry'] = null;
        $model->insert($data);
        return redirect()->to(site_url('drivers'))->with('success', 'Driver added.');
    }

    public function edit(int $id)
    {
        $row = (new DriverModel())->find($id);
        if (!$row) return redirect()->to(site_url('drivers'))->with('error', 'Not found.');
        return $this->render('drivers/form', [
            'pageTitle' => 'Edit Driver',
            'row'       => $row,
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $model = new DriverModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $data['status'] = !empty($data['status']) ? 1 : 0;
        if (empty($data['vendor_id'])) $data['vendor_id'] = null;
        if (empty($data['license_expiry'])) $data['license_expiry'] = null;
        $model->update($id, $data);
        return redirect()->to(site_url('drivers'))->with('success', 'Driver updated.');
    }

    public function delete(int $id)
    {
        (new DriverModel())->delete($id);
        return redirect()->to(site_url('drivers'))->with('success', 'Driver removed.');
    }

    /** Mark KYC verified (or rejected). Required before any vehicle assignment. */
    public function verifyKyc(int $id)
    {
        $row = (new DriverModel())->find($id);
        if (!$row) return redirect()->to(site_url('drivers'))->with('error', 'Not found.');

        $action = (string) $this->request->getPost('action');   // 'verify' | 'reject'
        $notes  = trim((string) $this->request->getPost('kyc_notes'));
        $status = $action === 'reject' ? 'Rejected' : 'Verified';

        (new DriverModel())->update($id, [
            'kyc_status'      => $status,
            'kyc_verified_at' => date('Y-m-d H:i:s'),
            'kyc_verified_by' => $this->auth->id(),
            'kyc_notes'       => $notes ?: null,
        ]);
        return redirect()->to(site_url('drivers/' . $id . '/edit'))
            ->with('success', "Driver KYC {$status}.");
    }
}

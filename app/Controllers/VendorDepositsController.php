<?php

namespace App\Controllers;

use App\Models\VendorDepositModel;
use App\Models\VendorModel;

/** Vendor security deposit ledger. Gated by feature_flag 'vendor_deposits'. */
class VendorDepositsController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $rows = $db->query("
            SELECT v.id, v.vendor_code, v.company_name,
                   COALESCE((SELECT balance_after FROM vendor_deposits d
                              WHERE d.vendor_id = v.id ORDER BY d.id DESC LIMIT 1), 0) AS balance,
                   (SELECT COUNT(*) FROM vendor_deposits d WHERE d.vendor_id = v.id) AS txn_count
              FROM vendors v
             WHERE v.deleted_at IS NULL
             ORDER BY balance DESC, v.company_name ASC
        ")->getResultArray();

        return $this->render('vendor_deposits/index', [
            'pageTitle' => 'Vendor Deposit Master [Purchase] — List',
            'rows'      => $rows,
        ], retroFixedShell: true);
    }

    public function ledger(int $vendorId)
    {
        $vendor = (new VendorModel())->find($vendorId);
        if (!$vendor) return redirect()->to(site_url('vendor-deposits'))->with('error', 'Not found.');
        $model = new VendorDepositModel();
        return $this->render('vendor_deposits/ledger', [
            'pageTitle' => 'Vendor Deposit Master [Purchase]',
            'vendor'    => $vendor,
            'rows'      => $model->ledger($vendorId),
            'balance'   => $model->balance($vendorId),
        ], retroFixedShell: true);
    }

    public function record(int $vendorId)
    {
        if (!(new VendorModel())->find($vendorId)) {
            return redirect()->to(site_url('vendor-deposits'))->with('error', 'Vendor not found.');
        }
        $type   = $this->request->getPost('txn_type');
        $amount = (float) $this->request->getPost('amount');
        if (!in_array($type, VendorDepositModel::TYPES, true) || $amount <= 0) {
            return redirect()->back()->with('error', 'Invalid type or amount.');
        }
        (new VendorDepositModel())->record($vendorId, $type, $amount, [
            'reason'         => trim((string) $this->request->getPost('reason')),
            'reference_no'   => trim((string) $this->request->getPost('reference_no')) ?: null,
            'reference_trip_id' => $this->request->getPost('reference_trip_id') ?: null,
            'txn_date'       => $this->request->getPost('txn_date') ?: date('Y-m-d'),
            'created_by'     => $this->auth->id(),
        ]);
        return redirect()->to(site_url('vendor-deposits/' . $vendorId))->with('success', "{$type} of ₹" . number_format($amount, 2) . ' recorded.');
    }
}

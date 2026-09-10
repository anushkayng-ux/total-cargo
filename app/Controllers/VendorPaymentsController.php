<?php

namespace App\Controllers;

use App\Models\VendorPaymentModel;
use App\Models\VendorBillModel;

class VendorPaymentsController extends BaseController
{
    public function index()
    {
        $search  = trim((string) $this->request->getGet('q'));
        $mode    = (string) $this->request->getGet('mode');
        $from    = (string) $this->request->getGet('from');
        $to      = (string) $this->request->getGet('to');
        $perPage = max(10, min(100, (int) ($this->request->getGet('per_page') ?? 30)));

        $model = new VendorPaymentModel();
        $q = $model->withJoins()->orderBy('vendor_payments.id', 'DESC');
        if ($search !== '') {
            $q->groupStart()
                ->like('vendor_payments.reference_no', $search)
                ->orLike('vendors.company_name', $search)
                ->orLike('vendor_bills.bill_no', $search)
                ->orLike('vendor_payments.notes', $search)
              ->groupEnd();
        }
        if ($mode !== '') $q->where('vendor_payments.payment_mode', $mode);
        if ($from !== '') $q->where('vendor_payments.payment_date >=', date('Y-m-d', strtotime($from)));
        if ($to   !== '') $q->where('vendor_payments.payment_date <=', date('Y-m-d', strtotime($to)));

        // Ownership scope — vendor payments link to vendor_bills → trips → bookings → clients.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            $q->join('trips t', 't.id = vendor_bills.trip_id', 'left')
              ->join('bookings b', 'b.id = t.booking_id', 'left')
              ->whereIn('b.client_id', $ownedIds ?: [0]);
        }

        $rows = $q->paginate($perPage);
        return $this->render('vendor_payments/index', [
            'pageTitle' => 'Vendor Payments',
            'rows'      => $rows,
            'pager'     => $model->pager,
            'filters'   => compact('search','mode','from','to','perPage'),
        ]);
    }

    public function store()
    {
        $post  = $this->request->getPost();
        $rules = [
            'vendor_id'    => 'required|integer',
            'payment_date' => 'required|valid_date',
            'amount_paid'  => 'required|numeric|greater_than[0]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $billId = !empty($post['vendor_bill_id']) ? (int) $post['vendor_bill_id'] : null;
        (new VendorPaymentModel())->insert([
            'vendor_id'      => (int) $post['vendor_id'],
            'vendor_bill_id' => $billId,
            'payment_date'   => $post['payment_date'],
            'payment_mode'   => $post['payment_mode'] ?? null,
            'amount_paid'    => (float) $post['amount_paid'],
            'reference_no'   => $post['reference_no'] ?? null,
            'notes'          => $post['notes'] ?? null,
            'created_by'     => $this->auth->id(),
        ]);

        // Notify Accounts a vendor payment went out.
        \App\Libraries\Notify::toPermission('vendor_payments', 'can_view',
            'Vendor paid — ₹' . number_format((float) $post['amount_paid'], 2),
            'Vendor payment recorded' . ($billId ? ' against a bill' : ''),
            $billId ? site_url('vendor-bills/' . $billId) : site_url('vendor-payments'),
            ['type' => 'vendor_paid', 'icon' => 'cash-coin']);

        if ($billId) {
            (new VendorBillModel())->recomputeBalance($billId);
            return redirect()->to(site_url('vendor-bills/' . $billId))->with('success', 'Payment recorded.');
        }
        return redirect()->to(site_url('vendor-payments'))->with('success', 'Payment recorded.');
    }

    public function delete(int $id)
    {
        $p = (new VendorPaymentModel())->find($id);
        if (!$p) return redirect()->to(site_url('vendor-payments'))->with('error', 'Not found.');
        (new VendorPaymentModel())->delete($id);
        if (!empty($p['vendor_bill_id'])) {
            (new VendorBillModel())->recomputeBalance((int) $p['vendor_bill_id']);
            return redirect()->to(site_url('vendor-bills/' . (int) $p['vendor_bill_id']))->with('success', 'Payment removed.');
        }
        return redirect()->to(site_url('vendor-payments'))->with('success', 'Payment removed.');
    }
}

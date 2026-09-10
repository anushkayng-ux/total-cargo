<?php

namespace App\Controllers;

use App\Models\ReceiptModel;
use App\Models\InvoiceModel;

class ReceiptsController extends BaseController
{
    public function index()
    {
        $search  = trim((string) $this->request->getGet('q'));
        $mode    = (string) $this->request->getGet('mode');
        $from    = (string) $this->request->getGet('from');
        $to      = (string) $this->request->getGet('to');
        $perPage = max(10, min(100, (int) ($this->request->getGet('per_page') ?? 30)));

        $model = new ReceiptModel();
        $q = $model->withJoins()->orderBy('receipts.id', 'DESC');
        if ($search !== '') {
            $q->groupStart()
                ->like('receipts.reference_no', $search)
                ->orLike('clients.company_name', $search)
                ->orLike('invoices.invoice_no', $search)
                ->orLike('receipts.notes', $search)
              ->groupEnd();
        }
        if ($mode !== '') $q->where('receipts.payment_mode', $mode);
        if ($from !== '') $q->where('receipts.receipt_date >=', date('Y-m-d', strtotime($from)));
        if ($to   !== '') $q->where('receipts.receipt_date <=', date('Y-m-d', strtotime($to)));

        // Ownership scope — non-admins only see receipts for their assigned clients.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) $q->whereIn('receipts.client_id', $ownedIds ?: [0]);

        $rows = $q->paginate($perPage);
        return $this->render('receipts/index', [
            'pageTitle' => 'Client Receipts',
            'rows'      => $rows,
            'pager'     => $model->pager,
            'filters'   => compact('search','mode','from','to','perPage'),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $rules = [
            'client_id'       => 'required|integer',
            'receipt_date'    => 'required|valid_date',
            'amount_received' => 'required|numeric|greater_than[0]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $invoiceId = !empty($post['invoice_id']) ? (int) $post['invoice_id'] : null;
        (new ReceiptModel())->insert([
            'client_id'       => (int) $post['client_id'],
            'invoice_id'      => $invoiceId,
            'receipt_date'    => $post['receipt_date'],
            'payment_mode'    => $post['payment_mode'] ?? null,
            'amount_received' => (float) $post['amount_received'],
            'reference_no'    => $post['reference_no'] ?? null,
            'notes'           => $post['notes']        ?? null,
            'created_by'      => $this->auth->id(),
        ]);

        // Notify Accounts + Management that a payment came in.
        \App\Libraries\Notify::toPermission('receipts', 'can_view',
            'Payment received — ₹' . number_format((float) $post['amount_received'], 2),
            'Receipt recorded' . ($invoiceId ? ' against an invoice' : ''),
            $invoiceId ? site_url('invoices/' . $invoiceId) : site_url('receipts'),
            ['type' => 'payment_received', 'icon' => 'cash-stack']);

        if ($invoiceId) {
            (new InvoiceModel())->recomputeBalance($invoiceId);
            return redirect()->to(site_url('invoices/' . $invoiceId))->with('success', 'Receipt recorded.');
        }
        return redirect()->to(site_url('receipts'))->with('success', 'Receipt recorded.');
    }

    public function delete(int $id)
    {
        $r = (new ReceiptModel())->find($id);
        if (!$r) return redirect()->to(site_url('receipts'))->with('error', 'Not found.');
        (new ReceiptModel())->delete($id);
        if (!empty($r['invoice_id'])) {
            (new InvoiceModel())->recomputeBalance((int) $r['invoice_id']);
            return redirect()->to(site_url('invoices/' . (int) $r['invoice_id']))->with('success', 'Receipt removed.');
        }
        return redirect()->to(site_url('receipts'))->with('success', 'Receipt removed.');
    }
}

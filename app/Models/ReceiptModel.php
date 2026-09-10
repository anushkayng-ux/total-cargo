<?php

namespace App\Models;

class ReceiptModel extends BaseModel
{
    protected $table          = 'receipts';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = false;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'client_id', 'invoice_id', 'receipt_date', 'payment_mode',
        'amount_received', 'reference_no', 'notes', 'created_by',
    ];

    public function withJoins()
    {
        return $this->select('receipts.*, clients.company_name AS client_company, invoices.invoice_no')
            ->join('clients',  'clients.id  = receipts.client_id',  'left')
            ->join('invoices', 'invoices.id = receipts.invoice_id', 'left');
    }

    public function forInvoice(int $invoiceId): array
    {
        return $this->select('receipts.*, u.name AS created_by_name')
            ->join('users u', 'u.id = receipts.created_by', 'left')
            ->where('invoice_id', $invoiceId)
            ->orderBy('receipt_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}

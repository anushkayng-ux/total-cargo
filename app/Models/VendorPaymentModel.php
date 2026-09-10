<?php

namespace App\Models;

class VendorPaymentModel extends BaseModel
{
    protected $table          = 'vendor_payments';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = false;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'vendor_id', 'vendor_bill_id', 'payment_date', 'payment_mode',
        'amount_paid', 'reference_no', 'notes', 'created_by',
    ];

    public function withJoins()
    {
        return $this->select('vendor_payments.*, vendors.company_name AS vendor_company, vendor_bills.bill_no')
            ->join('vendors',      'vendors.id      = vendor_payments.vendor_id',      'left')
            ->join('vendor_bills', 'vendor_bills.id = vendor_payments.vendor_bill_id', 'left');
    }

    public function forBill(int $billId): array
    {
        return $this->select('vendor_payments.*, u.name AS created_by_name')
            ->join('users u', 'u.id = vendor_payments.created_by', 'left')
            ->where('vendor_bill_id', $billId)
            ->orderBy('payment_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}

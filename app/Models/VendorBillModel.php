<?php

namespace App\Models;

class VendorBillModel extends BaseModel
{
    protected $table         = 'vendor_bills';
    protected $primaryKey    = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'vendor_id', 'trip_id', 'bill_no', 'bill_date',
        'bill_amount', 'amount_paid', 'balance_due', 'due_date',
        'status', 'document_id', 'notes', 'created_by',
    ];

    public const STATUSES = ['Open', 'Partially Paid', 'Paid', 'Cancelled'];

    public function withJoins()
    {
        return $this->select('vendor_bills.*, vendors.company_name AS vendor_company, trips.trip_no, trips.vehicle_number')
            ->join('vendors', 'vendors.id = vendor_bills.vendor_id', 'left')
            ->join('trips',   'trips.id   = vendor_bills.trip_id',   'left');
    }

    public function recomputeBalance(int $id): void
    {
        $db   = \Config\Database::connect();
        $bill = $this->find($id);
        if (!$bill) return;

        $paid    = (float) ($db->table('vendor_payments')->selectSum('amount_paid', 'total')->where('vendor_bill_id', $id)->get()->getRow('total') ?? 0);
        $amount  = (float) $bill['bill_amount'];
        $balance = round($amount - $paid, 2);

        $status = $bill['status'];
        if ($status !== 'Cancelled') {
            if ($paid <= 0.01)                $status = 'Open';
            elseif ($paid + 0.01 >= $amount)  $status = 'Paid';
            else                               $status = 'Partially Paid';
        }

        $this->update($id, [
            'amount_paid' => round($paid, 2),
            'balance_due' => $balance,
            'status'      => $status,
        ]);
    }
}

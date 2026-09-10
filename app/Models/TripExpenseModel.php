<?php

namespace App\Models;

class TripExpenseModel extends BaseModel
{
    protected $table         = 'trip_expenses';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'trip_id', 'expense_date', 'category', 'description', 'amount',
        'is_billable', 'billed_on_invoice_id',
        'paid_to', 'payment_mode', 'reference_no', 'document_id',
        'remarks', 'created_by', 'updated_by',
    ];

    protected $validationRules = [
        'trip_id' => 'required|integer',
        'amount'  => 'required|numeric|greater_than[0]',
        'paid_to' => 'permit_empty|in_list[Driver,Vendor,Direct,Self]',
    ];

    public function withJoins()
    {
        return $this->select('trip_expenses.*, trips.trip_no, trips.vehicle_number, trips.booking_id,
                              b.booking_no, c.company_name AS client_company, v.company_name AS vendor_company,
                              i.invoice_no, u.name AS created_by_name')
            ->join('trips',    'trips.id    = trip_expenses.trip_id',          'left')
            ->join('bookings b','b.id       = trips.booking_id',                'left')
            ->join('clients c',  'c.id      = b.client_id',                    'left')
            ->join('vendors v',  'v.id      = b.vendor_id',                    'left')
            ->join('invoices i', 'i.id      = trip_expenses.billed_on_invoice_id', 'left')
            ->join('users u',    'u.id      = trip_expenses.created_by',       'left');
    }

    public function forTrip(int $tripId): array
    {
        return $this->select('trip_expenses.*, u.name AS created_by_name, d.original_file_name AS receipt_name, i.invoice_no AS billed_on_invoice_no')
            ->join('users u',    'u.id = trip_expenses.created_by', 'left')
            ->join('documents d','d.id = trip_expenses.document_id', 'left')
            ->join('invoices i', 'i.id = trip_expenses.billed_on_invoice_id', 'left')
            ->where('trip_expenses.trip_id', $tripId)
            ->where('trip_expenses.deleted_at IS NULL')
            ->orderBy('trip_expenses.expense_date', 'DESC')
            ->orderBy('trip_expenses.id', 'DESC')
            ->findAll();
    }

    public function totalsForTrip(int $tripId): array
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('trip_expenses')
            ->select('is_billable, COALESCE(SUM(amount),0) AS total, COUNT(*) AS c')
            ->where('trip_id', $tripId)
            ->where('deleted_at IS NULL')
            ->groupBy('is_billable')
            ->get()->getResultArray();

        $internal = 0.0; $billable = 0.0; $internalCount = 0; $billableCount = 0;
        foreach ($rows as $r) {
            if ((int) $r['is_billable'] === 1) { $billable += (float) $r['total']; $billableCount = (int) $r['c']; }
            else                                { $internal += (float) $r['total']; $internalCount = (int) $r['c']; }
        }

        $unbilledBillable = (float) ($db->table('trip_expenses')
            ->selectSum('amount', 't')
            ->where('trip_id', $tripId)
            ->where('is_billable', 1)
            ->where('billed_on_invoice_id IS NULL')
            ->where('deleted_at IS NULL')
            ->get()->getRow('t') ?? 0);

        return [
            'internal'          => round($internal, 2),
            'billable'          => round($billable, 2),
            'unbilled_billable' => round($unbilledBillable, 2),
            'internal_count'    => $internalCount,
            'billable_count'    => $billableCount,
        ];
    }

    public function unbilledBillableForTrip(int $tripId): array
    {
        return $this->where('trip_id', $tripId)
            ->where('is_billable', 1)
            ->where('billed_on_invoice_id IS NULL')
            ->where('deleted_at IS NULL')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}

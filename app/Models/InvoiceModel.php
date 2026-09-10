<?php

namespace App\Models;

class InvoiceModel extends BaseModel
{
    protected $table         = 'invoices';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'invoice_no', 'invoice_date', 'client_id', 'booking_id', 'trip_id',
        'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'gst_treatment', 'detention_amount', 'tds_rate', 'tds_amount', 'net_receivable',
        'round_off', 'total_amount', 'amount_received', 'balance_due',
        'due_date', 'invoice_status', 'pdf_path',
        'irn_no', 'ack_no', 'ack_date',
        'notes', 'payment_terms', 'created_by', 'updated_by',
    ];

    public const STATUSES = ['Draft', 'Issued', 'Partially Paid', 'Paid', 'Cancelled'];

    public function withJoins()
    {
        return $this->select('invoices.*,
                              clients.company_name AS client_company, clients.gst_no AS client_gstin, clients.state AS client_state,
                              bookings.booking_no, bookings.route_text, bookings.pickup_city, bookings.drop_city,
                              bookings.consignor_name, bookings.consignor_gstin, bookings.consignor_address,
                              bookings.consignee_name, bookings.consignee_gstin, bookings.consignee_address,
                              bookings.shipper_invoices_json, bookings.invoice_number AS booking_invoice_number,
                              trips.trip_no, trips.lr_no, trips.lr_generated_at, trips.vehicle_number')
            ->join('clients', 'clients.id = invoices.client_id', 'left')
            ->join('bookings', 'bookings.id = invoices.booking_id', 'left')
            ->join('trips', 'trips.id = invoices.trip_id', 'left');
    }

    /** Recompute balance + status from current receipts. */
    public function recomputeBalance(int $id): void
    {
        $db  = \Config\Database::connect();
        $inv = $this->find($id);
        if (!$inv) return;

        $received = (float) ($db->table('receipts')->selectSum('amount_received', 'total')->where('invoice_id', $id)->get()->getRow('total') ?? 0);
        $total    = (float) $inv['total_amount'];
        $balance  = round($total - $received, 2);

        $status = $inv['invoice_status'];
        if (!in_array($status, ['Draft', 'Cancelled'], true)) {
            if ($received <= 0.01)                 $status = 'Issued';
            elseif ($received + 0.01 >= $total)    $status = 'Paid';
            else                                    $status = 'Partially Paid';
        }

        $this->update($id, [
            'amount_received' => round($received, 2),
            'balance_due'     => $balance,
            'invoice_status'  => $status,
        ]);
    }
}

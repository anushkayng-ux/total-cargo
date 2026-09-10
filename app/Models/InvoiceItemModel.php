<?php

namespace App\Models;

class InvoiceItemModel extends BaseModel
{
    protected $table          = 'invoice_items';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = false;
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'invoice_id', 'description', 'hsn_sac', 'qty', 'rate',
        'taxable_amount', 'gst_percent', 'gst_amount', 'total_amount',
        'trip_id',
    ];

    public function forInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)->orderBy('id', 'ASC')->findAll();
    }

    /**
     * IDs of trips already used on any invoice line — the source of truth
     * for "billed vs unbilled". Callers use this to exclude billed trips
     * from consolidated-billing selection screens.
     */
    public function billedTripIds(): array
    {
        $ids = [];
        // Rows on the line-item level (consolidated flow).
        $rows = $this->select('trip_id')->where('trip_id IS NOT NULL')->findAll();
        foreach ($rows as $r) $ids[] = (int) $r['trip_id'];

        // Header-level link (single-trip flow via createFromTrip). Any invoice
        // that isn't Cancelled counts as billed for exclusion purposes.
        $hdrs = \Config\Database::connect()
            ->table('invoices')
            ->select('trip_id')
            ->where('trip_id IS NOT NULL')
            ->where('invoice_status !=', 'Cancelled')
            ->get()->getResultArray();
        foreach ($hdrs as $h) $ids[] = (int) $h['trip_id'];

        return array_values(array_unique(array_filter($ids)));
    }
}

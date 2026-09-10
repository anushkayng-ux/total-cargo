<?php

namespace App\Models;

class QuotationModel extends BaseModel
{
    protected $table          = 'quotations';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'rfq_id', 'vendor_id', 'quote_amount',
        'availability_notes', 'transit_days', 'quote_valid_till',
        'response_source', 'is_shortlisted', 'is_final_selected',
        'response_time_minutes', 'remarks',
    ];

    public function forRfq(int $rfqId): array
    {
        return $this->select('quotations.*, vendors.company_name, vendors.owner_name, vendors.mobile, vendors.whatsapp_no, vendors.rating, vendors.is_preferred, vendors.is_blacklisted')
            ->join('vendors', 'vendors.id = quotations.vendor_id', 'inner')
            ->where('rfq_id', $rfqId)
            ->orderBy('quotations.quote_amount', 'ASC')
            ->findAll();
    }
}

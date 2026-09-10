<?php

namespace App\Models;

class RfqVendorModel extends BaseModel
{
    protected $table          = 'rfq_vendors';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'rfq_id', 'vendor_id', 'whatsapp_message_id',
        'sent_at', 'reminder_count', 'last_reminder_at',
        'response_status', 'responded_at',
    ];

    public function forRfq(int $rfqId): array
    {
        return $this->select('rfq_vendors.*, vendors.company_name, vendors.owner_name, vendors.mobile, vendors.whatsapp_no, vendors.is_preferred, vendors.rating')
            ->join('vendors', 'vendors.id = rfq_vendors.vendor_id', 'inner')
            ->where('rfq_id', $rfqId)
            ->orderBy('rfq_vendors.id', 'ASC')
            ->findAll();
    }
}

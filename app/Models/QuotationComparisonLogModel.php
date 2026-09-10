<?php

namespace App\Models;

class QuotationComparisonLogModel extends BaseModel
{
    protected $table          = 'quotation_comparison_logs';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['rfq_id', 'selected_vendor_id', 'logic_notes', 'created_by', 'created_at'];

    public function log(int $rfqId, ?int $vendorId, string $notes, ?int $userId): void
    {
        $this->insert([
            'rfq_id' => $rfqId,
            'selected_vendor_id' => $vendorId,
            'logic_notes' => $notes,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

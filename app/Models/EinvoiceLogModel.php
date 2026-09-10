<?php

namespace App\Models;

class EinvoiceLogModel extends BaseModel
{
    protected $table          = 'einvoice_logs';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = false;
    protected $updatedField   = '';
    protected $allowedFields  = ['invoice_id', 'request_payload', 'response_payload', 'irn_status', 'created_at'];
}

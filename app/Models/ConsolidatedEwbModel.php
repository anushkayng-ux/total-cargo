<?php

namespace App\Models;

class ConsolidatedEwbModel extends BaseModel
{
    protected $table          = 'ewb_consolidated';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'consol_no', 'vehicle_no', 'from_state', 'trip_ids_json',
        'generated_at', 'valid_until', 'status', 'raw_payload', 'created_by',
    ];
}

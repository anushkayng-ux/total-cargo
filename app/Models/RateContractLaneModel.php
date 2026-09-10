<?php

namespace App\Models;

class RateContractLaneModel extends BaseModel
{
    protected $table          = 'rate_contract_lanes';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'contract_id', 'pickup_city', 'drop_city', 'vehicle_type', 'rate_inr',
        'min_load_tons', 'free_loading_hrs', 'free_unloading_hrs',
        'detention_per_hour', 'notes',
    ];

    public function forContract(int $contractId): array
    {
        return $this->where('contract_id', $contractId)
            ->orderBy('pickup_city', 'ASC')->orderBy('drop_city', 'ASC')
            ->find();
    }
}

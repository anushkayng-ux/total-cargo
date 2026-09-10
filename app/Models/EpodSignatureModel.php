<?php

namespace App\Models;

class EpodSignatureModel extends BaseModel
{
    protected $table          = 'epod_signatures';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'trip_id', 'consignee_name', 'consignee_mobile', 'signed_at',
        'signature_data', 'remarks', 'damage_noted', 'shortage_noted',
        'ip_address', 'user_agent', 'geo_lat', 'geo_lng',
    ];

    public function forTrip(int $tripId): ?array
    {
        return $this->where('trip_id', $tripId)->orderBy('id', 'DESC')->first();
    }
}

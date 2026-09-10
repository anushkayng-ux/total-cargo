<?php

namespace App\Models;

class InsuranceQuoteModel extends BaseModel
{
    protected $table          = 'insurance_quotes';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'trip_id', 'cargo_value', 'premium', 'provider', 'policy_no',
        'valid_from', 'valid_until', 'raw_payload', 'status', 'created_by',
    ];

    public function forTrip(int $tripId): array
    {
        return $this->where('trip_id', $tripId)->orderBy('id', 'DESC')->find();
    }
}

<?php

namespace App\Models;

class TripStopModel extends BaseModel
{
    protected $table          = 'trip_stops';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'trip_id', 'sequence', 'stop_type',
        'address', 'city', 'contact_name', 'contact_mobile', 'gstin',
        'planned_at', 'arrived_at', 'departed_at', 'notes',
    ];

    public const TYPES = ['pickup', 'drop'];

    public function forTrip(int $tripId): array
    {
        return $this->where('trip_id', $tripId)
            ->orderBy('sequence', 'ASC')
            ->orderBy('id', 'ASC')
            ->find();
    }
}

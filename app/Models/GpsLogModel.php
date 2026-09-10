<?php

namespace App\Models;

class GpsLogModel extends BaseModel
{
    protected $table          = 'gps_logs';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'trip_id', 'vehicle_number', 'latitude', 'longitude',
        'gps_timestamp', 'speed', 'address', 'raw_payload', 'source',
    ];

    public function forTrip(int $tripId, int $limit = 500): array
    {
        return $this->where('trip_id', $tripId)
            ->orderBy('gps_timestamp', 'ASC')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->findAll();
    }
}

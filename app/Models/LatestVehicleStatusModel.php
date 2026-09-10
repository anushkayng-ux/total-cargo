<?php

namespace App\Models;

class LatestVehicleStatusModel extends BaseModel
{
    protected $table          = 'latest_vehicle_status';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $createdField   = '';
    protected $updatedField   = 'updated_at';
    protected $allowedFields  = [
        'trip_id', 'vehicle_number', 'latitude', 'longitude',
        'gps_timestamp', 'speed', 'address', 'eta_text', 'delay_flag', 'source',
    ];

    public function getByVehicle(string $vehicleNumber): ?array
    {
        return $this->where('vehicle_number', $vehicleNumber)->first();
    }

    public function upsert(string $vehicleNumber, array $data): int
    {
        $vehicleNumber        = strtoupper(preg_replace('/\s+/', '', $vehicleNumber));
        $data['vehicle_number'] = $vehicleNumber;
        $existing = $this->getByVehicle($vehicleNumber);
        if ($existing) {
            $this->update($existing['id'], $data);
            return (int) $existing['id'];
        }
        return (int) $this->insert($data);
    }
}

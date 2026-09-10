<?php

namespace App\Models;

class RateContractModel extends BaseModel
{
    protected $table         = 'rate_contracts';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'contract_no', 'client_id', 'valid_from', 'valid_to', 'status',
        'tds_rate', 'gst_treatment', 'notes', 'created_by',
    ];

    public const STATUSES = ['Active','Expired','Suspended','Draft'];

    public function nextContractNo(): string
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $n = $last ? ((int) $last['id']) + 1 : 1;
        return 'RC' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    public function withClient()
    {
        return $this->select('rate_contracts.*, clients.company_name AS client_company')
            ->join('clients', 'clients.id = rate_contracts.client_id', 'left');
    }

    /** Find an active rate for a client + lane + vehicle type. */
    public function findRate(int $clientId, string $pickup, string $drop, ?string $vehicleType = null): ?array
    {
        $today = date('Y-m-d');
        $sql = "SELECT l.* FROM rate_contract_lanes l
                  JOIN rate_contracts c ON c.id = l.contract_id
                 WHERE c.client_id = ? AND c.status = 'Active'
                   AND c.valid_from <= ? AND c.valid_to >= ?
                   AND c.deleted_at IS NULL
                   AND LOWER(l.pickup_city) = LOWER(?)
                   AND LOWER(l.drop_city)   = LOWER(?)";
        $args = [$clientId, $today, $today, trim($pickup), trim($drop)];
        if ($vehicleType) { $sql .= " AND (l.vehicle_type IS NULL OR LOWER(l.vehicle_type) = LOWER(?))"; $args[] = trim($vehicleType); }
        $sql .= " ORDER BY l.id DESC LIMIT 1";
        return \Config\Database::connect()->query($sql, $args)->getRowArray();
    }
}

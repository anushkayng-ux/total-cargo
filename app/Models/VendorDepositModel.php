<?php

namespace App\Models;

class VendorDepositModel extends BaseModel
{
    protected $table          = 'vendor_deposits';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'vendor_id', 'txn_type', 'amount', 'balance_after',
        'reference_trip_id', 'reason', 'txn_date', 'reference_no',
        'created_by',
    ];

    public const TYPES = ['Deposit','Release','Forfeit'];

    public function balance(int $vendorId): float
    {
        $rows = $this->where('vendor_id', $vendorId)->orderBy('id', 'DESC')->limit(1)->find();
        return $rows ? (float) $rows[0]['balance_after'] : 0.0;
    }

    public function ledger(int $vendorId): array
    {
        return $this->where('vendor_id', $vendorId)->orderBy('id', 'ASC')->find();
    }

    /** Append a new ledger entry, computing balance_after correctly. */
    public function record(int $vendorId, string $type, float $amount, array $extra = []): int
    {
        $bal = $this->balance($vendorId);
        if ($type === 'Deposit')   $bal += $amount;
        elseif ($type === 'Release') $bal -= $amount;
        elseif ($type === 'Forfeit') $bal -= $amount;
        else throw new \InvalidArgumentException('Unknown txn type ' . $type);

        $row = array_merge($extra, [
            'vendor_id'     => $vendorId,
            'txn_type'      => $type,
            'amount'        => round($amount, 2),
            'balance_after' => round($bal, 2),
            'txn_date'      => $extra['txn_date'] ?? date('Y-m-d'),
        ]);
        return (int) $this->insert($row);
    }
}

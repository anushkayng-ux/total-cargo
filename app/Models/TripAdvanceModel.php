<?php

namespace App\Models;

class TripAdvanceModel extends BaseModel
{
    protected $table          = 'trip_advances';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'trip_id', 'amount', 'mode', 'reference_no',
        'given_to', 'given_at', 'given_by',
        'settled_at', 'settled_amount', 'notes',
    ];

    public const MODES = ['Cash','UPI','Bank Transfer','FuelCard','FastTag'];

    public function forTrip(int $tripId): array
    {
        return $this->where('trip_id', $tripId)
            ->orderBy('id', 'ASC')
            ->find();
    }

    public function totalsForTrip(int $tripId): array
    {
        $rows = $this->forTrip($tripId);
        $given = 0; $settled = 0;
        foreach ($rows as $r) {
            $given   += (float) $r['amount'];
            $settled += (float) $r['settled_amount'];
        }
        return [
            'given'        => round($given, 2),
            'settled'      => round($settled, 2),
            'outstanding'  => round($given - $settled, 2),
        ];
    }
}

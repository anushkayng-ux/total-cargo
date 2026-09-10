<?php

namespace App\Models;

class LoadingSlotModel extends BaseModel
{
    protected $table          = 'loading_slots';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'booking_id', 'plant_name', 'slot_date',
        'slot_window_start', 'slot_window_end', 'gate_pass_no',
        'status', 'requested_at', 'confirmed_at', 'used_at', 'notes', 'created_by',
    ];

    public const STATUSES = ['Requested','Confirmed','Cancelled','Used','Missed'];

    public function forBooking(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)->orderBy('slot_date', 'ASC')->find();
    }
}

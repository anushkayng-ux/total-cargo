<?php

namespace App\Models;

class TripModel extends BaseModel
{
    protected $table         = 'trips';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'trip_no', 'lr_no', 'lr_generated_at',
        'ewb_no', 'ewb_date', 'ewb_valid_until', 'ewb_status',
        'booking_id', 'vendor_id', 'driver_id', 'vehicle_id',
        'driver_name', 'driver_mobile', 'vehicle_number',
        'driver_track_token', 'driver_track_started_at', 'driver_track_last_ping',
        'loading_point', 'unloading_point',
        'dispatch_datetime', 'delivery_datetime',
        'current_status', 'delay_reason',
        'pod_status', 'pod_received_at',
        'remarks',
        'cargo_value_inr', 'insurance_policy_no', 'insurance_provider',
        'loading_arrived_at', 'loading_departed_at',
        'unloading_arrived_at', 'unloading_departed_at',
        'detention_billable_hours', 'detention_amount',
        'created_by', 'updated_by', 'assigned_to', 'assigned_by',
    ];

    public const STATUSES = [
        'Booking Created', 'Vehicle Placed', 'Loading', 'In Transit',
        'Arrived', 'Unloading', 'Delivered', 'POD Received', 'Closed', 'Cancelled',
    ];

    /**
     * Forward-only status pipeline (Cancelled sits outside — reachable from anywhere
     * except terminal states, but not part of the linear flow).
     * The operator must not regress: once at "In Transit" they cannot pick
     * "Loading" or "Vehicle Placed" again — surface a confirmation from Ops if
     * a rollback is genuinely needed.
     */
    public const STATUS_PIPELINE = [
        'Booking Created', 'Vehicle Placed', 'Loading', 'In Transit',
        'Arrived', 'Unloading', 'Delivered', 'POD Received', 'Closed',
    ];

    /** True if this status ends the trip lifecycle (no further edits allowed). */
    public static function isTerminal(string $status): bool
    {
        return in_array($status, ['Closed', 'Cancelled'], true);
    }

    /**
     * Return the statuses the operator is allowed to move to from $current.
     * Rules:
     *   - Terminal (Closed / Cancelled) → nothing (return only $current itself so
     *     the current row still shows in the dropdown as selected).
     *   - Otherwise → $current + all pipeline states after $current + Cancelled.
     * The current state is included so the form re-renders with the value selected.
     */
    public static function allowedNextStatuses(string $current): array
    {
        if (self::isTerminal($current)) {
            return [$current];
        }
        $idx = array_search($current, self::STATUS_PIPELINE, true);
        if ($idx === false) {
            // Unknown state — allow the full forward list defensively.
            return array_merge(self::STATUS_PIPELINE, ['Cancelled']);
        }
        $forward = array_slice(self::STATUS_PIPELINE, (int) $idx);
        $forward[] = 'Cancelled';
        return $forward;
    }

    public function withJoins()
    {
        return $this->select('trips.*, bookings.booking_no, bookings.client_id, bookings.route_text, bookings.loading_date,
                              clients.company_name AS client_company, clients.mobile AS client_mobile,
                              vendors.company_name AS vendor_company')
            ->join('bookings', 'bookings.id = trips.booking_id',   'left')
            ->join('clients',  'clients.id  = bookings.client_id', 'left')
            ->join('vendors',  'vendors.id  = trips.vendor_id',    'left');
    }
}

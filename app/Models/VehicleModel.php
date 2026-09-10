<?php

namespace App\Models;

class VehicleModel extends BaseModel
{
    protected $table         = 'vehicles';
    protected $primaryKey    = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'vendor_id', 'vehicle_number', 'vehicle_type',
        'rc_no', 'rc_expiry',
        'permit_no', 'permit_expiry',
        'insurance_no', 'insurance_expiry',
        'fitness_expiry',
        'puc_no', 'puc_expiry',
        'tax_expiry',
        'status',
        'gps_provider', 'gps_device_imei', 'gps_tracking_url', 'gps_notes',
    ];

    /** Document fields used by the expiry-tracker widget. Order = display order. */
    public const EXPIRY_FIELDS = [
        'rc_expiry'        => 'RC',
        'insurance_expiry' => 'Insurance',
        'fitness_expiry'   => 'Fitness',
        'permit_expiry'    => 'Permit',
        'puc_expiry'       => 'PUC',
        'tax_expiry'       => 'Road Tax',
    ];

    protected $validationRules = [
        'vehicle_number' => 'required|min_length[4]|max_length[20]|is_unique[vehicles.vehicle_number,id,{id}]',
    ];

    public const GPS_PROVIDERS = [
        'none'              => 'No tracking',
        'loconav'           => 'LocoNav (multi-OEM aggregator)',
        'fasttag'           => 'FastTag toll-plaza pings',
        'dedicated_link'    => 'Vendor portal URL only',
        'driver_phone_only' => 'Driver phone PWA only',
    ];
}

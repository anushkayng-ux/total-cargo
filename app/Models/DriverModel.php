<?php

namespace App\Models;

class DriverModel extends BaseModel
{
    protected $table         = 'drivers';
    protected $primaryKey    = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'vendor_id', 'driver_name', 'mobile', 'alt_mobile',
        'license_no', 'license_expiry', 'status',
        'aadhaar_last_4', 'dl_verified',
        'kyc_status', 'kyc_verified_at', 'kyc_verified_by', 'kyc_notes',
    ];

    public const KYC_STATUSES = ['Pending', 'Verified', 'Rejected'];

    protected $validationRules = [
        'driver_name' => 'required|min_length[2]|max_length[150]',
    ];
}

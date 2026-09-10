<?php

namespace App\Models;

class VendorContactModel extends BaseModel
{
    protected $table          = 'vendor_contacts';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'vendor_id', 'contact_name', 'designation',
        'mobile', 'email', 'is_primary', 'notes',
    ];

    /** Common designations — used as datalist suggestions in the form. */
    public const COMMON_DESIGNATIONS = [
        'Owner', 'Manager', 'Operations Manager', 'Accountant',
        'Dispatch', 'Fleet Supervisor', 'Driver', 'Sales', 'Other',
    ];

    public function forVendor(int $vendorId): array
    {
        return $this->where('vendor_id', $vendorId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('id', 'ASC')
            ->find();
    }

    public function primaryFor(int $vendorId): ?array
    {
        return $this->where('vendor_id', $vendorId)
            ->where('is_primary', 1)
            ->first();
    }
}

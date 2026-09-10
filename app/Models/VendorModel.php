<?php

namespace App\Models;

class VendorModel extends BaseModel
{
    use \App\Traits\ResolvesCityIds;

    protected $table         = 'vendors';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'vendor_code', 'owner_name', 'company_name', 'vendor_type',
        'mobile', 'alt_mobile',
        'whatsapp_no', 'email', 'address', 'city', 'city_id', 'state', 'pincode',
        'gst_no', 'pan_no', 'bank_name', 'account_no', 'ifsc_code',
        'rating', 'is_preferred', 'is_blacklisted', 'status',
        'created_by', 'updated_by',
    ];

    protected $cityPairs    = ['city' => 'city_id'];
    protected $beforeInsert = ['_resolveCityIds'];
    protected $beforeUpdate = ['_resolveCityIds'];

    protected $validationRules = [
        'company_name' => 'required|min_length[2]|max_length[200]',
        'mobile'       => 'permit_empty|max_length[20]',
        'whatsapp_no'  => 'permit_empty|max_length[20]',
    ];

    public function nextCode(): string
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $n    = $last ? ((int) $last['id']) + 1 : 1;
        return 'VN' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }
}

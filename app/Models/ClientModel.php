<?php

namespace App\Models;

class ClientModel extends BaseModel
{
    use \App\Traits\ResolvesCityIds;

    protected $table         = 'clients';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'client_code', 'company_name', 'contact_name', 'mobile', 'alt_mobile',
        'email', 'gst_no', 'pan_no', 'address', 'city', 'city_id', 'state', 'pincode',
        'credit_limit', 'credit_days', 'status',
        'portal_enabled', 'kyc_status', 'portal_notes',
        'account_manager_user_id',
        'gst_treatment', 'tds_rate',
        'detention_free_hours_loading', 'detention_free_hours_unloading',
        'detention_rate_per_hour', 'is_msme',
        'created_by', 'updated_by',
    ];

    protected $cityPairs    = ['city' => 'city_id'];
    protected $beforeInsert = ['_resolveCityIds'];
    protected $beforeUpdate = ['_resolveCityIds'];

    public const KYC_STATUSES     = ['Pending', 'Verified', 'Rejected'];
    public const GST_TREATMENTS   = [
        'rcm'   => 'RCM — recipient pays GST',
        'fcm5'  => 'FCM 5% (no ITC)',
        'fcm12' => 'FCM 12% (with ITC)',
    ];

    protected $validationRules = [
        'company_name' => 'required|min_length[2]|max_length[200]',
        'mobile'       => 'permit_empty|max_length[20]',
        'email'        => 'permit_empty|valid_email|max_length[150]',
    ];

    public function nextCode(): string
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $n    = $last ? ((int) $last['id']) + 1 : 1;
        return 'CL' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }
}

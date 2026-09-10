<?php

namespace App\Models;

class RfqModel extends BaseModel
{
    use \App\Traits\ResolvesCityIds;

    protected $table          = 'rfq_master';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'rfq_no', 'lead_id', 'masked_reference',
        'pickup_city', 'pickup_city_id',
        'drop_city',   'drop_city_id',
        'vehicle_type', 'material_category',
        'weight', 'weight_unit', 'loading_date', 'status', 'created_by', 'assigned_to', 'assigned_by',
    ];

    protected $cityPairs    = ['pickup_city' => 'pickup_city_id', 'drop_city' => 'drop_city_id'];
    protected $beforeInsert = ['_resolveCityIds'];
    protected $beforeUpdate = ['_resolveCityIds'];

    public const STATUSES = ['Open', 'In Progress', 'Quote Received', 'Shortlisted', 'Awarded', 'Closed', 'Cancelled'];

    public function withJoins()
    {
        return $this->select('rfq_master.*, leads.lead_no, leads.client_name, leads.company_name AS lead_company, u.name AS created_by_name')
            ->join('leads', 'leads.id = rfq_master.lead_id', 'left')
            ->join('users u', 'u.id = rfq_master.created_by', 'left');
    }
}

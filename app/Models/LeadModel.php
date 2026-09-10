<?php

namespace App\Models;

class LeadModel extends BaseModel
{
    use \App\Traits\ResolvesCityIds;

    protected $table         = 'leads';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'lead_no', 'lead_datetime', 'source_id', 'client_id', 'client_name',
        'company_name', 'mobile', 'alt_mobile', 'email',
        'pickup_city', 'pickup_city_id', 'pickup_state',
        'drop_city',   'drop_city_id',   'drop_state',
        'material_type', 'vehicle_type_required', 'vehicle_count', 'weight', 'weight_unit',
        'expected_dispatch_date', 'priority', 'assigned_crm_user_id', 'assigned_by',
        'current_status', 'lost_reason', 'remarks',
        'created_by', 'updated_by',
    ];

    protected $cityPairs    = ['pickup_city' => 'pickup_city_id', 'drop_city' => 'drop_city_id'];
    protected $beforeInsert = ['_resolveCityIds'];
    protected $beforeUpdate = ['_resolveCityIds'];

    protected $validationRules = [
        'mobile'       => 'permit_empty|max_length[20]',
        'email'        => 'permit_empty|valid_email|max_length[150]',
        'priority'     => 'permit_empty|in_list[Low,Normal,High,Urgent]',
    ];

    public const STATUSES = [
        'New', 'Under Review', 'Sent to Purchase', 'Quote Received',
        'Sent to Client', 'Negotiation', 'Won', 'Lost', 'Closed',
    ];

    public function withJoins()
    {
        return $this->select('leads.*, lead_sources.source_name, u.name AS assignee_name, clients.company_name AS client_company')
            ->join('lead_sources', 'lead_sources.id = leads.source_id', 'left')
            ->join('users u', 'u.id = leads.assigned_crm_user_id', 'left')
            ->join('clients', 'clients.id = leads.client_id', 'left');
    }
}

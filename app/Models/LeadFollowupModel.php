<?php

namespace App\Models;

class LeadFollowupModel extends BaseModel
{
    protected $table          = 'lead_followups';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = false;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'lead_id', 'followup_datetime', 'followup_type', 'discussion_notes',
        'next_followup_datetime', 'created_by',
    ];

    public function forLead(int $leadId): array
    {
        return $this->select('lead_followups.*, u.name AS created_by_name')
            ->join('users u', 'u.id = lead_followups.created_by', 'left')
            ->where('lead_id', $leadId)
            ->orderBy('followup_datetime', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}

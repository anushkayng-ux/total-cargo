<?php

namespace App\Models;

class LeadStatusHistoryModel extends BaseModel
{
    protected $table          = 'lead_status_history';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = false;
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'lead_id', 'old_status', 'new_status', 'remarks', 'changed_by', 'changed_at',
    ];

    public function forLead(int $leadId): array
    {
        return $this->select('lead_status_history.*, u.name AS changed_by_name')
            ->join('users u', 'u.id = lead_status_history.changed_by', 'left')
            ->where('lead_id', $leadId)
            ->orderBy('changed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function log(int $leadId, ?string $oldStatus, string $newStatus, ?string $remarks, ?int $userId): void
    {
        $this->insert([
            'lead_id'    => $leadId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'remarks'    => $remarks,
            'changed_by' => $userId,
            'changed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

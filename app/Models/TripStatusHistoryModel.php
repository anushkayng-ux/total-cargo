<?php

namespace App\Models;

class TripStatusHistoryModel extends BaseModel
{
    protected $table          = 'trip_status_history';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['trip_id', 'old_status', 'new_status', 'notes', 'changed_by', 'changed_at'];

    public function forTrip(int $tripId): array
    {
        return $this->select('trip_status_history.*, u.name AS changed_by_name')
            ->join('users u', 'u.id = trip_status_history.changed_by', 'left')
            ->where('trip_id', $tripId)
            ->orderBy('changed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function log(int $tripId, ?string $oldStatus, string $newStatus, ?string $notes, ?int $userId): void
    {
        $this->insert([
            'trip_id'    => $tripId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes'      => $notes,
            'changed_by' => $userId,
            'changed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

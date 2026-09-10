<?php

namespace App\Models;

class EmailEventModel extends BaseModel
{
    protected $table          = 'email_events';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'email_log_id', 'event_type', 'data_json',
        'ip_address', 'user_agent', 'created_at',
    ];

    public function record(int $logId, string $type, array $data = []): void
    {
        $req = service('request');
        $this->insert([
            'email_log_id' => $logId,
            'event_type'   => $type,
            'data_json'    => $data ? json_encode($data) : null,
            'ip_address'   => method_exists($req, 'getIPAddress') ? $req->getIPAddress() : null,
            'user_agent'   => method_exists($req, 'getUserAgent') ? substr((string) $req->getUserAgent()->getAgentString(), 0, 255) : null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function forLog(int $logId): array
    {
        return $this->where('email_log_id', $logId)->orderBy('id', 'ASC')->find();
    }
}

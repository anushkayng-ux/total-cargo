<?php

namespace App\Models;

class ClientUserLoginLogModel extends BaseModel
{
    protected $table          = 'client_user_login_logs';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'client_user_id', 'email_tried', 'success', 'reason',
        'ip_address', 'user_agent', 'created_at',
    ];

    public function record(?int $clientUserId, string $email, bool $success, ?string $reason = null): void
    {
        $req = service('request');
        $this->insert([
            'client_user_id' => $clientUserId,
            'email_tried'    => substr($email, 0, 150),
            'success'        => $success ? 1 : 0,
            'reason'         => $reason ? substr($reason, 0, 80) : null,
            'ip_address'     => $req->getIPAddress(),
            'user_agent'     => substr((string) $req->getUserAgent()->getAgentString(), 0, 255),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    public function recentForUser(int $clientUserId, int $limit = 10): array
    {
        return $this->where('client_user_id', $clientUserId)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->find();
    }
}

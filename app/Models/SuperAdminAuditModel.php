<?php

namespace App\Models;

class SuperAdminAuditModel extends BaseModel
{
    protected $table          = 'super_admin_audit';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'user_id', 'action', 'target_type', 'target_id',
        'description', 'details_json', 'ip_address', 'user_agent', 'created_at',
    ];

    public function record(int $userId, string $action, ?string $description = null, array $details = [], ?string $targetType = null, ?int $targetId = null): void
    {
        $req = service('request');
        $this->insert([
            'user_id'      => $userId,
            'action'       => substr($action, 0, 80),
            'target_type'  => $targetType ? substr($targetType, 0, 60) : null,
            'target_id'    => $targetId,
            'description'  => $description ? substr($description, 0, 400) : null,
            'details_json' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address'   => method_exists($req, 'getIPAddress') ? $req->getIPAddress() : null,
            'user_agent'   => method_exists($req, 'getUserAgent') ? substr((string) $req->getUserAgent()->getAgentString(), 0, 255) : null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}

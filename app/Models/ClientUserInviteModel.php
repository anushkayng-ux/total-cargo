<?php

namespace App\Models;

class ClientUserInviteModel extends BaseModel
{
    protected $table         = 'client_user_invites';
    protected $primaryKey    = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields = [
        'client_id', 'name', 'email', 'mobile', 'portal_role',
        'token', 'expires_at', 'accepted_at', 'invited_by', 'created_at',
    ];

    public function findByToken(string $token): ?array
    {
        return $this->where('token', $token)->first();
    }
}

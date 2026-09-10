<?php

namespace App\Models;

class ClientUserResetModel extends BaseModel
{
    protected $table          = 'client_user_resets';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'client_user_id', 'token_hash', 'expires_at', 'used_at', 'created_at', 'ip_address',
    ];

    public function findValidByHash(string $hash): ?array
    {
        return $this->where('token_hash', $hash)
            ->where('used_at', null)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();
    }
}

<?php

namespace App\Models;

class EmailUnsubscribeModel extends BaseModel
{
    protected $table          = 'email_unsubscribes';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['email', 'reason', 'source', 'created_at'];

    public function isSuppressed(string $email): bool
    {
        $email = strtolower(trim($email));
        return $this->where('email', $email)->countAllResults() > 0;
    }

    public function add(string $email, string $source = 'link', ?string $reason = null): void
    {
        $email = strtolower(trim($email));
        if ($this->isSuppressed($email)) return;
        $this->insert([
            'email'      => $email,
            'reason'     => $reason ? substr($reason, 0, 200) : null,
            'source'     => $source,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function remove(string $email): void
    {
        $this->where('email', strtolower(trim($email)))->delete();
    }
}

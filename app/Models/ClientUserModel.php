<?php

namespace App\Models;

class ClientUserModel extends BaseModel
{
    protected $table         = 'client_users';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'client_id', 'name', 'email', 'mobile', 'password_hash',
        'portal_role', 'status', 'must_change_password',
        'failed_attempts', 'locked_until',
        'last_login_at', 'last_login_ip',
        'notify_whatsapp', 'notify_email',
        'created_by', 'updated_by',
    ];

    public const ROLES = ['Owner', 'Booker', 'Accounts', 'Viewer'];

    protected $validationRules = [
        'client_id' => 'required|integer',
        'name'      => 'required|min_length[2]|max_length[120]',
        'email'     => 'required|valid_email|max_length[150]|is_unique[client_users.email,id,{id}]',
        'mobile'    => 'permit_empty|max_length[20]',
    ];

    public function findActiveByEmail(string $email): ?array
    {
        return $this->where('email', $email)
            ->where('status', 1)
            ->first();
    }

    public function withClient(int $id): ?array
    {
        return $this->select('client_users.*, clients.company_name AS client_company, clients.client_code, clients.portal_enabled, clients.kyc_status')
            ->join('clients', 'clients.id = client_users.client_id', 'left')
            ->where('client_users.id', $id)
            ->first();
    }
}

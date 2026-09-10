<?php

namespace App\Models;

class UserModel extends BaseModel
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'role_id', 'name', 'email', 'mobile', 'password_hash', 'status',
        'last_login_at', 'last_login_ip', 'failed_attempts', 'locked_until',
        'is_super_admin', 'totp_secret', 'totp_enabled',
        'created_by', 'updated_by',
    ];

    // NOTE: Uniqueness is enforced in the controllers (UsersController::store
    // / update) where the real $id is available — model-level is_unique with
    // the {id} placeholder is unreliable on update() in this CI4 build and
    // silently rejected legitimate edits. Keep model rules to format-only.
    protected $validationRules = [
        'role_id' => 'required|integer',
        'name'    => 'required|min_length[2]|max_length[120]',
        'email'   => 'required|valid_email|max_length[150]',
        'mobile'  => 'permit_empty|max_length[20]',
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->where('status', 1)->first();
    }

    public function withRole(int $id): ?array
    {
        return $this->select('users.*, roles.role_name, roles.role_key')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', $id)
            ->first();
    }

    /** Active staff for assignee dropdowns: [id, name, role_name]. */
    public function activeList(): array
    {
        return $this->select('users.id, users.name, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.status', 1)
            ->where('users.deleted_at', null)
            ->orderBy('users.name', 'ASC')
            ->findAll();
    }
}

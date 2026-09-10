<?php

namespace App\Models;

class RolePermissionModel extends BaseModel
{
    protected $table         = 'role_permissions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'role_id', 'permission_id',
        'can_view', 'can_add', 'can_edit', 'can_delete', 'can_approve', 'can_export',
    ];

    /**
     * Build a permission map for a role keyed by module_key.
     * Returns [module_key => ['can_view' => 1, 'can_add' => 1, ...]]
     */
    public function mapForRole(int $roleId): array
    {
        $rows = $this->select('role_permissions.*, permissions.module_key, permissions.action_key')
            ->join('permissions', 'permissions.id = role_permissions.permission_id', 'inner')
            ->where('role_permissions.role_id', $roleId)
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['module_key']] = [
                'can_view'    => (int) $r['can_view'],
                'can_add'     => (int) $r['can_add'],
                'can_edit'    => (int) $r['can_edit'],
                'can_delete'  => (int) $r['can_delete'],
                'can_approve' => (int) $r['can_approve'],
                'can_export'  => (int) $r['can_export'],
            ];
        }
        return $map;
    }
}

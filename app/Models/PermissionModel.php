<?php

namespace App\Models;

class PermissionModel extends BaseModel
{
    protected $table         = 'permissions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;
    protected $allowedFields = ['module_key', 'action_key', 'label', 'status'];
}

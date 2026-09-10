<?php

namespace App\Models;

class RoleModel extends BaseModel
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['role_name', 'role_key', 'status'];

    // NOTE: role_key uniqueness is enforced in RolesController::store /
    // update (where $id is concretely known). The {id} placeholder for
    // is_unique on update() is unreliable in this CI4 build and silently
    // blocks legitimate role edits, so we keep the model to format-only.
    protected $validationRules = [
        'role_name' => 'required|min_length[2]|max_length[80]',
        'role_key'  => 'permit_empty|max_length[60]',
    ];
}

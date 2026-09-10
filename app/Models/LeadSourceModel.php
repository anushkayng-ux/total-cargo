<?php

namespace App\Models;

class LeadSourceModel extends BaseModel
{
    protected $table         = 'lead_sources';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;
    protected $allowedFields = ['source_name', 'source_key', 'status'];

    protected $validationRules = [
        'source_name' => 'required|min_length[2]|max_length[80]',
    ];
}

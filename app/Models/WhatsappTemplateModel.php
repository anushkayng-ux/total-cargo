<?php

namespace App\Models;

class WhatsappTemplateModel extends BaseModel
{
    protected $table          = 'whatsapp_templates';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'template_key', 'audience_type', 'template_name', 'language_code',
        'body_text', 'variables', 'status',
    ];

    protected $validationRules = [
        'template_key'  => 'required|max_length[80]',
        'template_name' => 'required|max_length[150]',
        'audience_type' => 'required|in_list[client,vendor,internal,driver]',
    ];

    public function getByKey(string $key): ?array
    {
        return $this->where('template_key', $key)->where('status', 1)->first();
    }
}

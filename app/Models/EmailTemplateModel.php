<?php

namespace App\Models;

class EmailTemplateModel extends BaseModel
{
    protected $table         = 'email_templates';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'template_key', 'audience_type', 'subject',
        'body_html', 'body_text', 'variables_json', 'status',
    ];

    public const AUDIENCES = ['client', 'vendor', 'internal', 'driver'];

    protected $validationRules = [
        'template_key' => 'required|min_length[2]|max_length[80]',
        'subject'      => 'required|min_length[1]|max_length[200]',
        'body_html'    => 'required',
    ];

    public function findByKey(string $key): ?array
    {
        return $this->where('template_key', $key)->where('status', 1)->first();
    }
}

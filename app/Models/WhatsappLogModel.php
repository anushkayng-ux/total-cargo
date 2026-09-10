<?php

namespace App\Models;

class WhatsappLogModel extends BaseModel
{
    protected $table          = 'whatsapp_logs';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'module_name', 'module_ref_id', 'audience_type',
        'recipient_no', 'template_key', 'message_type',
        'provider_message_id', 'delivery_status',
        'request_payload', 'response_payload', 'error_message', 'retry_count',
        'sent_at', 'delivered_at', 'read_at', 'failed_at',
    ];
}

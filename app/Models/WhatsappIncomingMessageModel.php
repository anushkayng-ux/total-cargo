<?php

namespace App\Models;

class WhatsappIncomingMessageModel extends BaseModel
{
    protected $table          = 'whatsapp_incoming_messages';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'sender_no', 'provider_message_id', 'message_type',
        'text_body', 'media_url', 'media_type', 'media_id',
        'rfq_id', 'trip_id', 'booking_id', 'vendor_id', 'client_id',
        'is_processed', 'raw_payload', 'received_at',
    ];
}

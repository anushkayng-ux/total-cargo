<?php

namespace App\Models;

class SupportTicketReplyModel extends BaseModel
{
    protected $table          = 'support_ticket_replies';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['ticket_id', 'user_id', 'body', 'is_internal', 'created_at'];

    public function thread(int $ticketId): array
    {
        return $this->select('support_ticket_replies.*, u.name AS author_name')
            ->join('users u', 'u.id = support_ticket_replies.user_id', 'left')
            ->where('ticket_id', $ticketId)
            ->orderBy('id', 'ASC')
            ->find();
    }
}

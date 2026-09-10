<?php

namespace App\Models;

class SupportTicketModel extends BaseModel
{
    protected $table         = 'support_tickets';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'ticket_no', 'user_id', 'type', 'priority', 'subject', 'body', 'screen_url',
        'status', 'assigned_to',
        'resolution', 'resolved_at', 'resolved_by', 'closed_at',
        'rating', 'rating_comment', 'rated_at',
    ];

    public const TYPES      = ['Bug','Improvement','Question','Other'];
    public const PRIORITIES = ['Low','Normal','High','Urgent'];
    public const STATUSES   = ['Open','In Progress','Resolved','Closed','Reopened'];

    public function nextTicketNo(): string
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $n = $last ? ((int) $last['id']) + 1 : 1;
        return 'SUP' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    public function withJoins()
    {
        return $this->select('support_tickets.*,
                              u1.name AS reporter_name, u1.email AS reporter_email,
                              u2.name AS assignee_name')
            ->join('users u1', 'u1.id = support_tickets.user_id',     'left')
            ->join('users u2', 'u2.id = support_tickets.assigned_to', 'left');
    }

    public function ratingsSummary(): array
    {
        $rows = $this->where('rating IS NOT NULL')->findAll();
        if (empty($rows)) return ['count' => 0, 'avg' => null, 'breakdown' => []];
        $sum = 0; $break = [1=>0,2=>0,3=>0,4=>0,5=>0];
        foreach ($rows as $r) { $sum += (int) $r['rating']; $break[(int) $r['rating']]++; }
        return [
            'count'     => count($rows),
            'avg'       => round($sum / count($rows), 2),
            'breakdown' => $break,
        ];
    }
}

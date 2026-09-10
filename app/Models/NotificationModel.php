<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Per-user in-app notifications. No soft deletes, no updated_at — these are
 * lightweight, append-mostly rows. We manage created_at manually so inserts
 * stay simple.
 */
class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'user_id', 'type', 'title', 'body', 'link', 'icon',
        'is_read', 'actor_id', 'created_at', 'read_at',
    ];

    /** Unread count for the bell badge. */
    public function unreadCount(int $userId): int
    {
        return (int) $this->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    /** Latest unread items for the bell dropdown. */
    public function unreadRecent(int $userId, int $limit = 8): array
    {
        return $this->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /** Full paginated history for the notifications page. */
    public function forUser(int $userId)
    {
        return $this->where('user_id', $userId)->orderBy('id', 'DESC');
    }

    public function markRead(int $id, int $userId): void
    {
        $this->where('id', $id)->where('user_id', $userId)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])->update();
    }

    public function markAllRead(int $userId): void
    {
        $this->where('user_id', $userId)->where('is_read', 0)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])->update();
    }
}

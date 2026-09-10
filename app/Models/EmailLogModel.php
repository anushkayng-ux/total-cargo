<?php

namespace App\Models;

class EmailLogModel extends BaseModel
{
    protected $table          = 'email_logs';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = [
        'tracking_token', 'template_key', 'to_email', 'to_name',
        'cc', 'bcc', 'reply_to', 'subject', 'body_html', 'body_text',
        'attachments_json', 'status', 'provider', 'provider_msg_id',
        'error', 'attempts', 'worker_id', 'worker_locked_at',
        'queued_at', 'sent_at', 'delivered_at', 'opened_at', 'open_count',
        'first_clicked_at', 'click_count', 'bounced_at', 'bounce_type',
        'complained_at', 'unsubscribed_at', 'last_event_at',
        'related_module', 'related_id', 'related_client_id',
        'sent_by_user_id', 'created_at',
    ];

    public const TERMINAL = ['Delivered','Failed','Bounced','Complained','Suppressed'];

    public function findByToken(string $token): ?array
    {
        return $this->where('tracking_token', $token)->first();
    }

    public function findByProviderId(string $provider, string $messageId): ?array
    {
        return $this->where('provider', $provider)->where('provider_msg_id', $messageId)->first();
    }

    /**
     * Atomically claim N queued rows for a worker. Returns claimed rows.
     * Prevents concurrent flushers from double-sending the same email.
     */
    public function claimBatch(string $workerId, int $limit = 50): array
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        // Atomically tag rows we will own. UPDATE first, then SELECT by worker_id.
        $db->query(
            "UPDATE {$this->table}
                SET worker_id = ?, worker_locked_at = ?, status = 'Sending', attempts = attempts + 1
              WHERE status = 'Queued' AND attempts < 5
              ORDER BY id ASC
              LIMIT ?",
            [$workerId, $now, $limit]
        );
        return $this->where('worker_id', $workerId)
            ->where('status', 'Sending')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->find();
    }

    /** Reset rows stuck in 'Sending' for too long (worker died mid-dispatch). */
    public function reapStuck(int $maxAgeSeconds = 300): int
    {
        $db = \Config\Database::connect();
        $cutoff = date('Y-m-d H:i:s', time() - $maxAgeSeconds);
        $db->query(
            "UPDATE {$this->table}
                SET status = 'Queued', worker_id = NULL, worker_locked_at = NULL,
                    error = CONCAT('reaped after stuck Sending; ', COALESCE(error, ''))
              WHERE status = 'Sending'
                AND worker_locked_at IS NOT NULL
                AND worker_locked_at < ?",
            [$cutoff]
        );
        return $db->affectedRows();
    }
}

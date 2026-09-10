<?php

namespace App\Models;

class RecordCommentModel extends BaseModel
{
    protected $table         = 'record_comments';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'record_type', 'record_id', 'user_id', 'body', 'mentions_json',
    ];

    public const TYPES = ['booking', 'trip', 'lead'];

    /** Returns thread with author names attached. */
    public function thread(string $type, int $id): array
    {
        return $this->select('record_comments.*, u.name AS author_name')
            ->join('users u', 'u.id = record_comments.user_id', 'left')
            ->where('record_type', $type)
            ->where('record_id', $id)
            ->orderBy('id', 'ASC')
            ->find();
    }
}

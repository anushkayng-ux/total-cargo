<?php

namespace App\Models;

class DocumentModel extends BaseModel
{
    protected $table          = 'documents';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = false;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
    protected $allowedFields  = [
        'module_name', 'module_ref_id', 'document_type',
        'original_file_name', 'stored_file_name', 'file_path',
        'file_size', 'mime_type', 'source_channel',
        'verification_status', 'remarks', 'uploaded_by', 'created_at',
    ];

    public function forModule(string $moduleName, int $refId): array
    {
        return $this->where('module_name', $moduleName)
            ->where('module_ref_id', $refId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}

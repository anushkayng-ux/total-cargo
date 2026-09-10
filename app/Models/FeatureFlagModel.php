<?php

namespace App\Models;

class FeatureFlagModel extends BaseModel
{
    protected $table          = 'feature_flags';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['flag_key', 'enabled', 'notes', 'updated_by', 'updated_at'];

    /** Returns enabled state for a flag; defaults to enabled for unknown flags. */
    public static function enabled(string $key): bool
    {
        static $cached = null;
        if ($cached === null) {
            $cached = [];
            try {
                $rows = \Config\Database::connect()->table('feature_flags')->select('flag_key, enabled')->get()->getResultArray();
                foreach ($rows as $r) $cached[$r['flag_key']] = (int) $r['enabled'] === 1;
            } catch (\Throwable $e) {
                // Table missing during migration — treat all as enabled.
            }
        }
        return $cached[$key] ?? true;
    }

    public function all(): array
    {
        return $this->orderBy('flag_key', 'ASC')->find();
    }
}

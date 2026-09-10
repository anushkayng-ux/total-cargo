<?php

namespace App\Models;

/**
 * City master. Soft master: canonical names referenced across the app
 * (clients.city, vendors.city, leads.pickup/drop_city, rfq_master.*,
 * vendor_routes.*) — UI autocomplete pulls from here, but the foreign
 * columns stay varchar to keep migrations simple.
 */
class CityModel extends BaseModel
{
    protected $table          = 'cities';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'name', 'state', 'gst_state_code', 'region', 'tier',
        'aliases', 'pincode_prefix', 'latitude', 'longitude',
        'status', 'created_by',
    ];

    public const REGIONS = ['North', 'South', 'East', 'West', 'Central', 'North-East'];
    public const TIERS   = ['1', '2', '3'];

    protected $validationRules = [
        'name'  => 'required|min_length[2]|max_length[80]',
        'state' => 'required|min_length[2]|max_length[80]',
    ];

    /**
     * Autocomplete lookup. Matches name OR aliases (comma-separated synonyms),
     * prioritises prefix hits, returns at most $limit rows.
     */
    public function search(string $q, int $limit = 12): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->where('status', 1)
                ->orderBy('tier', 'ASC')
                ->orderBy('name', 'ASC')
                ->limit($limit)
                ->find();
        }
        $like = $this->db->escapeLikeString($q);
        // Prefix wins over substring; alias hits last
        return $this->where('status', 1)
            ->groupStart()
                ->like('name', $q, 'after')
                ->orLike('name', $q, 'both')
                ->orLike('aliases', $q, 'both')
            ->groupEnd()
            ->orderBy("CASE WHEN name LIKE '$like%' THEN 0 WHEN name LIKE '%$like%' THEN 1 ELSE 2 END", '', false)
            ->orderBy('tier', 'ASC')
            ->orderBy('name', 'ASC')
            ->limit($limit)
            ->find();
    }

    /**
     * Resolve a free-text city string against the master. Returns the matched
     * row or null. Match order: exact name → exact alias → case-insensitive
     * name → case-insensitive alias.
     */
    public function resolve(string $cityName): ?array
    {
        $name = trim($cityName);
        if ($name === '') return null;

        $exact = $this->where('LOWER(name)', strtolower($name))->where('status', 1)->first();
        if ($exact) return $exact;

        $aliasHit = $this->like('aliases', $name, 'both')->where('status', 1)->first();
        return $aliasHit ?: null;
    }

    /**
     * Distinct active state names — used for the filter dropdown.
     */
    public function statesList(): array
    {
        $rows = $this->select('state')->distinct()->where('status', 1)->orderBy('state', 'ASC')->find();
        return array_column($rows, 'state');
    }
}

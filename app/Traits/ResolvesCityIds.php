<?php

namespace App\Traits;

/**
 * Mixin for models with one or more `*_city` varchar columns that have a
 * matching `*_city_id` soft FK. On every insert/update, the city_id is
 * auto-populated from the master via CityModel::resolve() so reports can
 * join on the id without forcing the UI to expose the master picker.
 *
 * To use:
 *   - List the city pairs in $cityPairs as ['city_col' => 'city_id_col'].
 *   - Add 'beforeInsert' / 'beforeUpdate' arrays in the model with
 *     '_resolveCityIds' as the first callback (or just add this trait and
 *     the constructor will append it automatically).
 *
 * Self-contained: no state, idempotent — if the varchar is empty the id is
 * cleared; if the master can't resolve the city, the id is left as null and
 * the varchar still wins for display purposes.
 */
trait ResolvesCityIds
{
    /**
     * Each consuming model declares its own `protected $cityPairs = [...]`
     * mapping `city_col => city_id_col`. We intentionally do NOT redeclare
     * it here — PHP 8.2 errors out on typed/untyped property mismatches
     * between traits and the classes that use them.
     */
    public function _resolveCityIds(array $event): array
    {
        if (empty($this->cityPairs ?? [])) return $event;
        $data = $event['data'] ?? [];
        $bag  = $data['data'] ?? null;
        if (!is_array($bag)) return $event;

        $resolver = new \App\Models\CityModel();
        foreach ($this->cityPairs as $cityCol => $idCol) {
            if (!array_key_exists($cityCol, $bag)) continue;
            $raw = trim((string) ($bag[$cityCol] ?? ''));
            if ($raw === '') { $bag[$idCol] = null; continue; }
            $hit = $resolver->resolve($raw);
            $bag[$idCol] = $hit ? (int) $hit['id'] : null;
        }
        $event['data']['data'] = $bag;
        return $event;
    }
}

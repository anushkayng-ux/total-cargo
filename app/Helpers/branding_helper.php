<?php

if (!function_exists('tpt_feature_enabled')) {
    /** Cheap wrapper around FeatureFlagModel for use in views / menu helpers. */
    function tpt_feature_enabled(string $key): bool
    {
        return \App\Models\FeatureFlagModel::enabled($key);
    }
}

if (!function_exists('tpt_logo_url')) {
    /**
     * Returns the absolute URL to the company logo, or null if none is configured.
     * Cached per-request to avoid repeat DB hits.
     */
    function tpt_logo_url(): ?string
    {
        static $cached = null;
        static $fetched = false;
        if ($fetched) return $cached;

        try {
            $row = \Config\Database::connect()
                ->table('settings')
                ->select('setting_value')
                ->where('setting_key', 'company_logo_path')
                ->get()->getRow();
            $val = $row && !empty($row->setting_value) ? (string) $row->setting_value : '';
            $cached = $val !== '' ? base_url($val) : null;
        } catch (\Throwable $e) {
            $cached = null;
        }
        $fetched = true;
        return $cached;
    }
}

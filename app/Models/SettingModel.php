<?php

namespace App\Models;

class SettingModel extends BaseModel
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;
    protected $allowedFields = ['setting_key', 'setting_value', 'setting_group', 'updated_by', 'updated_at'];

    /** Setting keys whose values must be encrypted at rest. */
    private const SENSITIVE_KEYS = [
        'brevo_api_key', 'brevo_webhook_secret',
        'ses_secret_key', 'ses_access_key',
        'smtp_pass',
        'whatsapp.appSecret', 'cleartax.apiKey', 'loconav.apiKey',
        'email_track_secret',
        'fasttag_api_key',
        'insurance_api_key',
    ];

    private static function isSensitive(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }

    /** Encrypt with CI4 Encrypter; prefix marks ciphertext so we can detect format. */
    private static function encryptValue(string $plain): string
    {
        if ($plain === '') return '';
        try {
            $cipher = service('encrypter')->encrypt($plain);
            return 'enc:' . base64_encode($cipher);
        } catch (\Throwable $e) {
            log_message('warning', 'SettingModel encrypt failed: ' . $e->getMessage());
            return $plain; // fail open — better than losing the value
        }
    }

    private static function decryptValue(string $stored): string
    {
        if (strpos($stored, 'enc:') !== 0) return $stored;
        try {
            return (string) service('encrypter')->decrypt(base64_decode(substr($stored, 4), true));
        } catch (\Throwable $e) {
            log_message('warning', 'SettingModel decrypt failed: ' . $e->getMessage());
            return '';
        }
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('setting_group')->orderBy('setting_key')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $val = (string) ($r['setting_value'] ?? '');
            if (self::isSensitive($r['setting_key'])) $val = self::decryptValue($val);
            $out[$r['setting_group']][$r['setting_key']] = $val;
        }
        return $out;
    }

    /**
     * Per-request in-memory cache.
     * Settings are read on most page renders (logo, mail-from, feature flags,
     * GST rate, etc.) — hitting the DB on each call was a measurable cost.
     */
    private static array $cache = [];
    private static bool  $cacheLoaded = false;

    private static function loadCache(): void
    {
        if (self::$cacheLoaded) return;
        $rows = \Config\Database::connect()->table('settings')->get()->getResultArray();
        foreach ($rows as $r) self::$cache[$r['setting_key']] = (string) ($r['setting_value'] ?? '');
        self::$cacheLoaded = true;
    }

    /** Wipe the cache after writes so a fresh get() reflects the new value. */
    private static function invalidate(string $key, string $stored): void
    {
        self::$cache[$key] = $stored;
    }

    public function get(string $key, $default = null)
    {
        self::loadCache();
        if (!array_key_exists($key, self::$cache)) return $default;
        $val = self::$cache[$key];
        if (self::isSensitive($key)) $val = self::decryptValue($val);
        return $val;
    }

    public function put(string $key, $value, string $group = 'general', ?int $userId = null): bool
    {
        $stored = (string) $value;
        if (self::isSensitive($key) && $stored !== '') {
            $stored = self::encryptValue($stored);
        }
        self::invalidate($key, $stored);
        $row = $this->where('setting_key', $key)->first();
        $data = [
            'setting_value' => $stored,
            'setting_group' => $group,
            'updated_by'    => $userId,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if ($row) {
            return (bool) $this->update($row['id'], $data);
        }
        $data['setting_key'] = $key;
        return (bool) $this->insert($data, false);
    }
}

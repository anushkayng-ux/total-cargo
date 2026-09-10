<?php

namespace App\Libraries;

/**
 * Minimal RFC 6238 TOTP implementation. SHA-1, 6 digits, 30-second period —
 * compatible with Google Authenticator / Authy / 1Password / Microsoft Authenticator.
 */
class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /** Build the otpauth:// URI for QR rendering. */
    public static function provisioningUri(string $secret, string $accountLabel, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $accountLabel);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    /** Compute the current 6-digit code (or for an offset of $window steps). */
    public static function code(string $secret, int $offset = 0, ?int $time = null): string
    {
        $time = $time ?? time();
        $counter = (int) floor($time / self::PERIOD) + $offset;
        $key = self::base32Decode($secret);

        // Pack counter as big-endian 8-byte int
        $bin = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);

        $offsetByte = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = ((ord($hash[$offsetByte])     & 0x7F) << 24)
                   | ((ord($hash[$offsetByte + 1]) & 0xFF) << 16)
                   | ((ord($hash[$offsetByte + 2]) & 0xFF) <<  8)
                   |  (ord($hash[$offsetByte + 3]) & 0xFF);

        $mod = 10 ** self::DIGITS;
        return str_pad((string) ($truncated % $mod), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Verifies a code with ±$window 30-second slots tolerance (default ±1). */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $i), $code)) return true;
        }
        return false;
    }

    // ── Base32 (RFC 4648, no padding) ────────────────────────────────────

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function base32Encode(string $bytes): string
    {
        if ($bytes === '') return '';
        $bin = '';
        for ($i = 0, $n = strlen($bytes); $i < $n; $i++) {
            $bin .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bin, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out  .= self::ALPHABET[bindec($chunk)];
        }
        return $out;
    }

    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
        if ($b32 === '') return '';
        $bin = '';
        $alphabet = self::ALPHABET;
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $idx = strpos($alphabet, $b32[$i]);
            if ($idx === false) continue;
            $bin .= str_pad(decbin($idx), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bin, 8) as $byte) {
            if (strlen($byte) === 8) $out .= chr(bindec($byte));
        }
        return $out;
    }
}

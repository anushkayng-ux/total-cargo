<?php

namespace App\Libraries;

/**
 * Encrypt/decrypt PII fields at rest. Uses the same `encrypter` service that
 * SettingModel uses for API keys, so the encryption.key in .env is the single
 * source of truth. Wraps every encrypted value with an `enc:` prefix so
 * already-plaintext rows (legacy data) stay readable on first decrypt.
 *
 * The sensitive columns are listed in SENSITIVE — apply via the static helpers
 * inside HrmsController save/load (or any other controller that touches them).
 *
 * Usage:
 *   $row['pan_no']  = PiiCrypto::decrypt($row['pan_no']);
 *   $update['pan_no'] = PiiCrypto::encrypt($_POST['pan_no']);
 *   $row = PiiCrypto::decryptRow($row, PiiCrypto::EMPLOYEE_PROFILE);
 *   $update = PiiCrypto::encryptRow($update, PiiCrypto::EMPLOYEE_PROFILE);
 */
class PiiCrypto
{
    /** Field sets per table. */
    public const EMPLOYEE_PROFILE = ['pan_no','aadhaar_last_4','uan_no','bank_account_no'];

    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') return $plain;
        if (str_starts_with($plain, 'enc:')) return $plain;  // already encrypted
        try {
            $cipher = service('encrypter')->encrypt($plain);
            return 'enc:' . base64_encode($cipher);
        } catch (\Throwable $e) {
            log_message('warning', 'PiiCrypto::encrypt failed: ' . $e->getMessage());
            return $plain;
        }
    }

    public static function decrypt(?string $stored): ?string
    {
        if ($stored === null || $stored === '')  return $stored;
        if (!str_starts_with($stored, 'enc:'))   return $stored; // legacy plaintext, return as-is
        try {
            $raw = base64_decode(substr($stored, 4), true);
            return $raw === false ? $stored : (string) service('encrypter')->decrypt($raw);
        } catch (\Throwable $e) {
            log_message('warning', 'PiiCrypto::decrypt failed: ' . $e->getMessage());
            return $stored;
        }
    }

    /** Encrypt all sensitive fields in a row (returns a new array). */
    public static function encryptRow(array $row, array $fields): array
    {
        foreach ($fields as $f) {
            if (array_key_exists($f, $row) && $row[$f] !== null && $row[$f] !== '') {
                $row[$f] = self::encrypt((string) $row[$f]);
            }
        }
        return $row;
    }

    /** Decrypt all sensitive fields in a row. Pass through an empty row. */
    public static function decryptRow(?array $row, array $fields): array
    {
        if (!$row) return [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $row) && $row[$f] !== null) {
                $row[$f] = self::decrypt((string) $row[$f]);
            }
        }
        return $row;
    }

    /** Mask a decrypted PII value for display (last 4 visible). */
    public static function mask(?string $plain, int $visibleSuffix = 4): string
    {
        if ($plain === null || $plain === '') return '';
        $len = strlen($plain);
        if ($len <= $visibleSuffix) return str_repeat('•', $len);
        return str_repeat('•', $len - $visibleSuffix) . substr($plain, -$visibleSuffix);
    }
}

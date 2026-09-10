<?php

namespace App\Libraries;

use App\Models\SettingModel;

/**
 * Generates zero-padded sequential numbers for lead / rfq / booking / trip / invoice.
 * Uses MAX(id)+1 on the source table plus configured prefix from `settings`.
 */
class NumberGenerator
{
    private const PAD = 5;

    private static function prefix(string $settingKey, string $fallback): string
    {
        static $cache = [];
        if (!isset($cache[$settingKey])) {
            $cache[$settingKey] = (new SettingModel())->get($settingKey, $fallback) ?: $fallback;
        }
        return $cache[$settingKey];
    }

    private static function nextFromTable(string $table, string $column, string $prefix): string
    {
        $db  = \Config\Database::connect();
        $max = (int) ($db->table($table)->selectMax('id')->get()->getRow('id') ?? 0);
        $n   = $max + 1;
        $candidate = sprintf('%s%s', $prefix, str_pad((string) $n, self::PAD, '0', STR_PAD_LEFT));
        // Collision guard: bump if already exists
        while ($db->table($table)->where($column, $candidate)->countAllResults() > 0) {
            $n++;
            $candidate = sprintf('%s%s', $prefix, str_pad((string) $n, self::PAD, '0', STR_PAD_LEFT));
        }
        return $candidate;
    }

    public static function lead(): string
    {
        return self::nextFromTable('leads', 'lead_no', self::prefix('lead_prefix', 'LD'));
    }

    public static function rfq(): string
    {
        return self::nextFromTable('rfq_master', 'rfq_no', self::prefix('rfq_prefix', 'RFQ'));
    }

    public static function booking(): string
    {
        return self::nextFromTable('bookings', 'booking_no', self::prefix('booking_prefix', 'BK'));
    }

    public static function trip(): string
    {
        return self::nextFromTable('trips', 'trip_no', self::prefix('trip_prefix', 'TR'));
    }

    public static function invoice(): string
    {
        return self::nextFromTable('invoices', 'invoice_no', self::prefix('invoice_prefix', 'INV'));
    }

    public static function lr(): string
    {
        return self::nextFromTable('trips', 'lr_no', self::prefix('lr_prefix', 'LR'));
    }

    public static function rfqMaskedRef(): string
    {
        // Deliberately opaque — vendor never sees the real lead number or client
        return 'REF-' . strtoupper(bin2hex(random_bytes(4)));
    }
}

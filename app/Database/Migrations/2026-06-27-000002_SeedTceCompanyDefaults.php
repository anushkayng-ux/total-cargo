<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seeds the settings table with TCE company defaults so LR / Invoice PDFs
 * render with correct letterhead out of the box. Idempotent:
 *  - Missing keys are inserted with the TCE defaults.
 *  - Existing keys that are blank are updated with the TCE value.
 *  - Existing keys with a non-blank value are LEFT ALONE (never overwritten).
 *
 * Override anytime via /settings → Company group.
 */
class SeedTceCompanyDefaults extends Migration
{
    private array $defaults = [
        'company_name'    => 'Total Cargo Express Private Limited',
        'company_address' => "J – 119 VIKASPURI, NEW DELHI, 110018",
        'company_phone'   => '01145638110 – 8287704541',
        'company_email'   => 'info@totalcargo.co.in',
        'company_gstin'   => '07AAICT9876G1ZN',
        'company_pan'     => 'AAICT9876G',
        'company_msme'    => 'UDYAM-DL-10-0021169',
        'company_state'   => 'Delhi',
        'company_city'    => 'New Delhi',
        'company_pincode' => '110018',
        'company_website' => 'www.totalcargo.co.in',
        'issuing_office'  => 'HEAD OFFICE',
    ];

    public function up(): void
    {
        $db = $this->db;
        $tbl = $db->table('settings');
        $now = date('Y-m-d H:i:s');

        foreach ($this->defaults as $key => $value) {
            $existing = $tbl->where('setting_key', $key)->get()->getRowArray();
            if (!$existing) {
                $tbl->insert([
                    'setting_key'   => $key,
                    'setting_value' => $value,
                    'setting_group' => 'company',
                    'updated_at'    => $now,
                ]);
            } elseif (trim((string) ($existing['setting_value'] ?? '')) === '') {
                // Blank cell — fill with the TCE default.
                $tbl->where('id', (int) $existing['id'])->update([
                    'setting_value' => $value,
                    'setting_group' => 'company',
                    'updated_at'    => $now,
                ]);
            }
            // Non-blank existing value — respect operator's choice, don't touch.
        }
    }

    public function down(): void
    {
        // Only reverse rows whose value STILL matches the seeded default —
        // don't touch any operator-edited value.
        $db = $this->db;
        foreach ($this->defaults as $key => $value) {
            $db->table('settings')
                ->where('setting_key', $key)
                ->where('setting_value', $value)
                ->delete();
        }
    }
}

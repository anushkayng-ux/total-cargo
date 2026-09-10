<?php
/**
 * TCE datetime display helpers.
 *
 * All UI display goes through these so the format stays consistent across
 * every view. The DB continues to store ISO (Y-m-d H:i:s) — these are only
 * for what the user sees. HTML <input type="date"|"datetime-local"> values
 * MUST stay ISO per browser spec, so do NOT use these helpers there.
 */

if (! function_exists('tpt_dt')) {
    /**
     * Format a stored datetime for on-screen display: "04-07-2026 14:30".
     * Accepts a MySQL datetime string, a date-only string, a unix ts, or null.
     * Returns $blank ('—' by default) for null/empty/invalid input.
     */
    function tpt_dt($value, string $blank = '—'): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return $blank;
        }
        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if ($ts === false || $ts <= 0) return $blank;
        return date('d-m-Y H:i', $ts);
    }
}

if (! function_exists('tpt_route')) {
    /**
     * Return a route string that is safe to render inside a dompdf PDF.
     * The stored route uses "→" (U+2192) which the default Helvetica font
     * doesn't ship with, so dompdf substitutes "?" — that's the source of
     * "New Delhi ? Ahmedabad" on Docket / Invoice / Quotation PDFs.
     * This helper replaces every Unicode arrow (and short variants like "->")
     * with a plain " - " separator, and collapses any doubled separators.
     */
    function tpt_route($value, string $blank = '—'): string
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '') return $blank;
        // Normalize every arrow-ish separator to " - "
        $s = preg_replace('/\s*(?:→|->|—|–|-+>|➔|➜|➞)\s*/u', ' - ', $s);
        // Collapse repeated " - " that could result from empty parts
        $s = preg_replace('/(?:\s*-\s*){2,}/', ' - ', $s);
        // Trim leading/trailing separator
        $s = trim($s, " -\t\n\r\0\x0B");
        return $s !== '' ? $s : $blank;
    }
}

if (! function_exists('tpt_d')) {
    /**
     * Format a stored date for on-screen display: "04-07-2026".
     * Same input contract as tpt_dt() but drops the time.
     */
    function tpt_d($value, string $blank = '—'): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return $blank;
        }
        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if ($ts === false || $ts <= 0) return $blank;
        return date('d-m-Y', $ts);
    }
}

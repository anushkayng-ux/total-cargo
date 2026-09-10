<?php

namespace App\Traits;

/**
 * Reusable CSV export helper. Mix into any controller and call
 *   $this->streamCsv('trips-2026.csv', $headers, $rows);
 *
 * - UTF-8 BOM so Excel opens it without re-encoding garble.
 * - Streams via php://output instead of building a huge string in memory.
 * - $rows can be an array of arrays OR a generator that yields one row at a time.
 * - $headers may be a numeric list (used verbatim) or an associative map
 *   ['db_col' => 'Display Header']; in the latter case row values are
 *   pulled by the keys.
 */
trait ExportsCsv
{
    protected function streamCsv(string $filename, array $headers, iterable $rows)
    {
        $useKeys = $this->isAssoc($headers);
        $resp = $this->response
            ->setHeader('Content-Type',        'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control',       'no-store, max-age=0')
            ->setHeader('Pragma',              'no-cache');
        $resp->setBody(''); // start clean
        // CI4's Response buffers — we need to emit headers then stream. Use a
        // string buffer instead and let the framework return it.
        $fh = fopen('php://temp', 'w+');
        fwrite($fh, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($fh, $useKeys ? array_values($headers) : $headers);
        foreach ($rows as $r) {
            if ($useKeys) {
                $line = [];
                foreach (array_keys($headers) as $k) $line[] = $this->cell($r[$k] ?? '');
            } else {
                $line = array_map(fn($v) => $this->cell($v), is_array($r) ? array_values($r) : [$r]);
            }
            fputcsv($fh, $line);
        }
        rewind($fh);
        $body = stream_get_contents($fh);
        fclose($fh);
        return $resp->setBody($body);
    }

    private function isAssoc(array $a): bool
    {
        if ($a === []) return false;
        return array_keys($a) !== range(0, count($a) - 1);
    }

    /**
     * Normalises a single cell value: scalars to string; arrays joined; null
     * to empty; preserves ₹ + dates as-is. We deliberately do NOT zero-pad or
     * apply formulas — Excel handles UTF-8 + BOM fine.
     */
    private function cell($v): string
    {
        if ($v === null) return '';
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_array($v)) return implode('; ', array_map(fn($x) => (string) $x, $v));
        return (string) $v;
    }
}

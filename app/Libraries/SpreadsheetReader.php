<?php

namespace App\Libraries;

/**
 * Zero-dependency reader for .csv and .xlsx files.
 *
 *  - CSV: native fgetcsv with BOM strip and delimiter auto-detect (, ; \t |).
 *  - XLSX: ZipArchive + SimpleXML — pulls sharedStrings.xml and the first
 *          worksheet, expands the column letters (A,B,…,AA) to numeric
 *          indexes and resolves shared/inline strings.
 *
 * Returns rows as arrays of cell strings. The first non-empty row is treated
 * as the header by the caller.
 */
class SpreadsheetReader
{
    /**
     * Read a file by extension. Throws \RuntimeException on parse errors.
     *
     * @return array<int, array<int, string>> Rows as 0-indexed cell arrays.
     */
    public static function read(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') return self::readCsv($path);
        if ($ext === 'xlsx')                  return self::readXlsx($path);
        throw new \RuntimeException("Unsupported file type: .$ext (use .csv or .xlsx)");
    }

    // ── CSV ───────────────────────────────────────────────────────────

    private static function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) throw new \RuntimeException('Could not open CSV file.');

        // Peek first 4 KB to guess the delimiter
        $peek = (string) fread($fh, 4096);
        rewind($fh);
        if (substr($peek, 0, 3) === "\xEF\xBB\xBF") {
            fread($fh, 3); // skip BOM
        }
        $delim = self::guessDelimiter($peek);

        $rows = [];
        while (($r = fgetcsv($fh, 0, $delim)) !== false) {
            // Skip rows that are entirely empty
            if (count(array_filter($r, fn($v) => trim((string) $v) !== '')) === 0) continue;
            $rows[] = array_map(fn($v) => trim((string) $v), $r);
        }
        fclose($fh);
        return $rows;
    }

    private static function guessDelimiter(string $sample): string
    {
        $candidates = [",", ";", "\t", "|"];
        $best = ','; $bestScore = -1;
        $firstLine = strtok($sample, "\n") ?: $sample;
        foreach ($candidates as $d) {
            $count = substr_count($firstLine, $d);
            if ($count > $bestScore) { $bestScore = $count; $best = $d; }
        }
        return $best;
    }

    // ── XLSX ──────────────────────────────────────────────────────────

    private static function readXlsx(string $path): array
    {
        if (!class_exists('\ZipArchive')) {
            throw new \RuntimeException('ZipArchive PHP extension is required for .xlsx files.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Cannot open .xlsx file — is it a valid Excel workbook?');
        }

        // 1) Shared strings (index → text). Optional.
        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false && $ss !== '') {
            $xml = @simplexml_load_string($ss);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    // <si><t>text</t></si>  OR  <si><r><t>part</t></r>...</si>
                    if (isset($si->t)) {
                        $shared[] = (string) $si->t;
                    } else {
                        $combined = '';
                        foreach ($si->r as $r) $combined .= (string) $r->t;
                        $shared[] = $combined;
                    }
                }
            }
        }

        // 2) First worksheet. Try xl/worksheets/sheet1.xml first; fall back to
        //    whatever's first in the workbook listing.
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false || $sheetXml === '') {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }
        $zip->close();
        if (!$sheetXml) throw new \RuntimeException('No worksheet found in the .xlsx file.');

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false) throw new \RuntimeException('Could not parse worksheet XML.');

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            $maxIdx = -1;
            foreach ($row->c as $c) {
                $ref  = (string) ($c['r'] ?? '');
                $type = (string) ($c['t'] ?? '');
                $col  = self::colLetterToIndex(preg_replace('/[0-9]+/', '', $ref));
                $val  = '';
                if ($type === 's') {
                    $idx = (int) $c->v;
                    $val = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } elseif ($type === 'b') {
                    $val = ((int) $c->v) ? 'TRUE' : 'FALSE';
                } else {
                    $val = (string) ($c->v ?? '');
                }
                $cells[$col] = trim($val);
                if ($col > $maxIdx) $maxIdx = $col;
            }
            // Densify the sparse row — Excel only emits cells that have a value.
            if ($maxIdx >= 0) {
                $dense = [];
                for ($i = 0; $i <= $maxIdx; $i++) $dense[] = $cells[$i] ?? '';
                if (count(array_filter($dense, fn($v) => $v !== '')) === 0) continue;
                $rows[] = $dense;
            }
        }
        return $rows;
    }

    /** "A" → 0, "B" → 1, "Z" → 25, "AA" → 26, "AB" → 27, etc. */
    private static function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $n = 0;
        for ($i = 0, $L = strlen($letters); $i < $L; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $n - 1);
    }
}

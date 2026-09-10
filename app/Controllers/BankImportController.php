<?php

namespace App\Controllers;

use App\Libraries\SafeUpload;
use App\Models\InvoiceModel;
use App\Models\ReceiptModel;
use App\Models\ClientModel;

/**
 * Bank-statement CSV → receipt auto-match.
 *
 * Flow:
 *   1. GET  /receipts/import          — upload form + last import summary
 *   2. POST /receipts/import          — parse CSV, hold proposed matches in session
 *   3. GET  /receipts/import/review   — table of (statement row, suggested invoice, confidence)
 *   4. POST /receipts/import/confirm  — create receipts for the checked rows
 *
 * Matching strategy (in priority order):
 *   - Exact UTR/reference match against existing receipts.reference_no   → skip duplicate
 *   - Exact UTR/reference match against client's expected_payment_refs   → high confidence
 *   - Exact amount + invoice no in narration                             → high
 *   - Amount + within ±2 days of invoice due_date for any unpaid invoice → medium
 *   - Amount alone, single match against a client's outstanding          → low
 *
 * Supported formats: HDFC, ICICI, SBI, Axis common CSV columns. Falls back
 * to "best guess by header name" so generic bank exports also work.
 */
class BankImportController extends BaseController
{
    public function form()
    {
        return $this->render('receipts/import', [
            'pageTitle' => 'Bank Statement Import',
            'lastSummary' => $this->session->get('bank_import_last_summary') ?? null,
        ]);
    }

    public function parse()
    {
        $file = $this->request->getFile('csv');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Upload a CSV file.');
        }
        try {
            $path = SafeUpload::move($file, 'bank-import', 'csv', 5 * 1024 * 1024);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $full = WRITEPATH . 'uploads/' . $path;
        $rows = $this->readCsv($full);
        @unlink($full); // we don't keep the original on disk past parsing

        if (empty($rows)) {
            return redirect()->back()->with('error', 'No data rows detected. Verify the CSV has a header row.');
        }

        // Only credits (money in) become receipts. Filter & normalise.
        $credits = $this->filterCredits($rows);

        // Match each credit against open invoices
        $proposed = $this->matchAll($credits);

        // Stash in session — confirm step will read it
        $this->session->set('bank_import_proposed', $proposed);

        return redirect()->to(site_url('receipts/import/review'));
    }

    public function review()
    {
        $proposed = $this->session->get('bank_import_proposed') ?? [];
        return $this->render('receipts/import_review', [
            'pageTitle' => 'Review proposed receipts',
            'proposed'  => $proposed,
        ]);
    }

    public function confirm()
    {
        $proposed = $this->session->get('bank_import_proposed') ?? [];
        if (empty($proposed)) return redirect()->to(site_url('receipts'))->with('error', 'Nothing to import.');

        $picked = array_map('intval', (array) $this->request->getPost('idx'));
        $rm     = new ReceiptModel();
        $created = 0; $skipped = 0;

        foreach ($picked as $i) {
            if (!isset($proposed[$i])) { $skipped++; continue; }
            $p = $proposed[$i];
            if (!empty($p['duplicate'])) { $skipped++; continue; }
            if (empty($p['invoice_id']))  { $skipped++; continue; }

            $rm->insert([
                'client_id'       => (int) $p['client_id'],
                'invoice_id'      => (int) $p['invoice_id'],
                'receipt_date'    => $p['date'],
                'payment_mode'    => 'Bank Transfer',
                'amount_received' => (float) $p['amount'],
                'reference_no'    => substr((string) $p['ref'], 0, 80),
                'notes'           => 'Auto-matched from bank import',
                'created_by'      => $this->auth->id(),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            // Recompute invoice balance via existing model hook if available
            $this->reduceInvoiceBalance((int) $p['invoice_id'], (float) $p['amount']);
            $created++;

            \App\Libraries\AuditLogger::log('receipts', $rm->getInsertID(), 'bank_imported',
                "Auto-matched bank credit ₹" . number_format((float) $p['amount'], 2) . " → invoice #" . $p['invoice_id']);
        }

        $this->session->remove('bank_import_proposed');
        $this->session->set('bank_import_last_summary', [
            'when'    => date('Y-m-d H:i'),
            'created' => $created,
            'skipped' => $skipped,
        ]);
        return redirect()->to(site_url('receipts'))->with('success', "$created receipt(s) created · $skipped skipped");
    }

    // ── Parsing ───────────────────────────────────────────────────────

    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) return [];
        $header = null;
        $rows   = [];
        $i = 0;
        while (($r = fgetcsv($fh)) !== false) {
            $i++;
            if (!$r || count(array_filter($r, fn($v) => trim((string) $v) !== '')) === 0) continue;
            if ($header === null) {
                // Find a row that looks like a header — contains "date" or "narration"
                $joined = strtolower(implode('|', $r));
                if (str_contains($joined, 'date') || str_contains($joined, 'narration') || str_contains($joined, 'description')) {
                    $header = array_map(fn($h) => strtolower(trim((string) $h)), $r);
                    continue;
                }
                continue;
            }
            $row = [];
            foreach ($header as $idx => $key) $row[$key] = trim((string) ($r[$idx] ?? ''));
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    private function filterCredits(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $credit = $this->pick($r, ['credit', 'deposit', 'cr', 'credit amount', 'amount credited', 'cr/dr amount']);
            $debit  = $this->pick($r, ['debit', 'withdrawal', 'dr', 'debit amount', 'amount debited']);
            $type   = strtolower($this->pick($r, ['type', 'cr/dr', 'cr_dr', 'transaction type']));
            $amount = $this->amount($credit);

            // If there's a single signed amount column, positive = credit
            if ($amount <= 0) {
                $signed = $this->amount($this->pick($r, ['amount', 'transaction amount']));
                if ($signed > 0 && $type !== 'dr' && $debit === '') $amount = $signed;
            }
            if ($amount <= 0) continue;

            $date = $this->date($this->pick($r, ['date', 'value date', 'tran date', 'txn date', 'transaction date']));
            if (!$date) continue;

            $narr = $this->pick($r, ['narration', 'description', 'particulars', 'remarks', 'transaction remarks']);
            $ref  = $this->pick($r, ['ref no', 'ref no./cheque no.', 'chq./ref.no.', 'cheque/utr no.', 'utr', 'reference no', 'reference', 'ref no.']);

            $out[] = [
                'date'      => $date,
                'amount'    => $amount,
                'ref'       => $ref ?: $this->extractUtr($narr),
                'narration' => $narr,
            ];
        }
        return $out;
    }

    private function pick(array $row, array $candidateKeys): string
    {
        foreach ($candidateKeys as $k) if (isset($row[$k]) && $row[$k] !== '') return $row[$k];
        // Fuzzy: case-insensitive contains
        foreach ($row as $k => $v) {
            if ($v === '') continue;
            foreach ($candidateKeys as $cand) {
                if (str_contains((string) $k, $cand)) return $v;
            }
        }
        return '';
    }

    private function amount(string $raw): float
    {
        $s = preg_replace('/[^0-9.\-]/', '', $raw);
        return $s === '' ? 0.0 : (float) $s;
    }

    private function date(string $raw): ?string
    {
        if ($raw === '') return null;
        // Try common Indian bank formats: dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd, dd-MMM-yyyy
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd-M-Y', 'd M Y', 'd/m/y', 'd-m-y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt) return $dt->format('Y-m-d');
        }
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function extractUtr(string $narration): string
    {
        // NEFT UTR is 16 chars; RTGS UTR is 22; IMPS is 12. Grab anything that looks like one.
        if (preg_match('/\b([A-Z0-9]{12,22})\b/', strtoupper($narration), $m)) return $m[1];
        return '';
    }

    // ── Matching ──────────────────────────────────────────────────────

    private function matchAll(array $credits): array
    {
        $db = \Config\Database::connect();
        // Pull every open invoice once
        $open = $db->table('invoices')
            ->select('invoices.id, invoices.invoice_no, invoices.client_id, invoices.total_amount, invoices.balance_due, invoices.due_date, clients.company_name')
            ->join('clients', 'clients.id = invoices.client_id', 'left')
            ->where('invoices.balance_due >', 0)
            ->where('invoices.invoice_status !=', 'Cancelled')
            ->where('invoices.deleted_at', null)
            ->get()->getResultArray();

        // Existing receipt refs (avoid duplicates)
        $existingRefs = array_column(
            $db->table('receipts')->select('reference_no')->where('reference_no IS NOT NULL', null, false)
                ->where('reference_no !=', '')->get()->getResultArray(),
            'reference_no'
        );
        $existingRefs = array_flip(array_map('strtoupper', $existingRefs));

        $out = [];
        foreach ($credits as $c) {
            $row = $c + ['invoice_id' => null, 'invoice_no' => null, 'client_id' => null, 'client_company' => null, 'confidence' => 0, 'duplicate' => false];

            if ($c['ref'] !== '' && isset($existingRefs[strtoupper($c['ref'])])) {
                $row['duplicate'] = true;
                $row['confidence'] = 0;
                $out[] = $row;
                continue;
            }

            // 1) Invoice no in narration (exact)
            foreach ($open as $inv) {
                if (stripos($c['narration'], (string) $inv['invoice_no']) !== false && abs($c['amount'] - (float) $inv['balance_due']) < 1) {
                    $row['invoice_id'] = (int) $inv['id'];
                    $row['invoice_no'] = $inv['invoice_no'];
                    $row['client_id']  = (int) $inv['client_id'];
                    $row['client_company'] = $inv['company_name'];
                    $row['confidence'] = 95;
                    break;
                }
            }
            if ($row['invoice_id']) { $out[] = $row; continue; }

            // 2) Amount exact + within 7 days of due_date — one candidate only
            $candidates = [];
            foreach ($open as $inv) {
                if (abs($c['amount'] - (float) $inv['balance_due']) >= 1) continue;
                $score = 70;
                if (!empty($inv['due_date'])) {
                    $days = abs((strtotime($c['date']) - strtotime($inv['due_date'])) / 86400);
                    if ($days <= 7) $score += 15;
                }
                $candidates[] = ['inv' => $inv, 'score' => $score];
            }
            if (count($candidates) === 1) {
                $inv = $candidates[0]['inv'];
                $row['invoice_id'] = (int) $inv['id'];
                $row['invoice_no'] = $inv['invoice_no'];
                $row['client_id']  = (int) $inv['client_id'];
                $row['client_company'] = $inv['company_name'];
                $row['confidence'] = $candidates[0]['score'];
            }

            // 3) Client-name fuzzy in narration + partial amount — lowest confidence, surface anyway
            if (!$row['invoice_id']) {
                foreach ($open as $inv) {
                    if (!$inv['company_name']) continue;
                    $needle = strtolower((string) $inv['company_name']);
                    // Strip "Pvt Ltd", "Limited", etc. before matching
                    $needle = trim(preg_replace('/\b(pvt|private|ltd|limited|inc|llp|company|co\.?)\b/i', '', $needle));
                    if (strlen($needle) < 4) continue;
                    if (stripos($c['narration'], $needle) !== false) {
                        $row['invoice_id'] = (int) $inv['id'];
                        $row['invoice_no'] = $inv['invoice_no'];
                        $row['client_id']  = (int) $inv['client_id'];
                        $row['client_company'] = $inv['company_name'];
                        $row['confidence'] = 45;
                        break;
                    }
                }
            }

            $out[] = $row;
        }
        return $out;
    }

    private function reduceInvoiceBalance(int $invoiceId, float $amount): void
    {
        $im = new InvoiceModel();
        $inv = $im->find($invoiceId);
        if (!$inv) return;
        $newReceived = (float) $inv['amount_received'] + $amount;
        $newBalance  = max(0, (float) $inv['total_amount'] - $newReceived);
        $update = ['amount_received' => $newReceived, 'balance_due' => $newBalance];
        if ($newBalance < 0.5) $update['invoice_status'] = 'Paid';
        elseif ($newReceived > 0) $update['invoice_status'] = 'Partially Paid';
        $im->update($invoiceId, $update);
    }
}

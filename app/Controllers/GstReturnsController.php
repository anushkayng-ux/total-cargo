<?php

namespace App\Controllers;

use App\Traits\ExportsCsv;
use App\Models\InvoiceModel;
use App\Models\SettingModel;

/**
 * GST returns export — generates the GSTR-1 (outward supplies, per-invoice)
 * and GSTR-3B (summary) datasets in CSV format that's importable into Tally,
 * ClearTax, or the GSTN offline tool.
 *
 *   GET /gst-returns                                — picker page (period selector)
 *   GET /gst-returns/gstr1.csv?from=YYYY-MM-DD&to=… — B2B invoice line items
 *   GET /gst-returns/gstr3b.csv?from=…&to=…         — summary numbers
 *
 * Notes:
 *   - We do NOT submit anything to the GSTN portal — that requires a paid GSP.
 *     This export is for offline upload via GSTN's Returns Offline Tool or
 *     for handing to your CA.
 *   - GSTR-1 here covers transport-of-goods (HSN/SAC 9965 / 996511).
 *     fcm5 = forward charge 5% (no ITC), fcm12 = 12% (with ITC), rcm = 0%
 *     (recipient pays under reverse charge).
 *   - Intrastate split between CGST + SGST is computed from the columns;
 *     interstate uses IGST. Place-of-supply is read from the client's state.
 */
class GstReturnsController extends BaseController
{
    use ExportsCsv;

    public function index()
    {
        $from = (string) ($this->request->getGet('from') ?: date('Y-m-01', strtotime('first day of last month')));
        $to   = (string) ($this->request->getGet('to')   ?: date('Y-m-t',  strtotime('last day of last month')));
        $summary = $this->summary($from, $to);
        return $this->render('gst_returns/index', [
            'pageTitle' => 'GST Returns [Accounts]',
            'from'      => $from,
            'to'        => $to,
            'summary'   => $summary,
        ], retroFixedShell: true);
    }

    /** GSTR-1 — per-invoice rows. Format: GSTIN-friendly column names. */
    public function gstr1()
    {
        $from = (string) ($this->request->getGet('from') ?: date('Y-m-01'));
        $to   = (string) ($this->request->getGet('to')   ?: date('Y-m-t'));
        $rows = $this->b2bInvoices($from, $to);

        $csvRows = [];
        foreach ($rows as $r) {
            $taxRate    = $this->ratePct((string) $r['gst_treatment']);
            $taxable    = (float) $r['taxable_amount'];
            $isIntra    = $this->isIntrastate((string) $r['client_state']);
            $csvRows[] = [
                $r['client_gstin']   ?? '',
                $r['invoice_no'],
                $this->fmtDate((string) $r['invoice_date']),
                number_format((float) $r['total_amount'], 2, '.', ''),
                $this->stateCode((string) $r['client_state']),
                'N',                                // Reverse Charge (Y/N)
                'Regular B2B',                      // Invoice Type
                '',                                 // E-Commerce GSTIN
                $taxRate,                           // Rate
                number_format($taxable, 2, '.', ''),
                $isIntra ? number_format((float) $r['cgst_amount'], 2, '.', '') : '0.00',
                $isIntra ? number_format((float) $r['sgst_amount'], 2, '.', '') : '0.00',
                $isIntra ? '0.00' : number_format((float) $r['igst_amount'], 2, '.', ''),
                '0.00',                             // Cess
            ];
        }

        return $this->streamCsv("gstr1-{$from}-to-{$to}.csv", [
            'GSTIN/UIN of Recipient',
            'Invoice Number', 'Invoice date', 'Invoice Value',
            'Place Of Supply (State code)', 'Reverse Charge',
            'Invoice Type', 'E-Commerce GSTIN',
            'Rate', 'Taxable Value', 'CGST Amount', 'SGST Amount', 'IGST Amount', 'Cess Amount',
        ], $csvRows);
    }

    /** GSTR-3B — summary rows + tax breakdown. */
    public function gstr3b()
    {
        $from = (string) ($this->request->getGet('from') ?: date('Y-m-01'));
        $to   = (string) ($this->request->getGet('to')   ?: date('Y-m-t'));
        $s    = $this->summary($from, $to);

        $rows = [
            ['Section', 'Description', 'Taxable Value', 'IGST', 'CGST', 'SGST', 'Cess'],
            ['3.1(a)', 'Outward taxable supplies (other than zero-rated, nil-rated, exempted)',
                number_format($s['taxable_taxable'], 2, '.', ''),
                number_format($s['igst'], 2, '.', ''),
                number_format($s['cgst'], 2, '.', ''),
                number_format($s['sgst'], 2, '.', ''),
                '0.00',
            ],
            ['3.1(b)', 'Outward taxable supplies (zero rated)',     '0.00','0.00','0.00','0.00','0.00'],
            ['3.1(c)', 'Other outward supplies (nil rated, exempted)', '0.00','0.00','0.00','0.00','0.00'],
            ['3.1(d)', 'Inward supplies (liable to reverse charge)',
                number_format($s['rcm_taxable'], 2, '.', ''),
                '0.00','0.00','0.00','0.00',
            ],
            ['3.1(e)', 'Non-GST outward supplies',                    '0.00','0.00','0.00','0.00','0.00'],
        ];

        // Use the trait with already-built rows
        return $this->streamCsv("gstr3b-{$from}-to-{$to}.csv", array_shift($rows), $rows);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function b2bInvoices(string $from, string $to): array
    {
        return (new InvoiceModel())
            ->select('invoices.*, clients.gst_no AS client_gstin, clients.state AS client_state, clients.company_name')
            ->join('clients', 'clients.id = invoices.client_id', 'left')
            ->where('invoices.invoice_date >=', $from)
            ->where('invoices.invoice_date <=', $to)
            ->where('invoices.invoice_status !=', 'Cancelled')
            ->where('invoices.deleted_at', null)
            ->orderBy('invoices.invoice_date', 'ASC')
            ->findAll();
    }

    private function summary(string $from, string $to): array
    {
        $rows = $this->b2bInvoices($from, $to);
        $out = [
            'count'          => count($rows),
            'taxable_taxable'=> 0,
            'rcm_taxable'    => 0,
            'cgst'           => 0,
            'sgst'           => 0,
            'igst'           => 0,
        ];
        foreach ($rows as $r) {
            $taxable = (float) $r['taxable_amount'];
            if ($r['gst_treatment'] === 'rcm') {
                $out['rcm_taxable'] += $taxable;
                continue;
            }
            $out['taxable_taxable'] += $taxable;
            $out['cgst'] += (float) $r['cgst_amount'];
            $out['sgst'] += (float) $r['sgst_amount'];
            $out['igst'] += (float) $r['igst_amount'];
        }
        return $out;
    }

    private function ratePct(string $treatment): string
    {
        return match ($treatment) {
            'fcm5'  => '5',
            'fcm12' => '12',
            default => '0',
        };
    }

    /** Our state code (from settings.gst_state) vs client's — interstate triggers IGST. */
    private function isIntrastate(string $clientState): bool
    {
        $our = (string) ((new SettingModel())->get('our_state', '') ?? '');
        if ($our === '' || $clientState === '') return true; // assume intrastate if unconfigured
        return strcasecmp(trim($our), trim($clientState)) === 0;
    }

    /** Map state name → 2-digit GST state code (subset; admins should fill via cities master). */
    private function stateCode(string $state): string
    {
        // Look up via cities master if available — falls back to a static map for common ones
        $row = \Config\Database::connect()->table('cities')
            ->select('gst_state_code')
            ->where('state', $state)
            ->limit(1)->get()->getRowArray();
        return $row['gst_state_code'] ?? '';
    }

    private function fmtDate(string $ymd): string
    {
        $ts = strtotime($ymd);
        return $ts ? date('d-m-Y', $ts) : '';
    }
}

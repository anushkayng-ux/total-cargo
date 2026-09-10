<?php

namespace App\Controllers\Portal;

class LedgerController extends BaseController
{
    public function index()
    {
        $cid  = (int) $this->clientAuth->clientId();
        $from = (string) $this->request->getGet('from');
        $to   = (string) $this->request->getGet('to');

        // Default window — last 90 days
        if ($from === '') $from = date('Y-m-d', strtotime('-90 days'));
        if ($to === '')   $to   = date('Y-m-d');

        $db = \Config\Database::connect();

        // Invoices (debits)
        $invs = $db->table('invoices')
            ->select("id, invoice_no, invoice_date AS dt, total_amount AS amount, balance_due, invoice_status, due_date")
            ->where('client_id', $cid)
            ->whereIn('invoice_status', ['Issued','Partially Paid','Paid'])
            ->where('deleted_at', null)
            ->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)
            ->get()->getResultArray();

        // Receipts (credits) — receipts table has no receipt_no column; we
        // synthesise RCT-<id> below for the ledger ref.
        $rcps = $db->table('receipts r')
            ->select("r.id, r.receipt_date AS dt, r.amount_received AS amount, r.payment_mode, r.reference_no, i.invoice_no")
            ->join('invoices i', 'i.id = r.invoice_id', 'left')
            ->where('i.client_id', $cid)
            ->where('r.receipt_date >=', $from)
            ->where('r.receipt_date <=', $to)
            ->get()->getResultArray();

        $entries = [];
        foreach ($invs as $i) {
            $entries[] = [
                'date'    => $i['dt'],
                'type'    => 'Invoice',
                'ref'     => $i['invoice_no'],
                'debit'   => (float) $i['amount'],
                'credit'  => 0,
                'note'    => $i['invoice_status'],
                'link'    => site_url('portal/invoices/' . $i['id']),
            ];
        }
        foreach ($rcps as $r) {
            $entries[] = [
                'date'    => $r['dt'],
                'type'    => 'Receipt',
                'ref'     => 'RCT-' . $r['id'],
                'debit'   => 0,
                'credit'  => (float) $r['amount'],
                'note'    => trim(($r['payment_mode'] ?? '') . ' ' . ($r['invoice_no'] ? '· ' . $r['invoice_no'] : '')),
                'link'    => $r['invoice_no'] ? null : null,
            ];
        }
        usort($entries, fn($a, $b) => strcmp((string) $a['date'], (string) $b['date']) ?: ($a['type'] <=> $b['type']));

        $running = 0;
        foreach ($entries as &$e) {
            $running += ($e['debit'] - $e['credit']);
            $e['balance'] = $running;
        }
        unset($e);

        $totalBilled = (float) $db->table('invoices')
            ->selectSum('total_amount', 'total')
            ->where('client_id', $cid)
            ->whereIn('invoice_status', ['Issued','Partially Paid','Paid'])
            ->where('deleted_at', null)
            ->get()->getRow('total');

        $totalReceived = (float) $db->table('receipts r')
            ->selectSum('r.amount_received', 'total')
            ->join('invoices i', 'i.id = r.invoice_id', 'left')
            ->where('i.client_id', $cid)
            ->get()->getRow('total');

        $outstanding = (float) $db->table('invoices')
            ->selectSum('balance_due', 'total')
            ->where('client_id', $cid)
            ->whereIn('invoice_status', ['Issued','Partially Paid'])
            ->where('deleted_at', null)
            ->get()->getRow('total');

        return $this->render('portal/ledger/index', [
            'pageTitle'     => 'Ledger',
            'entries'       => $entries,
            'from'          => $from,
            'to'            => $to,
            'totalBilled'   => $totalBilled,
            'totalReceived' => $totalReceived,
            'outstanding'   => $outstanding,
        ]);
    }
}

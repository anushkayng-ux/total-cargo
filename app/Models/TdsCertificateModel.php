<?php

namespace App\Models;

class TdsCertificateModel extends BaseModel
{
    protected $table          = 'tds_certificates';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'client_id', 'financial_year', 'quarter',
        'expected_amount', 'received_amount',
        'certificate_no', 'certificate_path', 'received_date',
        'status', 'notes', 'created_by',
    ];

    public const QUARTERS = ['Q1','Q2','Q3','Q4'];
    public const STATUSES = ['Pending','Received','Disputed','Reconciled'];

    /**
     * Compute expected TDS per (client, FY-quarter) from finalized invoices and
     * upsert the rows. Idempotent — safe to re-run.
     */
    public function reconcileExpected(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->query("
            SELECT client_id, invoice_date, COALESCE(tds_amount, 0) AS tds
              FROM invoices
             WHERE invoice_status IN ('Issued','Partially Paid','Paid')
               AND deleted_at IS NULL
               AND tds_amount > 0
        ")->getResultArray();

        $bucket = []; // keyed by client_id|fy|q
        foreach ($rows as $r) {
            [$fy, $q] = self::fyQuarter($r['invoice_date']);
            $key = $r['client_id'] . '|' . $fy . '|' . $q;
            if (!isset($bucket[$key])) $bucket[$key] = ['client_id' => (int) $r['client_id'], 'fy' => $fy, 'q' => $q, 'amt' => 0];
            $bucket[$key]['amt'] += (float) $r['tds'];
        }

        $created = 0; $updated = 0;
        foreach ($bucket as $b) {
            $existing = $this->where('client_id', $b['client_id'])
                ->where('financial_year', $b['fy'])
                ->where('quarter', $b['q'])->first();
            if ($existing) {
                $this->update($existing['id'], ['expected_amount' => round($b['amt'], 2)]);
                $updated++;
            } else {
                $this->insert([
                    'client_id'       => $b['client_id'],
                    'financial_year'  => $b['fy'],
                    'quarter'         => $b['q'],
                    'expected_amount' => round($b['amt'], 2),
                    'received_amount' => 0,
                    'status'          => 'Pending',
                ]);
                $created++;
            }
        }
        return ['created' => $created, 'updated' => $updated, 'total' => count($bucket)];
    }

    /** Indian financial year + quarter for a date. FY Apr-Mar; Q1=Apr-Jun, Q2=Jul-Sep, Q3=Oct-Dec, Q4=Jan-Mar. */
    public static function fyQuarter(string $date): array
    {
        $t = strtotime($date) ?: time();
        $m = (int) date('n', $t);
        $y = (int) date('Y', $t);
        if ($m >= 4) {
            $fy = $y . '-' . ($y + 1);
        } else {
            $fy = ($y - 1) . '-' . $y;
        }
        if     ($m >= 4  && $m <= 6)  $q = 'Q1';
        elseif ($m >= 7  && $m <= 9)  $q = 'Q2';
        elseif ($m >= 10 && $m <= 12) $q = 'Q3';
        else                           $q = 'Q4';
        return [$fy, $q];
    }
}

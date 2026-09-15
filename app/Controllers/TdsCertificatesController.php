<?php

namespace App\Controllers;

use App\Models\TdsCertificateModel;
use App\Models\ClientModel;

/** Quarterly TDS Form-16A collection workflow. Gated by feature_flag 'tds_certificates'. */
class TdsCertificatesController extends BaseController
{
    public function index()
    {
        $model = new TdsCertificateModel();
        $rows = $model->select('tds_certificates.*, clients.company_name, clients.client_code')
            ->join('clients', 'clients.id = tds_certificates.client_id', 'left')
            ->orderBy('financial_year', 'DESC')
            ->orderBy('quarter', 'DESC')
            ->orderBy('clients.company_name', 'ASC')
            ->paginate(50);

        // Aggregate totals in a single query instead of looping every row in PHP
        $agg = $model->builder()
            ->selectSum('expected_amount', 'expected')
            ->selectSum('received_amount', 'received')
            ->select("SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count", false)
            ->get()->getRowArray() ?: [];
        $totals = [
            'expected'      => (float) ($agg['expected'] ?? 0),
            'received'      => (float) ($agg['received'] ?? 0),
            'pending_count' => (int)   ($agg['pending_count'] ?? 0),
        ];

        return $this->render('tds_certificates/index', [
            'pageTitle' => 'TDS Certificates [Accounts] — List',
            'rows'      => $rows,
            'pager'     => $model->pager,
            'totals'    => $totals,
        ], retroFixedShell: true);
    }

    /** Recompute expected TDS per client × FY-quarter from finalized invoices. */
    public function reconcile()
    {
        $r = (new TdsCertificateModel())->reconcileExpected();
        return redirect()->to(site_url('tds-certificates'))
            ->with('success', "Reconciled: {$r['total']} rows ({$r['created']} new, {$r['updated']} updated).");
    }

    public function update(int $id)
    {
        $model = new TdsCertificateModel();
        $row = $model->find($id);
        if (!$row) return redirect()->to(site_url('tds-certificates'))->with('error', 'Not found.');

        $data = [
            'received_amount'  => (float) ($this->request->getPost('received_amount') ?: 0),
            'certificate_no'   => trim((string) $this->request->getPost('certificate_no')) ?: null,
            'received_date'    => $this->request->getPost('received_date') ?: null,
            'status'           => in_array($this->request->getPost('status'), TdsCertificateModel::STATUSES, true)
                                  ? $this->request->getPost('status') : $row['status'],
            'notes'            => $this->request->getPost('notes'),
        ];

        // Optional file upload
        $file = $this->request->getFile('certificate_file');
        if ($file && $file->isValid()) {
            $ext = strtolower($file->getExtension() ?: $file->getClientExtension());
            if (!in_array($ext, ['pdf','png','jpg','jpeg'], true)) {
                return redirect()->back()->with('error', 'Only PDF / PNG / JPG allowed.');
            }
            $dir = WRITEPATH . 'uploads/tds';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $name = 'tds-' . $id . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $file->move($dir, $name, true);
            $data['certificate_path'] = 'tds/' . $name;
        }

        $model->update($id, $data);
        \App\Libraries\AuditLogger::log('tds_certificates', $id, 'updated',
            "TDS row #$id updated (status={$data['status']}, received=₹" . number_format((float) $data['received_amount'], 2) . ")",
            $row, array_merge($row, $data));
        return redirect()->to(site_url('tds-certificates'))->with('success', 'TDS row updated.');
    }
}

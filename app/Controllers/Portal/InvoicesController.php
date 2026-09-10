<?php

namespace App\Controllers\Portal;

use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\ClientModel;
use App\Models\SettingModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicesController extends BaseController
{
    public function index()
    {
        $cid    = (int) $this->clientAuth->clientId();
        $status = (string) $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('q'));
        $model  = new InvoiceModel();

        $q = $model->where('invoices.client_id', $cid)->orderBy('invoices.id', 'DESC');
        if ($status !== '' && in_array($status, InvoiceModel::STATUSES, true)) {
            $q->where('invoices.invoice_status', $status);
        }
        if ($search !== '') {
            $q->groupStart()
                ->like('invoices.invoice_no', $search)
                ->groupEnd();
        }
        return $this->render('portal/invoices/index', [
            'pageTitle' => 'Invoices',
            'rows'      => $q->paginate(25),
            'pager'     => $model->pager,
            'status'    => $status,
            'search'    => $search,
            'statuses'  => InvoiceModel::STATUSES,
        ]);
    }

    public function show(int $id)
    {
        $row = (new InvoiceModel())->find($id);
        $row = $this->requireOwn($row);

        // Hide drafts from clients — staff hasn't issued the invoice yet.
        if ($row['invoice_status'] === 'Draft') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $items    = (new InvoiceItemModel())->forInvoice($id);
        $receipts = \Config\Database::connect()->table('receipts')
            ->where('invoice_id', $id)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return $this->render('portal/invoices/show', [
            'pageTitle' => 'Invoice ' . $row['invoice_no'],
            'row'       => $row,
            'items'     => $items,
            'receipts'  => $receipts,
        ]);
    }

    public function pdf(int $id)
    {
        $row = (new InvoiceModel())->find($id);
        $row = $this->requireOwn($row);
        if ($row['invoice_status'] === 'Draft') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();

        $html = view('invoices/pdf', [
            'row'      => $row,
            'items'    => (new InvoiceItemModel())->forInvoice($id),
            'client'   => !empty($row['client_id']) ? (new ClientModel())->find((int) $row['client_id']) : null,
            'settings' => (new SettingModel())->getAllGrouped(),
        ]);

        $opts = new Options();
        $opts->set('isRemoteEnabled', false);
        $opts->set('defaultFont', 'helvetica');

        $pdf = new Dompdf($opts);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $this->audit('invoice', $id, 'download_pdf');

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $row['invoice_no'] . '.pdf"')
            ->setBody($pdf->output());
    }
}

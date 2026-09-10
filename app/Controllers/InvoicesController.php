<?php

namespace App\Controllers;

use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\ReceiptModel;
use App\Models\EinvoiceLogModel;
use App\Models\ClientModel;
use App\Models\BookingModel;
use App\Models\TripModel;
use App\Models\SettingModel;
use App\Models\WhatsappTemplateModel;
use App\Models\TripExpenseModel;
use App\Libraries\NumberGenerator;
use App\Libraries\WhatsAppService;
use App\Libraries\ClearTaxService;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicesController extends BaseController
{
    use \App\Traits\ExportsCsv;

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $due    = (string) $this->request->getGet('due');

        $model = $this->filteredQuery($search, $status, $due);

        return $this->render('invoices/index', [
            'pageTitle' => 'Invoices',
            'rows'      => $model->paginate($this->perPage()),
            'pager'     => (new InvoiceModel())->pager,
            'search'    => $search,
            'status'    => $status,
            'due'       => $due,
            'statuses'  => InvoiceModel::STATUSES,
        ]);
    }

    /** GET /invoices/export — honors the same q/status/due filters as index */
    public function export()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $due    = (string) $this->request->getGet('due');

        $rows = $this->filteredQuery($search, $status, $due)->findAll();
        return $this->streamCsv('invoices-' . date('Y-m-d') . '.csv', [
            'invoice_no'      => 'Invoice No',
            'invoice_date'    => 'Date',
            'client_company'  => 'Client',
            'booking_no'      => 'Booking',
            'invoice_status'  => 'Status',
            'total_amount'    => 'Total',
            'balance_due'     => 'Balance Due',
            'due_date'        => 'Due Date',
            'irn_no'          => 'IRN',
        ], $rows);
    }

    /** Shared filter pipeline used by both index() and export(). */
    private function filteredQuery(string $search, string $status, string $due)
    {
        $model = new InvoiceModel();
        $q     = $model->withJoins()->orderBy('invoices.id', 'DESC');
        if ($search !== '') {
            $q->groupStart()
                ->like('invoices.invoice_no', $search)
                ->orLike('clients.company_name', $search)
                ->orLike('bookings.booking_no', $search)
                ->orLike('bookings.lr_no', $search)
                ->orLike('trips.lr_no', $search)
                ->orLike('trips.trip_no', $search)
                ->orLike('invoices.irn_no', $search)
                ->groupEnd();
        }
        if ($status !== '') $q->where('invoices.invoice_status', $status);
        if ($due === 'overdue') {
            $q->where('invoices.balance_due >', 0)->where('invoices.due_date <', date('Y-m-d'));
        }

        // Ownership scope — non-admins only see invoices for their clients.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) $q->whereIn('invoices.client_id', $ownedIds ?: [0]);

        return $q;
    }

    public function create()
    {
        return $this->renderForm(null);
    }

    public function createFromTrip(int $tripId)
    {
        $trip = (new TripModel())->withJoins()->where('trips.id', $tripId)->first();
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $booking   = (new BookingModel())->find((int) $trip['booking_id']);
        $gstRate   = (float) ((new SettingModel())->get('default_gst_rate', '0'));
        $expenses  = (new TripExpenseModel())->unbilledBillableForTrip($tripId);

        $lines = [[
            'description' => sprintf('Freight charges — %s %s%s',
                                     $trip['vehicle_number'] ?? '',
                                     $trip['route_text']     ?? '',
                                     $booking['load_details'] ? ' (' . $booking['load_details'] . ')' : ''),
            'hsn_sac'     => '996791',
            'qty'         => 1,
            'rate'        => (float) ($booking['final_sell_rate'] ?? 0),
            'gst_percent' => $gstRate,
            'expense_id'  => 0,
        ]];
        foreach ($expenses as $exp) {
            $lines[] = [
                'description' => 'Reimbursable — ' . $exp['category'] . ($exp['description'] ? ' · ' . $exp['description'] : ''),
                'hsn_sac'     => '996791',
                'qty'         => 1,
                'rate'        => (float) $exp['amount'],
                'gst_percent' => $gstRate,
                'expense_id'  => (int) $exp['id'],
            ];
        }

        $prefill = [
            'client_id'   => $booking['client_id'] ?? null,
            'booking_id'  => $booking['id'] ?? null,
            'trip_id'     => $tripId,
            'items'       => $lines,
        ];
        return $this->renderForm(null, $prefill);
    }

    /**
     * Consolidated billing — pick a client and see all their delivered/closed
     * trips that haven't been invoiced yet. Ticking rows and hitting the
     * generate button bundles them into a single invoice with one line per LR.
     * Trips already linked to any invoice line are excluded automatically.
     */
    public function consolidatedPicker()
    {
        $clientId = (int) ($this->request->getGet('client_id') ?? 0);
        $clients  = (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll();

        $trips  = [];
        $client = null;
        if ($clientId > 0) {
            $client = (new ClientModel())->find($clientId);
            $billedIds = (new InvoiceItemModel())->billedTripIds();
            $q = (new TripModel())->withJoins()
                ->select('trips.*, bookings.final_sell_rate, bookings.pickup_city, bookings.drop_city, bookings.charge_weight_kg')
                ->where('bookings.client_id', $clientId)
                ->whereIn('trips.current_status', ['Delivered','POD Received','Closed'])
                ->where('trips.deleted_at', null);
            if (!empty($billedIds)) $q->whereNotIn('trips.id', $billedIds);
            $trips = $q->orderBy('trips.created_at', 'DESC')->findAll();
        }

        return $this->render('invoices/consolidated', [
            'pageTitle' => 'Combined Invoice — pick trips',
            'clients'   => $clients,
            'clientId'  => $clientId,
            'client'    => $client,
            'trips'     => $trips,
        ]);
    }

    /** POST — build the consolidated invoice from selected trip ids. */
    public function consolidatedCreate()
    {
        $clientId = (int) $this->request->getPost('client_id');
        $tripIds  = array_map('intval', (array) $this->request->getPost('trip_ids'));
        $tripIds  = array_filter($tripIds, static fn ($x) => $x > 0);

        if ($clientId <= 0 || empty($tripIds)) {
            return redirect()->back()->with('error', 'Pick a client and at least one trip.');
        }

        // Guard against re-billing anything already invoiced (belt-and-braces
        // beyond the picker's exclusion — form could be stale).
        $billedIds = (new InvoiceItemModel())->billedTripIds();
        $tripIds   = array_values(array_diff($tripIds, $billedIds));
        if (empty($tripIds)) {
            return redirect()->back()->with('error', 'Every selected trip was already billed.');
        }

        $trips = (new TripModel())->withJoins()
            ->select('trips.*, bookings.final_sell_rate, bookings.pickup_city, bookings.drop_city, bookings.load_details')
            ->whereIn('trips.id', $tripIds)
            ->findAll();

        $gstRate = (float) ((new SettingModel())->get('default_gst_rate', '0'));
        $lines   = [];
        $primaryBooking = null;
        foreach ($trips as $t) {
            if (!$primaryBooking && !empty($t['booking_id'])) $primaryBooking = (int) $t['booking_id'];
            $lines[] = [
                'description' => sprintf('LR %s — %s %s',
                                     $t['lr_no'] ?? ($t['trip_no'] ?? ''),
                                     $t['vehicle_number'] ?? '',
                                     tpt_route(($t['pickup_city'] ?? '') . ' - ' . ($t['drop_city'] ?? ''))),
                'hsn_sac'     => '996791',
                'qty'         => 1,
                'rate'        => (float) ($t['final_sell_rate'] ?? 0),
                'gst_percent' => $gstRate,
                'trip_id'     => (int) $t['id'],
                'expense_id'  => 0,
            ];
        }

        $taxable = 0.0; $gst = 0.0;
        foreach ($lines as $l) {
            $t = (float) $l['rate'] * (float) $l['qty'];
            $taxable += $t;
            $gst     += round($t * (float) $l['gst_percent'] / 100, 2);
        }
        $total = round($taxable + $gst, 2);

        // Header. First trip's booking used as the primary booking_id / trip_id.
        (new InvoiceModel())->insert([
            'invoice_no'      => NumberGenerator::invoice(),
            'invoice_date'    => date('Y-m-d'),
            'client_id'       => $clientId,
            'booking_id'      => $primaryBooking,
            'trip_id'         => (int) ($trips[0]['id'] ?? 0),
            'taxable_amount'  => $taxable,
            'gst_amount'      => $gst,
            'total_amount'    => $total,
            'balance_due'     => $total,
            'invoice_status'  => 'Draft',
            'created_by'      => $this->auth->id(),
        ]);
        $invoiceId = (int) \Config\Database::connect()->insertID();

        // Rows.
        foreach ($lines as $l) {
            $taxableRow = (float) $l['rate'] * (float) $l['qty'];
            $gstRow     = round($taxableRow * (float) $l['gst_percent'] / 100, 2);
            (new InvoiceItemModel())->insert([
                'invoice_id'     => $invoiceId,
                'description'    => $l['description'],
                'hsn_sac'        => $l['hsn_sac'],
                'qty'            => $l['qty'],
                'rate'           => $l['rate'],
                'taxable_amount' => $taxableRow,
                'gst_percent'    => $l['gst_percent'],
                'gst_amount'     => $gstRow,
                'total_amount'   => $taxableRow + $gstRow,
                'trip_id'        => $l['trip_id'],
            ]);
        }

        return redirect()->to(site_url('invoices/' . $invoiceId))
            ->with('success', 'Combined invoice created with ' . count($lines) . ' LR(s).');
    }

    public function edit(int $id)
    {
        $row = (new InvoiceModel())->find($id);
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        if ($row['invoice_status'] !== 'Draft') {
            return redirect()->to(site_url('invoices/' . $id))->with('error', 'Cannot edit a finalized invoice.');
        }
        $row['items'] = (new InvoiceItemModel())->forInvoice($id);
        return $this->renderForm($row);
    }

    private function renderForm(?array $row, array $prefill = []): string
    {
        return $this->render('invoices/form', [
            'pageTitle' => $row ? 'Edit Invoice' : 'New Invoice',
            'row'       => $row,
            'prefill'   => $prefill,
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'bookings'  => (new BookingModel())->orderBy('id', 'DESC')->limit(100)->findAll(),
            'trips'     => (new TripModel())->orderBy('id', 'DESC')->limit(100)->findAll(),
            'settings'  => (new SettingModel())->getAllGrouped(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        [$invData, $items] = $this->computeInvoiceData($post);
        // Manual override if operator typed an invoice_no; else auto-generate.
        // Uniqueness enforced by the invoices.invoice_no unique index.
        $manualNo = trim((string) ($post['invoice_no'] ?? ''));
        if ($manualNo !== '') {
            $dup = (new InvoiceModel())->where('invoice_no', $manualNo)->first();
            if ($dup) {
                return redirect()->back()->withInput()->with('error', 'Invoice number "' . $manualNo . '" already exists (invoice #' . $dup['id'] . '). Pick a different one or leave blank to auto-generate.');
            }
            $invData['invoice_no'] = $manualNo;
        } else {
            $invData['invoice_no'] = NumberGenerator::invoice();
        }
        $invData['invoice_status'] = 'Draft';
        $invData['created_by']     = $this->auth->id();

        (new InvoiceModel())->insert($invData);
        $id = (int) \Config\Database::connect()->insertID();
        $this->saveItems($id, $items);
        (new InvoiceModel())->recomputeBalance($id);

        $this->markExpensesBilled($id, (array) ($post['line_expense_id'] ?? []));

        // Notify Accounts a new invoice was raised.
        \App\Libraries\Notify::toPermission('invoices', 'can_view',
            'Invoice created — ' . $invData['invoice_no'],
            'A new invoice was raised (Draft)',
            site_url('invoices/' . $id),
            ['type' => 'invoice_created', 'icon' => 'receipt']);

        return redirect()->to(site_url('invoices/' . $id))->with('success', 'Invoice ' . $invData['invoice_no'] . ' created as Draft.');
    }

    public function update(int $id)
    {
        $row = (new InvoiceModel())->find($id);
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        if ($row['invoice_status'] !== 'Draft') {
            return redirect()->to(site_url('invoices/' . $id))->with('error', 'Finalized invoices cannot be edited.');
        }

        $post = $this->request->getPost();
        [$invData, $items] = $this->computeInvoiceData($post);
        $invData['updated_by'] = $this->auth->id();

        // Allow re-numbering a Draft invoice — enforces uniqueness.
        $manualNo = trim((string) ($post['invoice_no'] ?? ''));
        if ($manualNo !== '' && $manualNo !== (string) $row['invoice_no']) {
            $dup = (new InvoiceModel())->where('invoice_no', $manualNo)->where('id !=', $id)->first();
            if ($dup) {
                return redirect()->back()->withInput()->with('error', 'Invoice number "' . $manualNo . '" already exists (invoice #' . $dup['id'] . ').');
            }
            $invData['invoice_no'] = $manualNo;
        }

        (new InvoiceModel())->update($id, $invData);

        // Replace items
        $db = \Config\Database::connect();
        $db->table('invoice_items')->where('invoice_id', $id)->delete();
        $this->saveItems($id, $items);
        (new InvoiceModel())->recomputeBalance($id);

        // Release any previously-billed expenses that are no longer on this invoice,
        // then re-mark those that are still present.
        $db->table('trip_expenses')
            ->where('billed_on_invoice_id', $id)
            ->update(['billed_on_invoice_id' => null]);
        $this->markExpensesBilled($id, (array) ($post['line_expense_id'] ?? []));

        return redirect()->to(site_url('invoices/' . $id))->with('success', 'Invoice updated.');
    }

    private function markExpensesBilled(int $invoiceId, array $expenseIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $expenseIds))));
        if (empty($ids)) return;
        (new TripExpenseModel())
            ->whereIn('id', $ids)
            ->where('is_billable', 1)
            ->set(['billed_on_invoice_id' => $invoiceId, 'updated_by' => $this->auth->id()])
            ->update();
    }

    /** Turn the posted form into invoice + items arrays, computing taxes correctly for intra vs inter-state. */
    private function computeInvoiceData(array $post): array
    {
        $settings      = (new SettingModel())->getAllGrouped();
        $companyState  = (string) ($settings['company']['company_state'] ?? '');
        $client        = !empty($post['client_id']) ? (new ClientModel())->find((int) $post['client_id']) : null;
        $clientState   = (string) ($client['state'] ?? '');
        $interstate    = ($clientState !== '' && $companyState !== '' && $this->slug($clientState) !== $this->slug($companyState));

        $descs  = (array) ($post['line_description'] ?? []);
        $hsns   = (array) ($post['line_hsn']         ?? []);
        $qtys   = (array) ($post['line_qty']         ?? []);
        $rates  = (array) ($post['line_rate']        ?? []);
        $gstPcs = (array) ($post['line_gst']         ?? []);

        $items = [];
        $taxable = 0.0;
        $gstSum  = 0.0;
        for ($i = 0; $i < count($descs); $i++) {
            $desc = trim((string) $descs[$i]);
            if ($desc === '') continue;
            $qty  = (float) ($qtys[$i]  ?? 1);
            $rate = (float) ($rates[$i] ?? 0);
            $gstP = (float) ($gstPcs[$i] ?? 0);

            $ti   = round($qty * $rate, 2);
            $ga   = round($ti * $gstP / 100, 2);
            $tot  = round($ti + $ga, 2);
            $taxable += $ti;
            $gstSum  += $ga;

            $items[] = [
                'description'    => $desc,
                'hsn_sac'        => trim((string) ($hsns[$i] ?? '')),
                'qty'            => $qty,
                'rate'           => $rate,
                'taxable_amount' => $ti,
                'gst_percent'    => $gstP,
                'gst_amount'     => $ga,
                'total_amount'   => $tot,
            ];
        }

        // ── GTA / RCM tax-mechanism logic ─────────────────────────────
        // RCM: client pays GST under reverse charge → invoice shows tax = 0
        // FCM 5% / FCM 12%: transporter pays → use the line items' GST as is
        $treatment = $client['gst_treatment'] ?? 'fcm5';
        if ($treatment === 'rcm') {
            $cgst = 0; $sgst = 0; $igst = 0;
        } else {
            $cgst = $interstate ? 0 : round($gstSum / 2, 2);
            $sgst = $interstate ? 0 : round($gstSum / 2, 2);
            $igst = $interstate ? round($gstSum, 2) : 0;
        }

        // ── Detention auto-pull from the linked trip (if any) ─────────
        $detention = 0.0;
        if (!empty($post['trip_id'])) {
            $trip = (new \App\Models\TripModel())->find((int) $post['trip_id']);
            if ($trip) {
                $detention = (float) ($trip['detention_amount'] ?? 0);
            }
        }
        // Allow staff override
        if (isset($post['detention_amount']) && $post['detention_amount'] !== '') {
            $detention = (float) $post['detention_amount'];
        }

        $preRound  = round($taxable + $cgst + $sgst + $igst + $detention, 2);
        $total     = round($preRound);
        $roundOff  = round($total - $preRound, 2);

        // ── TDS @ client's rate (Sec 194C). Net receivable = total − TDS. ─
        $tdsRate = (float) ($client['tds_rate'] ?? 0);
        if (isset($post['tds_rate']) && $post['tds_rate'] !== '') $tdsRate = (float) $post['tds_rate'];
        $tdsAmount = round($total * $tdsRate / 100, 2);
        $netReceivable = round($total - $tdsAmount, 2);

        $invData = [
            'invoice_date'     => $post['invoice_date']    ?: date('Y-m-d'),
            'client_id'        => !empty($post['client_id'])  ? (int) $post['client_id']  : null,
            'booking_id'       => !empty($post['booking_id']) ? (int) $post['booking_id'] : null,
            'trip_id'          => !empty($post['trip_id'])    ? (int) $post['trip_id']    : null,
            'taxable_amount'   => round($taxable, 2),
            'cgst_amount'      => $cgst,
            'sgst_amount'      => $sgst,
            'igst_amount'      => $igst,
            'gst_treatment'    => $treatment,
            'detention_amount' => round($detention, 2),
            'tds_rate'         => $tdsRate,
            'tds_amount'       => $tdsAmount,
            'net_receivable'   => $netReceivable,
            'round_off'        => $roundOff,
            'total_amount'     => $total,
            'due_date'         => $post['due_date'] ?: null,
            'notes'            => $post['notes'] ?? null,
            'payment_terms'    => $post['payment_terms'] ?? null,
        ];

        return [$invData, $items];
    }

    private function saveItems(int $invoiceId, array $items): void
    {
        if (empty($items)) return;
        $model = new InvoiceItemModel();
        foreach ($items as $it) {
            $it['invoice_id'] = $invoiceId;
            $model->insert($it);
        }
    }

    public function show(int $id)
    {
        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');

        return $this->render('invoices/show', [
            'pageTitle' => 'Invoice ' . $row['invoice_no'],
            'row'       => $row,
            'items'     => (new InvoiceItemModel())->forInvoice($id),
            'receipts'  => (new ReceiptModel())->forInvoice($id),
            'einvoice'  => (new EinvoiceLogModel())->where('invoice_id', $id)->orderBy('id', 'DESC')->first(),
            'client'    => !empty($row['client_id']) ? (new ClientModel())->find((int) $row['client_id']) : null,
            'settings'  => (new SettingModel())->getAllGrouped(),
            'waService' => new WhatsAppService(),
            'clearTax'  => new ClearTaxService(),
        ]);
    }

    public function finalize(int $id)
    {
        $row = (new InvoiceModel())->find($id);
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        if ($row['invoice_status'] !== 'Draft') {
            return redirect()->to(site_url('invoices/' . $id))->with('error', 'Already finalized.');
        }
        (new InvoiceModel())->update($id, [
            'invoice_status' => 'Issued',
            'updated_by'     => $this->auth->id(),
        ]);

        // Notify Accounts the invoice is issued (collection can begin) + admin.
        \App\Libraries\Notify::toPermission('invoices', 'can_view',
            'Invoice issued — ' . ($row['invoice_no'] ?? ('#' . $id)),
            'Total ₹' . number_format((float) ($row['total_amount'] ?? 0), 2) . ' — ready to send & collect',
            site_url('invoices/' . $id),
            ['type' => 'invoice_issued', 'icon' => 'receipt-cutoff']);

        return redirect()->to(site_url('invoices/' . $id))->with('success', 'Invoice finalized.');
    }

    public function cancel(int $id)
    {
        (new InvoiceModel())->update($id, ['invoice_status' => 'Cancelled', 'updated_by' => $this->auth->id()]);
        return redirect()->to(site_url('invoices/' . $id))->with('success', 'Invoice cancelled.');
    }

    public function delete(int $id)
    {
        (new InvoiceModel())->delete($id);
        return redirect()->to(site_url('invoices'))->with('success', 'Invoice removed.');
    }

    /** Render a print-friendly invoice and stream as PDF via dompdf. */
    public function pdf(int $id)
    {
        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');

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

        // Persist a copy so WhatsApp share can reference it
        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'invoices';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $stored = $row['invoice_no'] . '.pdf';
        file_put_contents($dir . DIRECTORY_SEPARATOR . $stored, $pdf->output());
        (new InvoiceModel())->update($id, ['pdf_path' => 'invoices/' . $stored]);

        // Stream inline
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $stored . '"')
            ->setBody($pdf->output());
    }

    public function shareWa(int $id)
    {
        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        if ($row['invoice_status'] === 'Draft') {
            return redirect()->back()->with('error', 'Finalize the invoice before sharing.');
        }

        $client = !empty($row['client_id']) ? (new ClientModel())->find((int) $row['client_id']) : null;
        $to = (string) ($client['mobile'] ?? '');
        if (!$to) return redirect()->back()->with('error', 'Client mobile number missing.');

        $template = (new WhatsappTemplateModel())->getByKey('invoice_share');
        if (!$template) return redirect()->back()->with('error', 'Template "invoice_share" missing.');

        $vars = [
            $row['invoice_no'],
            number_format((float) $row['total_amount'], 2),
            $row['due_date'] ?: '—',
        ];
        (new WhatsAppService())->sendTemplate($to, $template['template_name'], $vars, $template['language_code'] ?? 'en', [
            'module_name' => 'invoice', 'module_ref_id' => $id, 'audience_type' => 'client', 'template_key' => 'invoice_share',
        ]);

        return redirect()->to(site_url('invoices/' . $id))->with('success', 'WhatsApp invoice share queued.');
    }

    public function sendPaymentReminder(int $id)
    {
        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        $client = !empty($row['client_id']) ? (new ClientModel())->find((int) $row['client_id']) : null;
        $to = (string) ($client['mobile'] ?? '');
        if (!$to) return redirect()->back()->with('error', 'Client mobile number missing.');

        $template = (new WhatsappTemplateModel())->getByKey('payment_reminder');
        if (!$template) return redirect()->back()->with('error', 'Template "payment_reminder" missing.');

        $vars = [
            $row['invoice_no'],
            number_format((float) $row['balance_due'], 2),
            $row['due_date'] ?: '—',
        ];
        (new WhatsAppService())->sendTemplate($to, $template['template_name'], $vars, $template['language_code'] ?? 'en', [
            'module_name' => 'invoice', 'module_ref_id' => $id, 'audience_type' => 'client', 'template_key' => 'payment_reminder',
        ]);
        return redirect()->to(site_url('invoices/' . $id))->with('success', 'Payment reminder queued.');
    }

    /** POST /invoices/bulk — fan-out reminders to selected unpaid invoices */
    public function bulk()
    {
        $ids    = array_map('intval', (array) $this->request->getPost('ids'));
        $action = (string) $this->request->getPost('action');
        if (empty($ids) || $action === '') return redirect()->back()->with('error', 'Select rows and an action.');

        $ok = 0; $skipped = 0;
        foreach ($ids as $id) {
            try {
                if ($action === 'remind')        $this->sendPaymentReminder($id);
                elseif ($action === 'remind_email') $this->sendPaymentReminderEmail($id);
                else { $skipped++; continue; }
                $ok++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }
        return redirect()->to(site_url('invoices'))->with('success', "$ok reminder(s) queued · $skipped skipped");
    }

    /** Email the invoice (PDF attached) using the invoice_share email template. */
    public function shareEmail(int $id)
    {
        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        if ($row['invoice_status'] === 'Draft') {
            return redirect()->back()->with('error', 'Finalize the invoice before sharing.');
        }

        $client = !empty($row['client_id']) ? (new ClientModel())->find((int) $row['client_id']) : null;
        $to     = (string) ($client['email'] ?? '');
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Client email missing or invalid.');
        }

        // Make sure the PDF exists — render now if it has not been generated yet,
        // otherwise the email goes out with no attachment.
        $pdfPath = !empty($row['pdf_path']) ? WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $row['pdf_path']) : null;
        if (!$pdfPath || !is_file($pdfPath)) {
            try {
                $html = view('invoices/pdf', [
                    'row'      => $row,
                    'items'    => (new InvoiceItemModel())->forInvoice($id),
                    'client'   => $client,
                    'settings' => (new SettingModel())->getAllGrouped(),
                ]);
                $opts = new Options();
                $opts->set('isRemoteEnabled', false);
                $opts->set('defaultFont', 'helvetica');
                $pdf = new Dompdf($opts);
                $pdf->loadHtml($html);
                $pdf->setPaper('A4', 'portrait');
                $pdf->render();

                $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'invoices';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $stored = $row['invoice_no'] . '.pdf';
                file_put_contents($dir . DIRECTORY_SEPARATOR . $stored, $pdf->output());
                (new InvoiceModel())->update($id, ['pdf_path' => 'invoices/' . $stored]);
                $pdfPath = $dir . DIRECTORY_SEPARATOR . $stored;
            } catch (\Throwable $e) {
                log_message('warning', 'Eager invoice PDF render failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Could not generate the invoice PDF — please try the PDF action first, then resend.');
            }
        }

        $vars = [
            'invoice_no'   => $row['invoice_no'],
            'total'        => number_format((float) $row['total_amount'], 2),
            'due_date'     => $row['due_date'] ?: '—',
            'company'      => $client['company_name'] ?? '',
            'contact'      => $client['contact_name'] ?? '',
            'view_url'     => site_url('portal/invoices/' . $id),
            // numeric fallback (legacy {{1}}…{{5}})
            0 => $row['invoice_no'],
            1 => number_format((float) $row['total_amount'], 2),
            2 => $row['due_date'] ?: '—',
            3 => $client['company_name'] ?? '',
            4 => site_url('portal/invoices/' . $id),
        ];

        $opts = [
            'to_name'           => $client['company_name'] ?? '',
            'related_module'    => 'invoice',
            'related_id'        => $id,
            'related_client_id' => $client['id'] ?? null,
            'sent_by_user_id'   => $this->auth->id(),
        ];
        if ($pdfPath && is_file($pdfPath)) {
            $opts['attachments'] = [['path' => $pdfPath, 'name' => $row['invoice_no'] . '.pdf']];
        }

        $svc = new \App\Libraries\EmailService();
        $res = $svc->sendTemplate('invoice_share', $to, $vars, $opts);
        if (!empty($res['ok']) || !empty($res['queued'])) {
            return redirect()->to(site_url('invoices/' . $id))->with('success', 'Invoice email sent (or queued if no driver configured).');
        }
        return redirect()->back()->with('error', 'Email send failed: ' . ($res['error'] ?? 'unknown'));
    }

    /** Email payment reminder. */
    public function sendPaymentReminderEmail(int $id)
    {
        $row = (new InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        $client = !empty($row['client_id']) ? (new ClientModel())->find((int) $row['client_id']) : null;
        $to     = (string) ($client['email'] ?? '');
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Client email missing or invalid.');
        }
        $vars = [
            'invoice_no' => $row['invoice_no'],
            'balance'    => number_format((float) $row['balance_due'], 2),
            'due_date'   => $row['due_date'] ?: '—',
            'company'    => $client['company_name'] ?? '',
            'view_url'   => site_url('portal/invoices/' . $id),
            0 => $row['invoice_no'],
            1 => number_format((float) $row['balance_due'], 2),
            2 => $row['due_date'] ?: '—',
            3 => site_url('portal/invoices/' . $id),
        ];
        $svc = new \App\Libraries\EmailService();
        $svc->sendTemplate('payment_reminder', $to, $vars, [
            'related_module'    => 'invoice',
            'related_id'        => $id,
            'related_client_id' => $client['id'] ?? null,
            'sent_by_user_id'   => $this->auth->id(),
        ]);
        return redirect()->to(site_url('invoices/' . $id))->with('success', 'Payment reminder email queued.');
    }

    public function generateIrn(int $id)
    {
        $row = (new InvoiceModel())->find($id);
        if (!$row) return redirect()->to(site_url('invoices'))->with('error', 'Not found.');
        if (!empty($row['irn_no'])) {
            return redirect()->back()->with('error', 'IRN already generated for this invoice.');
        }

        $result = (new ClearTaxService())->generateIrn($id);
        if (!empty($result['ok'])) {
            return redirect()->to(site_url('invoices/' . $id))->with('success', 'IRN ' . $result['irn'] . ' generated.');
        }
        $msg = !empty($result['queued'])
            ? 'ClearTax credentials not set — payload queued in e-invoice log.'
            : ('IRN request failed: ' . ($result['error'] ?? 'unknown'));
        return redirect()->to(site_url('invoices/' . $id))->with('error', $msg);
    }

    private function slug(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $s));
    }
}

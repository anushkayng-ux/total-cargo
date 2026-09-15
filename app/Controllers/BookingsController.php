<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\TripModel;
use App\Models\TripStatusHistoryModel;
use App\Models\RfqModel;
use App\Models\QuotationModel;
use App\Models\LeadModel;
use App\Models\LeadStatusHistoryModel;
use App\Models\ClientModel;
use App\Models\VendorModel;
use App\Libraries\NumberGenerator;

class BookingsController extends BaseController
{
    use \App\Traits\ExportsCsv;

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $model  = $this->filteredQuery($search, $status);

        return $this->render('bookings/index', [
            'pageTitle' => 'GR/LR Booking [Transport] — List',
            'rows'      => $model->paginate($this->perPage()),
            'pager'     => $model->pager,
            'search'    => $search,
            'status'    => $status,
            'statuses'  => BookingModel::STATUSES,
        ], retroFixedShell: true);
    }

    public function export()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $rows   = $this->filteredQuery($search, $status)->findAll();
        return $this->streamCsv('bookings-' . date('Y-m-d') . '.csv', [
            'booking_no'       => 'Booking No',
            'created_at'       => 'Created',
            'clients_company'  => 'Client',
            'vendors_company'  => 'Vendor',
            'route_text'       => 'Route',
            'vehicle_type'     => 'Vehicle Type',
            'loading_date'     => 'Loading Date',
            'booking_status'   => 'Status',
            'sell_amount'      => 'Sell',
            'buy_amount'       => 'Buy',
        ], $rows);
    }

    private function filteredQuery(string $search, string $status)
    {
        $model = new BookingModel();
        $q     = $model->withJoins()->orderBy('bookings.id', 'DESC');
        if ($search !== '') {
            $q->groupStart()
                ->like('bookings.booking_no', $search)
                ->orLike('bookings.lr_no', $search)
                ->orLike('bookings.ewb_no', $search)
                ->orLike('clients.company_name', $search)
                ->orLike('vendors.company_name', $search)
                ->orLike('bookings.route_text', $search)
                ->groupEnd();
        }
        if ($status !== '') $q->where('bookings.booking_status', $status);

        // Ownership scope — non-admins see bookings whose CLIENT is theirs
        // OR whose per-record assigned_to points at them (so a lead-generator
        // can hand off a specific booking to another sales rep).
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            $uid = (int) $this->auth->id();
            $q->groupStart()
                ->whereIn('bookings.client_id', $ownedIds ?: [0])
                ->orWhere('bookings.assigned_to', $uid)
              ->groupEnd();
        }

        return $q;
    }

    /**
     * Convert awarded RFQ into a booking.
     * Pre-fills from RFQ + the is_final_selected quotation.
     */
    public function createFromRfq(int $rfqId)
    {
        $rfq = (new RfqModel())->find($rfqId);
        if (!$rfq) return redirect()->to(site_url('rfq'))->with('error', 'RFQ not found.');

        $final = (new QuotationModel())->where('rfq_id', $rfqId)->where('is_final_selected', 1)->first();
        if (!$final) return redirect()->to(site_url('rfq/' . $rfqId))->with('error', 'Award a quotation first.');

        $lead = !empty($rfq['lead_id']) ? (new LeadModel())->find((int) $rfq['lead_id']) : null;

        return $this->render('bookings/form', [
            'pageTitle' => 'Booking from RFQ ' . $rfq['rfq_no'],
            'row'       => null,
            'rfq'       => $rfq,
            'quote'     => $final,
            'lead'      => $lead,
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ]);
    }

    public function create()
    {
        return $this->render('bookings/form', [
            'pageTitle' => 'GR/LR Booking [New]',
            'row'       => null, 'rfq' => null, 'quote' => null, 'lead' => null,
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function store()
    {
        $post    = $this->request->getPost();

        // Workflow: Booking → Trip → Create Docket. Docket / LR number is
        // captured on the Docket step, NOT on the booking, so the booking
        // form doesn't require or duplicate-check it. If the operator does
        // type an LR here anyway (backward-compat), we still enforce the
        // uniqueness rule below.
        $errs = [];
        $lrNo = trim((string) ($post['lr_no'] ?? ''));
        if ($lrNo !== '') {
            if ($msg = $this->lrDuplicateMessage($lrNo, null)) $errs[] = $msg;
        }
        if ((float) ($post['charge_weight_kg'] ?? 0) <= 0) {
            $errs[] = 'Chargeable Weight (kg) is required.';
        }
        if (!in_array(($post['freight_mode'] ?? ''), ['To Pay','Paid','To Be Billed'], true)) {
            $errs[] = 'Freight Mode is required.';
        }
        if ($errs) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errs));
        }

        // Simplified form: no explicit "client" field — the Consignor IS the
        // billing party unless overridden later on the trip page. Fall back
        // to Consignee if only that was picked.
        if (empty($post['client_id'])) {
            $post['client_id'] = $post['consignor_client_id']
                              ?: ($post['consignee_client_id'] ?? null);
        }

        $buy     = (float) ($post['final_buy_rate']  ?? 0);
        $sell    = (float) ($post['final_sell_rate'] ?? 0);

        // Shipper invoices — clean the array, drop rows with no invoice number,
        // then persist as JSON + also fill legacy invoice_number (comma) +
        // cargo_value_inr (sum) so older reports/PDFs keep working.
        $shipperInvoices = [];
        foreach ((array) ($post['shipper_invoices'] ?? []) as $r) {
            $no  = trim((string) ($r['no']    ?? ''));
            $val = (float) ($r['value'] ?? 0);
            if ($no !== '' || $val > 0) $shipperInvoices[] = ['no' => $no, 'value' => $val];
        }
        $legacyInvoiceNo = implode(', ', array_filter(array_column($shipperInvoices, 'no')));
        $legacyCargoVal  = array_sum(array_column($shipperInvoices, 'value'));

        $data = [
            'booking_no'      => NumberGenerator::booking(),
            'lead_id'         => !empty($post['lead_id'])   ? (int) $post['lead_id']   : null,
            'client_id'       => !empty($post['client_id']) ? (int) $post['client_id'] : null,
            'vendor_id'       => !empty($post['vendor_id']) ? (int) $post['vendor_id'] : null,
            'final_buy_rate'  => $buy,
            'final_sell_rate' => $sell,
            'margin_amount'   => round($sell - $buy, 2),
            'pickup_city'     => trim((string) ($post['pickup_city'] ?? '')) ?: null,
            'drop_city'       => trim((string) ($post['drop_city']   ?? '')) ?: null,
            'route_text'      => trim(trim((string) ($post['pickup_city'] ?? '')) . ' - ' . trim((string) ($post['drop_city'] ?? '')), ' -') ?: null,
            'vehicle_type'    => $post['vehicle_type']   ?? null,
            'load_details'    => $post['load_details']   ?? null,
            'loading_date'      => !empty($post['loading_date']) ? $post['loading_date'] : null,
            'billing_party'     => $post['billing_party']     ?? null,
            'consignee_name'    => $post['consignee_name']    ?? null,
            'consignee_mobile'  => $post['consignee_mobile']  ?? null,
            'consignee_address' => $post['consignee_address'] ?? null,
            'consignee_gstin'   => $post['consignee_gstin']   ?? null,
            'consignor_client_id' => !empty($post['consignor_client_id']) ? (int) $post['consignor_client_id'] : null,
            'consignee_client_id' => !empty($post['consignee_client_id']) ? (int) $post['consignee_client_id'] : null,
            'consignor_name'    => $post['consignor_name']    ?? null,
            'consignor_mobile'  => $post['consignor_mobile']  ?? null,
            'consignor_address' => $post['consignor_address'] ?? null,
            'consignor_gstin'   => $post['consignor_gstin']   ?? null,
            'consignor_state'   => $post['consignor_state']   ?? null,
            'lr_no'             => trim((string) ($post['lr_no'] ?? '')) ?: null,
            'packages_count'     => !empty($post['packages_count'])    ? (int)   $post['packages_count']    : null,
            'packing_method'     => $post['packing_method']            ?: null,
            'actual_weight_kg'   => !empty($post['actual_weight_kg'])  ? (float) $post['actual_weight_kg']  : null,
            'charge_weight_kg'   => !empty($post['charge_weight_kg'])  ? (float) $post['charge_weight_kg']  : null,
            'dim_length_cm'      => !empty($post['dim_length_cm'])     ? (float) $post['dim_length_cm']     : null,
            'dim_width_cm'       => !empty($post['dim_width_cm'])      ? (float) $post['dim_width_cm']      : null,
            'dim_height_cm'      => !empty($post['dim_height_cm'])     ? (float) $post['dim_height_cm']     : null,
            'additional_charges' => !empty($post['additional_charges']) ? (float) $post['additional_charges'] : null,
            'other_charges'      => !empty($post['other_charges'])     ? (float) $post['other_charges']     : null,
            'gst_amount'         => !empty($post['gst_amount'])        ? (float) $post['gst_amount']        : null,
            'service_tax_amount' => !empty($post['service_tax_amount']) ? (float) $post['service_tax_amount'] : null,
            'invoice_number'     => $legacyInvoiceNo ?: null,
            'shipper_invoices_json' => $shipperInvoices ? json_encode($shipperInvoices, JSON_UNESCAPED_UNICODE) : null,
            'bill_of_entry'      => $post['bill_of_entry']             ?: null,
            'bl_number'          => $post['bl_number']                 ?: null,
            'container_number'   => $post['container_number']          ?: null,
            'seal_number'        => $post['seal_number']               ?: null,
            'person_liable_gst'  => in_array(($post['person_liable_gst'] ?? ''), ['Consignor','Consignee','TCE'], true) ? $post['person_liable_gst'] : null,
            'cargo_value_inr'    => $legacyCargoVal > 0 ? $legacyCargoVal : (!empty($post['cargo_value_inr']) ? (float) $post['cargo_value_inr'] : null),
            'freight_mode'      => in_array(($post['freight_mode'] ?? ''), ['To Pay','Paid','To Be Billed'], true) ? $post['freight_mode'] : 'To Be Billed',
            'particulars_text'  => trim((string) ($post['particulars_text'] ?? '')) ?: null,
            'driver_mobile'     => trim((string) ($post['driver_mobile']    ?? '')) ?: null,
            'ewb_no'            => trim((string) ($post['ewb_no']           ?? '')) ?: null,
            'instructions'      => $post['instructions']      ?? null,
            'booking_status'    => 'Pending',
            'created_by'        => $this->auth->id(),
        ];
        (new BookingModel())->insert($data);
        $id = (int) \Config\Database::connect()->insertID();

        // If booking came from an RFQ that's already Awarded, mark RFQ closed
        if (!empty($post['rfq_id'])) {
            (new RfqModel())->update((int) $post['rfq_id'], ['status' => 'Closed']);
        }

        // Notify whoever can approve/confirm bookings that one needs action.
        \App\Libraries\Notify::toPermission('bookings', 'can_approve',
            'New booking to confirm — ' . $data['booking_no'],
            trim(($data['route_text'] ?? '') . ' · ' . ($data['vehicle_type'] ?? ''), ' ·') ?: null,
            site_url('bookings/' . $id),
            ['type' => 'booking_new', 'icon' => 'journal-plus']);

        return redirect()->to(site_url('bookings/' . $id))->with('success', 'Booking ' . $data['booking_no'] . ' created.');
    }

    public function show(int $id)
    {
        $row = (new BookingModel())->withJoins()->where('bookings.id', $id)->first();
        if (!$row) return redirect()->to(site_url('bookings'))->with('error', 'Not found.');

        $trip = (new TripModel())->where('booking_id', $id)->first();

        $db      = \Config\Database::connect();
        $prevRow = $db->table('bookings')->select('id')->where('id <', $id)->where('deleted_at', null)->orderBy('id', 'DESC')->get(1)->getRowArray();
        $nextRow = $db->table('bookings')->select('id')->where('id >', $id)->where('deleted_at', null)->orderBy('id', 'ASC')->get(1)->getRowArray();
        $total   = $db->table('bookings')->where('deleted_at', null)->countAllResults();

        return $this->render('bookings/show', [
            'pageTitle'      => 'GR/LR Booking [Transport]',
            'row'            => $row,
            'trip'           => $trip,
            'prevId'         => $prevRow['id'] ?? null,
            'nextId'         => $nextRow['id'] ?? null,
            'total'          => $total,
            'vendors'        => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'staff'          => (new \App\Models\UserModel())->activeList(),
            'threadType'     => 'booking',
            'threadId'       => $id,
            'threadComments' => (new \App\Models\RecordCommentModel())->thread('booking', $id),
        ], retroFixedShell: true);
    }

    public function edit(int $id)
    {
        $row = (new BookingModel())->find($id);
        if (!$row) return redirect()->to(site_url('bookings'))->with('error', 'Not found.');

        return $this->render('bookings/form', [
            'pageTitle' => 'GR/LR Booking [Edit]',
            'row'       => $row, 'rfq' => null, 'quote' => null, 'lead' => null,
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function update(int $id)
    {
        $post = $this->request->getPost();

        // Docket / LR is captured on the Docket step (workflow: Booking →
        // Trip → Create Docket), so it isn't required on booking edits either.
        // We still dup-check if an LR was typed here for backward compat.
        $errs = [];
        $lrNo = trim((string) ($post['lr_no'] ?? ''));
        if ($lrNo !== '') {
            if ($msg = $this->lrDuplicateMessage($lrNo, $id)) $errs[] = $msg;
        }
        if ((float) ($post['charge_weight_kg'] ?? 0) <= 0) {
            $errs[] = 'Chargeable Weight (kg) is required.';
        }
        if (!in_array(($post['freight_mode'] ?? ''), ['To Pay','Paid','To Be Billed'], true)) {
            $errs[] = 'Freight Mode is required.';
        }
        if ($errs) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errs));
        }

        if (empty($post['client_id'])) {
            $post['client_id'] = $post['consignor_client_id']
                              ?: ($post['consignee_client_id'] ?? null);
        }

        $buy  = (float) ($post['final_buy_rate']  ?? 0);
        $sell = (float) ($post['final_sell_rate'] ?? 0);

        $shipperInvoices = [];
        foreach ((array) ($post['shipper_invoices'] ?? []) as $r) {
            $no  = trim((string) ($r['no']    ?? ''));
            $val = (float) ($r['value'] ?? 0);
            if ($no !== '' || $val > 0) $shipperInvoices[] = ['no' => $no, 'value' => $val];
        }
        $legacyInvoiceNo = implode(', ', array_filter(array_column($shipperInvoices, 'no')));
        $legacyCargoVal  = array_sum(array_column($shipperInvoices, 'value'));

        (new BookingModel())->update($id, [
            'client_id'       => !empty($post['client_id']) ? (int) $post['client_id'] : null,
            'vendor_id'       => !empty($post['vendor_id']) ? (int) $post['vendor_id'] : null,
            'final_buy_rate'  => $buy,
            'final_sell_rate' => $sell,
            'margin_amount'   => round($sell - $buy, 2),
            'pickup_city'     => trim((string) ($post['pickup_city'] ?? '')) ?: null,
            'drop_city'       => trim((string) ($post['drop_city']   ?? '')) ?: null,
            'route_text'      => trim(trim((string) ($post['pickup_city'] ?? '')) . ' - ' . trim((string) ($post['drop_city'] ?? '')), ' -') ?: null,
            'vehicle_type'    => $post['vehicle_type'] ?? null,
            'load_details'    => $post['load_details'] ?? null,
            'loading_date'      => !empty($post['loading_date']) ? $post['loading_date'] : null,
            'billing_party'     => $post['billing_party']     ?? null,
            'consignee_name'    => $post['consignee_name']    ?? null,
            'consignee_mobile'  => $post['consignee_mobile']  ?? null,
            'consignee_address' => $post['consignee_address'] ?? null,
            'consignee_gstin'   => $post['consignee_gstin']   ?? null,
            'consignor_client_id' => !empty($post['consignor_client_id']) ? (int) $post['consignor_client_id'] : null,
            'consignee_client_id' => !empty($post['consignee_client_id']) ? (int) $post['consignee_client_id'] : null,
            'consignor_name'    => $post['consignor_name']    ?? null,
            'consignor_mobile'  => $post['consignor_mobile']  ?? null,
            'consignor_address' => $post['consignor_address'] ?? null,
            'consignor_gstin'   => $post['consignor_gstin']   ?? null,
            'consignor_state'   => $post['consignor_state']   ?? null,
            'lr_no'             => trim((string) ($post['lr_no'] ?? '')) ?: null,
            'packages_count'     => !empty($post['packages_count'])    ? (int)   $post['packages_count']    : null,
            'packing_method'     => $post['packing_method']            ?: null,
            'actual_weight_kg'   => !empty($post['actual_weight_kg'])  ? (float) $post['actual_weight_kg']  : null,
            'charge_weight_kg'   => !empty($post['charge_weight_kg'])  ? (float) $post['charge_weight_kg']  : null,
            'dim_length_cm'      => !empty($post['dim_length_cm'])     ? (float) $post['dim_length_cm']     : null,
            'dim_width_cm'       => !empty($post['dim_width_cm'])      ? (float) $post['dim_width_cm']      : null,
            'dim_height_cm'      => !empty($post['dim_height_cm'])     ? (float) $post['dim_height_cm']     : null,
            'additional_charges' => !empty($post['additional_charges']) ? (float) $post['additional_charges'] : null,
            'other_charges'      => !empty($post['other_charges'])     ? (float) $post['other_charges']     : null,
            'gst_amount'         => !empty($post['gst_amount'])        ? (float) $post['gst_amount']        : null,
            'service_tax_amount' => !empty($post['service_tax_amount']) ? (float) $post['service_tax_amount'] : null,
            'invoice_number'     => $legacyInvoiceNo ?: null,
            'shipper_invoices_json' => $shipperInvoices ? json_encode($shipperInvoices, JSON_UNESCAPED_UNICODE) : null,
            'bill_of_entry'      => $post['bill_of_entry']             ?: null,
            'bl_number'          => $post['bl_number']                 ?: null,
            'container_number'   => $post['container_number']          ?: null,
            'seal_number'        => $post['seal_number']               ?: null,
            'person_liable_gst'  => in_array(($post['person_liable_gst'] ?? ''), ['Consignor','Consignee','TCE'], true) ? $post['person_liable_gst'] : null,
            'cargo_value_inr'    => $legacyCargoVal > 0 ? $legacyCargoVal : (!empty($post['cargo_value_inr']) ? (float) $post['cargo_value_inr'] : null),
            'freight_mode'      => in_array(($post['freight_mode'] ?? ''), ['To Pay','Paid','To Be Billed'], true) ? $post['freight_mode'] : 'To Be Billed',
            'particulars_text'  => trim((string) ($post['particulars_text'] ?? '')) ?: null,
            'driver_mobile'     => trim((string) ($post['driver_mobile']    ?? '')) ?: null,
            'ewb_no'            => trim((string) ($post['ewb_no']           ?? '')) ?: null,
            'instructions'      => $post['instructions']      ?? null,
            'updated_by'        => $this->auth->id(),
        ]);
        return redirect()->to(site_url('bookings/' . $id))->with('success', 'Booking updated.');
    }

    public function approve(int $id)
    {
        $row = (new BookingModel())->find($id);
        if (!$row) return redirect()->to(site_url('bookings'))->with('error', 'Not found.');

        // Confirm Booking step: capture vendor + rate + vehicle (type & number)
        // that may have been left blank at create time. Each field falls back to
        // the booking's existing value so a plain confirm (no form fields) still
        // works. Per business rule, confirm is allowed even if these are blank.
        $post = $this->request->getPost();
        $buy   = isset($post['final_buy_rate'])  && $post['final_buy_rate']  !== '' ? (float) $post['final_buy_rate']  : (float) $row['final_buy_rate'];
        $sell  = isset($post['final_sell_rate']) && $post['final_sell_rate'] !== '' ? (float) $post['final_sell_rate'] : (float) $row['final_sell_rate'];

        $update = [
            'booking_status' => 'Approved',
            'approved_by'    => $this->auth->id(),
            'approved_at'    => date('Y-m-d H:i:s'),
            'updated_by'     => $this->auth->id(),
            'final_buy_rate'  => $buy,
            'final_sell_rate' => $sell,
            'margin_amount'   => round($sell - $buy, 2),
        ];
        if (!empty($post['vendor_id']))      $update['vendor_id']      = (int) $post['vendor_id'];
        if (isset($post['vehicle_type']))    $update['vehicle_type']   = $post['vehicle_type'] ?: $row['vehicle_type'];
        if (isset($post['vehicle_number'])) {
            $vno = strtoupper(trim((string) $post['vehicle_number']));
            $update['vehicle_number'] = $vno !== '' ? $vno : ($row['vehicle_number'] ?? null);
        }

        (new BookingModel())->update($id, $update);
        // Refresh the local copy so downstream logic (GPS, email) sees new values.
        $row = (new BookingModel())->find($id);

        // Auto-move originating lead to Won — but preserve terminal states (Lost/Closed/Won).
        // Otherwise an unrelated booking could resurrect a closed lead.
        if (!empty($row['lead_id'])) {
            $lead = (new LeadModel())->find((int) $row['lead_id']);
            $promotable = ['New','Under Review','Sent to Purchase','Quote Received','Sent to Client','Negotiation'];
            if ($lead && in_array($lead['current_status'], $promotable, true)) {
                (new LeadModel())->update((int) $row['lead_id'], ['current_status' => 'Won']);
                (new LeadStatusHistoryModel())->log(
                    (int) $row['lead_id'],
                    $lead['current_status'],
                    'Won',
                    'Booking ' . $row['booking_no'] . ' approved',
                    $this->auth->id()
                );
            }
        }

        // Kick off GPS tracking immediately for any trip already linked to this booking that has a vehicle.
        // SOP: tracking starts at the loading point the moment a booking is confirmed.
        $msg = 'Booking approved.';
        try {
            $db = \Config\Database::connect();
            $trips = $db->table('trips')
                ->select('id, vehicle_number')
                ->where('booking_id', $id)
                ->where('deleted_at', null)
                ->where('vehicle_number IS NOT NULL')
                ->where("vehicle_number != ''")
                ->whereNotIn('current_status', ['Closed','Cancelled'])
                ->get()->getResultArray();
            $kicked = 0;
            if (!empty($trips)) {
                $svc = new \App\Libraries\LocoNavService();
                foreach ($trips as $t) {
                    $r = $svc->refreshVehicle((string) $t['vehicle_number'], (int) $t['id']);
                    if (!empty($r['ok'])) $kicked++;
                }
                if ($kicked > 0) {
                    $msg .= " GPS started for {$kicked} trip(s).";
                }
            }
        } catch (\Throwable $e) {
            // Don't fail the approval if LocoNav is misconfigured — the cron will pick it up shortly.
            log_message('warning', 'GPS refresh on approve failed: ' . $e->getMessage());
        }

        // Send a confirmation email to the client (if email present and template active)
        try {
            $client = !empty($row['client_id']) ? (new \App\Models\ClientModel())->find((int) $row['client_id']) : null;
            $to = (string) ($client['email'] ?? '');
            if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $vars = [
                    'booking_no' => $row['booking_no'],
                    'route'      => $row['route_text'] ?? '',
                    'company'    => $client['company_name'] ?? '',
                    'view_url'   => site_url('portal/bookings/' . $id),
                    0 => $row['booking_no'],
                    1 => $row['route_text'] ?? '',
                    2 => $client['company_name'] ?? '',
                    3 => site_url('portal/bookings/' . $id),
                ];
                (new \App\Libraries\EmailService())->sendTemplate('booking_confirmed', $to, $vars, [
                    'related_module'    => 'booking',
                    'related_id'        => $id,
                    'related_client_id' => $client['id'] ?? null,
                    'sent_by_user_id'   => $this->auth->id(),
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Booking confirmation email failed: ' . $e->getMessage());
        }

        // In-app: alert Operations (anyone who can see trips) that a confirmed
        // booking is ready for handover — role-name agnostic, view-inclusive.
        \App\Libraries\Notify::toPermission('trips', 'can_view',
            'Booking confirmed — ' . ($row['booking_no'] ?? ('#' . $id)),
            trim(($row['route_text'] ?? '') . ' · ' . ($row['vehicle_type'] ?? ''), ' ·') . ' — ready for handover',
            site_url('bookings/' . $id),
            ['type' => 'booking_confirmed', 'icon' => 'truck']);

        // Assignment loop: notify the booking's assignee + whoever assigned it.
        \App\Libraries\Notify::toUsers(
            [(int) ($row['assigned_to'] ?? 0), (int) ($row['assigned_by'] ?? 0)],
            'Booking confirmed — ' . ($row['booking_no'] ?? ('#' . $id)),
            trim(($row['route_text'] ?? '') . ' · ' . ($row['vehicle_type'] ?? ''), ' ·') ?: null,
            site_url('bookings/' . $id),
            ['type' => 'booking_progress', 'icon' => 'arrow-repeat']);

        return redirect()->to(site_url('bookings/' . $id))->with('success', $msg);
    }

    /** POST /bookings/:id/assign — give this booking to a team member and notify. */
    public function assign(int $id)
    {
        $row = (new BookingModel())->find($id);
        if (!$row) return redirect()->to(site_url('bookings'))->with('error', 'Not found.');

        $userId = (int) $this->request->getPost('assigned_to');
        (new BookingModel())->update($id, [
            'assigned_to' => $userId > 0 ? $userId : null,
            'assigned_by' => $userId > 0 ? $this->auth->id() : null,
            'updated_by'  => $this->auth->id(),
        ]);

        if ($userId > 0) {
            \App\Libraries\Notify::toUser($userId,
                'Booking assigned to you — ' . ($row['booking_no'] ?? ('#' . $id)),
                trim(($row['route_text'] ?? '') . ' · ' . ($row['vehicle_type'] ?? ''), ' ·') ?: null,
                site_url('bookings/' . $id),
                ['type' => 'booking_assigned', 'icon' => 'person-check']);
        }
        return redirect()->to(site_url('bookings/' . $id))->with('success', 'Booking assignment updated.');
    }

    public function cancel(int $id)
    {
        (new BookingModel())->update($id, [
            'booking_status' => 'Cancelled',
            'updated_by'     => $this->auth->id(),
        ]);
        return redirect()->to(site_url('bookings/' . $id))->with('success', 'Booking cancelled.');
    }

    public function delete(int $id)
    {
        (new BookingModel())->delete($id);
        return redirect()->to(site_url('bookings'))->with('success', 'Booking removed.');
    }

    /** POST /bookings/bulk — cancel or delete a batch of booking IDs */
    public function bulk()
    {
        $ids    = array_map('intval', (array) $this->request->getPost('ids'));
        $action = (string) $this->request->getPost('action');
        if (empty($ids) || $action === '') return redirect()->back()->with('error', 'Select rows and an action.');

        $model = new BookingModel();
        if ($action === 'cancel') {
            $model->whereIn('id', $ids)->set([
                'booking_status' => 'Cancelled',
                'updated_by'     => $this->auth->id(),
                'updated_at'     => date('Y-m-d H:i:s'),
            ])->update();
            return redirect()->back()->with('success', count($ids) . ' booking(s) cancelled.');
        }
        if ($action === 'delete') {
            foreach ($ids as $id) $model->delete($id);
            return redirect()->back()->with('success', count($ids) . ' booking(s) removed.');
        }
        return redirect()->back()->with('error', 'Unknown action.');
    }

    /** Hand over to Operations — creates a Trip for this Booking. */
    public function handover(int $id)
    {
        $row = (new BookingModel())->find($id);
        if (!$row) return redirect()->to(site_url('bookings'))->with('error', 'Not found.');
        if ($row['booking_status'] !== 'Approved') {
            return redirect()->back()->with('error', 'Approve the booking before handover.');
        }

        $tripModel = new TripModel();
        $existing  = $tripModel->where('booking_id', $id)->first();
        if ($existing) {
            return redirect()->to(site_url('trips/' . $existing['id']))->with('success', 'Trip already exists.');
        }

        // Use structured pickup/drop from booking; fall back to parsing the
        // legacy route_text for very old rows that pre-date the split.
        $pickup = trim((string) ($row['pickup_city'] ?? ''));
        $drop   = trim((string) ($row['drop_city']   ?? ''));
        if ($pickup === '' && $drop === '' && !empty($row['route_text'])) {
            $split = preg_split('/\s*(?:→|->|—|–|-+>|\|)\s*/u', (string) $row['route_text'], 2);
            $pickup = trim((string) ($split[0] ?? ''));
            $drop   = trim((string) ($split[1] ?? ''));
        }
        // Keep old $parts variable for anything downstream that still reads it.
        $parts  = [$pickup, $drop];
        $tripNo = NumberGenerator::trip();
        $tripData = [
            'trip_no'          => $tripNo,
            'booking_id'       => (int) $id,
            'vendor_id'        => $row['vendor_id'],
            'loading_point'    => $pickup ?: null,
            'unloading_point'  => $drop   ?: null,
            'current_status'   => 'Booking Created',
            'pod_status'       => 'Pending',
            'created_by'       => $this->auth->id(),
        ];
        // Carry the vehicle registration captured at Confirm over to the trip.
        if (!empty($row['vehicle_number'])) {
            $tripData['vehicle_number'] = $row['vehicle_number'];
        }
        // Driver mobile captured on the docket form flows straight to the trip.
        if (!empty($row['driver_mobile'])) {
            $tripData['driver_mobile'] = $row['driver_mobile'];
        }
        // E-Way Bill entered on the booking / docket flows to the trip too.
        if (!empty($row['ewb_no'])) {
            $tripData['ewb_no'] = $row['ewb_no'];
        }
        // Carry the LR number entered on the booking form. Skip if another trip
        // already claimed the same LR — trip.lr_no has a unique index that
        // would otherwise blow up the handover with a DB error.
        if (!empty($row['lr_no'])) {
            $clash = $tripModel->where('lr_no', $row['lr_no'])->first();
            if (!$clash) {
                $tripData['lr_no']           = $row['lr_no'];
                $tripData['lr_generated_at'] = date('Y-m-d H:i:s');
            }
        }
        $tripId = (int) $tripModel->insert($tripData);

        (new TripStatusHistoryModel())->log($tripId, null, 'Booking Created', 'Handed over from booking ' . $row['booking_no'], $this->auth->id());
        (new BookingModel())->update($id, ['booking_status' => 'Handed Over', 'updated_by' => $this->auth->id()]);

        // Notify Operations a new trip is ready to run.
        \App\Libraries\Notify::toPermission('trips', 'can_view',
            'New trip created — ' . $tripNo,
            trim(($parts[0] ?? '') . ' - ' . ($parts[1] ?? ''), ' -') ?: null,
            site_url('trips/' . $tripId),
            ['type' => 'trip_new', 'icon' => 'truck']);

        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Trip ' . $tripNo . ' created.');
    }

    /**
     * Returns a human-readable error message if the given LR number is already
     * used on another booking or trip. Null if the number is free.
     * @param  string   $lrNo             The LR number the operator typed
     * @param  int|null $excludeBookingId Booking id to ignore (self on edit)
     */
    private function lrDuplicateMessage(string $lrNo, ?int $excludeBookingId): ?string
    {
        // Check other bookings
        $bookings = new BookingModel();
        $q = $bookings->where('lr_no', $lrNo);
        if ($excludeBookingId !== null) $q->where('id !=', $excludeBookingId);
        $dupBk = $q->first();
        if ($dupBk) {
            return 'LR ' . $lrNo . ' is already used on Booking ' . ($dupBk['booking_no'] ?? ('#' . $dupBk['id'])) . '. Please enter a different LR number.';
        }

        // Check trips (the true unique index) — a trip could have the LR even
        // if no booking has been saved with it (legacy manual entry).
        $dupTrip = (new TripModel())->where('lr_no', $lrNo)->first();
        if ($dupTrip) {
            return 'LR ' . $lrNo . ' is already used on Trip ' . ($dupTrip['trip_no'] ?? ('#' . $dupTrip['id'])) . '. Please enter a different LR number.';
        }

        return null;
    }
}

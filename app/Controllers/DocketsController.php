<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\TripModel;
use App\Models\TripStatusHistoryModel;
use App\Models\ClientModel;
use App\Libraries\NumberGenerator;

/**
 * Fast Docket / LR creation flow.
 *
 * The form at /dockets/create captures ONLY the 18 fields Ops needs to
 * print an LR immediately. The controller writes:
 *   1. a booking (client = consignor by default) with LR pre-set + approved
 *   2. a trip (via the same handover path bookings use) carrying LR + driver mobile
 * and then lands the operator on the trip's Dispatch Pack so the docket
 * PDF can be printed in one click.
 *
 * The full accounting Booking form at /bookings/create remains for cases
 * where rates / vendor / expenses need to be captured upfront.
 */
class DocketsController extends BaseController
{
    public function create()
    {
        return $this->render('dockets/create', [
            'pageTitle' => 'Create Docket',
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'trip'      => null,
            'booking'   => null,
            // Show the top-N trips that still need a docket right on the create form so
            // ops can jump straight into filling in the LR for an existing trip instead
            // of re-keying party/route data from scratch.
            'pending'   => $this->pendingDocketQuery()->limit(20)->get()->getResultArray(),
        ]);
    }

    /**
     * Full-page table of every active trip that still needs its docket / LR number
     * filled in. Click a row → jumps to createFromTrip() with the form prefilled.
     */
    public function pendingDockets()
    {
        return $this->render('dockets/pending', [
            'pageTitle' => 'Trips awaiting docket',
            'rows'      => $this->pendingDocketQuery()->get()->getResultArray(),
        ]);
    }

    /**
     * Trips that are still "waiting for their LR" — the ones ops needs to pick from
     * on the Create Docket page. Excludes anything terminal (Cancelled / Closed) so
     * old junk trips don't pollute the list.
     *
     * Respects the current viewer's per-client visibility (scopedClientIds) so
     * a salesperson only sees their own clients' pending trips.
     */
    private function pendingDocketQuery()
    {
        $db = \Config\Database::connect();

        // Discover which bookings columns actually exist — different deploys
        // have added them at different times. Missing ones just render as blank.
        $bkCols   = $db->getFieldNames('bookings');
        $has      = static fn (string $c) => in_array($c, $bkCols, true);
        $bkPick   = static fn (string $c, string $alias = null) => $has($c)
            ? 'b.' . $c . ($alias ? ' AS ' . $alias : '')
            : 'NULL AS ' . ($alias ?: $c);

        $selectParts = [
            't.id, t.trip_no, t.lr_no, t.current_status, t.loading_point, t.unloading_point,
             t.vehicle_number, t.driver_mobile, t.created_at',
            'b.id AS booking_id',
            $bkPick('booking_no'),
            $bkPick('loading_date'),
            $bkPick('charge_weight_kg'),
            $bkPick('freight_mode'),
            $bkPick('route_text'),
            'c.company_name AS client_company',
        ];

        $q = $db->table('trips t')
            ->select(implode(', ', $selectParts), false)
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->join('clients  c', 'c.id = b.client_id', 'left')
            // "LR blank" = either NULL or an all-whitespace string
            ->groupStart()
                ->where('t.lr_no IS NULL', null, false)
                ->orWhere("(t.lr_no = '' OR TRIM(t.lr_no) = '')", null, false)
            ->groupEnd()
            ->whereNotIn('t.current_status', ['Cancelled', 'Closed'])
            // Pass raw so CI doesn't try to backtick-escape COALESCE(...)
            ->orderBy('COALESCE(b.loading_date, t.created_at) DESC', '', false);

        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null && $has('client_id')) {
            $uid = (int) $this->auth->id();
            $q->groupStart()
                ->whereIn('b.client_id', $ownedIds ?: [0])
                ->orWhere('t.assigned_to', $uid)
                ->orWhere('b.assigned_to', $uid)
              ->groupEnd();
        }
        return $q;
    }

    /**
     * "Create Docket" launched from the Trip page — pre-fills the form with
     * everything already known on that trip + its parent booking. On save,
     * the store() method detects the hidden trip_id and updates the existing
     * records instead of creating a duplicate booking/trip.
     */
    public function createFromTrip(int $tripId)
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $booking = !empty($trip['booking_id'])
            ? (new BookingModel())->find((int) $trip['booking_id'])
            : null;

        return $this->render('dockets/create', [
            'pageTitle' => 'Docket for ' . $trip['trip_no'],
            'clients'   => (new ClientModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'trip'      => $trip,
            'booking'   => $booking,
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();

        // If a trip_id came from the "Create Docket from Trip" launcher, we
        // update the existing booking + trip instead of creating a new pair.
        $existingTripId = (int) ($post['trip_id'] ?? 0);

        // Guard the operational essentials — same rules as the full booking form.
        $errs = [];
        $lrNo = trim((string) ($post['lr_no'] ?? ''));
        if ($lrNo === '') {
            $errs[] = 'Docket / LR Number is required.';
        } elseif ($msg = $this->lrDuplicateMessage($lrNo, $existingTripId)) {
            $errs[] = $msg;
        }
        if (empty($post['consignor_client_id'])) $errs[] = 'Consignor is required.';
        if (empty($post['consignee_client_id'])) $errs[] = 'Consignee is required.';
        if ((float) ($post['charge_weight_kg'] ?? 0) <= 0) $errs[] = 'Chargeable Weight (kg) is required.';
        if (!in_array(($post['freight_mode'] ?? ''), ['To Pay','Paid','To Be Billed'], true)) {
            $errs[] = 'Remarks / Payment Method is required.';
        }
        if ($errs) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errs));
        }

        // Shipper invoices — same JSON + legacy-column shape used by /bookings.
        $shipperInvoices = [];
        foreach ((array) ($post['shipper_invoices'] ?? []) as $r) {
            $no  = trim((string) ($r['no']    ?? ''));
            $val = (float) ($r['value'] ?? 0);
            if ($no !== '' || $val > 0) $shipperInvoices[] = ['no' => $no, 'value' => $val];
        }
        $legacyInvoiceNo = implode(', ', array_filter(array_column($shipperInvoices, 'no')));
        $legacyCargoVal  = array_sum(array_column($shipperInvoices, 'value'));

        // Client on the booking = Consignor by default (they're the billing party
        // for most freight jobs). Can be overridden later on the Trip's parties card.
        $consignorId = (int) $post['consignor_client_id'];
        $consigneeId = (int) $post['consignee_client_id'];
        $pickup      = trim((string) ($post['pickup_city'] ?? ''));
        $drop        = trim((string) ($post['drop_city']   ?? ''));

        // Shared field map — writable to both new booking and existing booking updates.
        $bookingFields = [
            'client_id'       => $consignorId,
            'pickup_city'     => $pickup ?: null,
            'drop_city'       => $drop   ?: null,
            'route_text'      => trim($pickup . ' - ' . $drop, ' -') ?: null,
            'vehicle_type'    => $post['vehicle_type']   ?: null,
            'vehicle_number'  => strtoupper(trim((string) ($post['vehicle_number'] ?? ''))) ?: null,
            'loading_date'    => !empty($post['loading_date']) ? $post['loading_date'] : null,
            'consignor_client_id' => $consignorId,
            'consignee_client_id' => $consigneeId,
            'consignor_name'    => $post['consignor_name']    ?? null,
            'consignor_mobile'  => $post['consignor_mobile']  ?? null,
            'consignor_address' => $post['consignor_address'] ?? null,
            'consignor_gstin'   => $post['consignor_gstin']   ?? null,
            'consignor_state'   => $post['consignor_state']   ?? null,
            'consignee_name'    => $post['consignee_name']    ?? null,
            'consignee_mobile'  => $post['consignee_mobile']  ?? null,
            'consignee_address' => $post['consignee_address'] ?? null,
            'consignee_gstin'   => $post['consignee_gstin']   ?? null,
            'lr_no'             => $lrNo,
            'packages_count'    => !empty($post['packages_count'])   ? (int) $post['packages_count']   : null,
            'packing_method'    => $post['packing_method']           ?: null,
            'particulars_text'  => trim((string) ($post['particulars_text'] ?? '')) ?: null,
            'actual_weight_kg'  => !empty($post['actual_weight_kg']) ? (float) $post['actual_weight_kg'] : null,
            'charge_weight_kg'  => (float) $post['charge_weight_kg'],
            'driver_mobile'     => trim((string) ($post['driver_mobile'] ?? '')) ?: null,
            'ewb_no'            => trim((string) ($post['ewb_no']        ?? '')) ?: null,
            'bill_of_entry'     => $post['bill_of_entry']    ?: null,
            'container_number'  => $post['container_number'] ?: null,
            'invoice_number'    => $legacyInvoiceNo ?: null,
            'shipper_invoices_json' => $shipperInvoices ? json_encode($shipperInvoices, JSON_UNESCAPED_UNICODE) : null,
            'cargo_value_inr'   => $legacyCargoVal > 0 ? $legacyCargoVal : null,
            'freight_mode'      => $post['freight_mode'],
            'booking_status'    => 'Approved',                     // straight to approved so handover works
            'approved_by'       => $this->auth->id(),
            'approved_at'       => date('Y-m-d H:i:s'),
            'created_by'        => $this->auth->id(),
        ];

        $bookingModel = new BookingModel();
        $tripModel    = new TripModel();

        if ($existingTripId > 0) {
            // ── UPDATE path: called from Trip page → update the existing pair,
            // no new records, LR just gets filled in / edited.
            $trip = $tripModel->find($existingTripId);
            if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip vanished mid-save.');

            if (!empty($trip['booking_id'])) {
                $bookingUpdate = $bookingFields;
                $bookingUpdate['updated_by'] = $this->auth->id();
                unset($bookingUpdate['booking_status'], $bookingUpdate['approved_by'],
                      $bookingUpdate['approved_at'], $bookingUpdate['created_by']);
                $bookingModel->update((int) $trip['booking_id'], $bookingUpdate);
            }
            $tripModel->update($existingTripId, [
                'lr_no'           => $lrNo,
                'lr_generated_at' => $trip['lr_generated_at'] ?: date('Y-m-d H:i:s'),
                'loading_point'   => $pickup ?: $trip['loading_point'],
                'unloading_point' => $drop   ?: $trip['unloading_point'],
                'driver_mobile'   => $bookingFields['driver_mobile']  ?: $trip['driver_mobile'],
                'vehicle_number'  => $bookingFields['vehicle_number'] ?: $trip['vehicle_number'],
                'ewb_no'          => $bookingFields['ewb_no']         ?: ($trip['ewb_no'] ?? null),
                'updated_by'      => $this->auth->id(),
            ]);
            return redirect()->to(site_url('trips/' . $existingTripId . '/dispatch'))
                ->with('success', 'Docket ' . $lrNo . ' saved. Print below.');
        }

        // ── CREATE path: standalone quick-Docket form → new booking + trip.
        $bookingData = ['booking_no' => NumberGenerator::booking()] + $bookingFields;
        $bookingModel->insert($bookingData);
        $bookingId = (int) \Config\Database::connect()->insertID();

        $tripNo    = NumberGenerator::trip();
        $tripId    = (int) $tripModel->insert([
            'trip_no'          => $tripNo,
            'booking_id'       => $bookingId,
            'loading_point'    => $pickup ?: null,
            'unloading_point'  => $drop   ?: null,
            'current_status'   => 'Booking Created',
            'pod_status'       => 'Pending',
            'lr_no'            => $lrNo,
            'lr_generated_at'  => date('Y-m-d H:i:s'),
            'driver_mobile'    => $bookingFields['driver_mobile'],
            'vehicle_number'   => $bookingFields['vehicle_number'],
            'ewb_no'           => $bookingFields['ewb_no'],
            'created_by'       => $this->auth->id(),
        ]);
        (new TripStatusHistoryModel())->log($tripId, null, 'Booking Created', 'Docket ' . $lrNo . ' created via quick Docket form', $this->auth->id());
        $bookingModel->update($bookingId, ['booking_status' => 'Handed Over']);

        return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))
            ->with('success', 'Docket ' . $lrNo . ' created. Print it below.');
    }

    /**
     * Duplicate check against bookings + trips. When editing an existing trip,
     * $excludeTripId is that trip's id (and its parent booking is also skipped)
     * so we don't false-flag the trip's own LR back to itself.
     */
    private function lrDuplicateMessage(string $lrNo, int $excludeTripId = 0): ?string
    {
        $excludeBookingId = 0;
        if ($excludeTripId > 0) {
            $t = (new TripModel())->find($excludeTripId);
            if ($t) $excludeBookingId = (int) ($t['booking_id'] ?? 0);
        }

        $bkQ = (new BookingModel())->where('lr_no', $lrNo);
        if ($excludeBookingId > 0) $bkQ->where('id !=', $excludeBookingId);
        $dupBk = $bkQ->first();
        if ($dupBk) {
            return 'LR ' . $lrNo . ' is already used on Booking ' . ($dupBk['booking_no'] ?? ('#' . $dupBk['id'])) . '. Pick a different number.';
        }

        $trQ = (new TripModel())->where('lr_no', $lrNo);
        if ($excludeTripId > 0) $trQ->where('id !=', $excludeTripId);
        $dupTrip = $trQ->first();
        if ($dupTrip) {
            return 'LR ' . $lrNo . ' is already used on Trip ' . ($dupTrip['trip_no'] ?? ('#' . $dupTrip['id'])) . '. Pick a different number.';
        }
        return null;
    }
}

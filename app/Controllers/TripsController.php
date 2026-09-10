<?php

namespace App\Controllers;

use App\Models\TripModel;
use App\Models\TripStatusHistoryModel;
use App\Models\BookingModel;
use App\Models\DriverModel;
use App\Models\VehicleModel;
use App\Models\DocumentModel;
use App\Models\WhatsappTemplateModel;
use App\Models\ClientModel;
use App\Models\TripExpenseModel;
use App\Models\TripExpenseCategoryModel;
use App\Libraries\WhatsAppService;

class TripsController extends BaseController
{
    use \App\Traits\ExportsCsv;

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $pod    = (string) $this->request->getGet('pod');
        $docket = (string) $this->request->getGet('docket');

        $model = $this->filteredQuery($search, $status, $pod, $docket);

        return $this->render('trips/index', [
            'pageTitle' => $docket === 'pending' ? 'Create Docket — Trips without LR' : 'Trips',
            'rows'      => $model->paginate($this->perPage()),
            'pager'     => (new TripModel())->pager,
            'search'    => $search,
            'status'    => $status,
            'pod'       => $pod,
            'docket'    => $docket,
            'statuses'  => TripModel::STATUSES,
        ]);
    }

    /** GET /trips/export — honors q/status/pod filters from the list page */
    public function export()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $pod    = (string) $this->request->getGet('pod');
        $rows   = $this->filteredQuery($search, $status, $pod)->findAll();
        return $this->streamCsv('trips-' . date('Y-m-d') . '.csv', [
            'trip_no'         => 'Trip No',
            'created_at'      => 'Created',
            'client_company'  => 'Client',
            'pickup_city'     => 'Pickup',
            'drop_city'       => 'Drop',
            'vehicle_number'  => 'Vehicle',
            'driver_name'     => 'Driver',
            'current_status'  => 'Status',
            'pod_status'      => 'POD',
            'sell_amount'     => 'Sell',
            'buy_amount'      => 'Buy',
        ], $rows);
    }

    private function filteredQuery(string $search, string $status, string $pod, string $docket = '')
    {
        $model = new TripModel();
        $q     = $model->withJoins()->orderBy('trips.id', 'DESC');
        if ($search !== '') {
            $q->groupStart()
                ->like('trips.trip_no', $search)
                ->orLike('trips.lr_no', $search)
                ->orLike('trips.ewb_no', $search)
                ->orLike('bookings.booking_no', $search)
                ->orLike('trips.vehicle_number', $search)
                ->orLike('trips.driver_name', $search)
                ->orLike('trips.driver_mobile', $search)
                ->orLike('clients.company_name', $search)
                ->groupEnd();
        }
        if ($status !== '') $q->where('trips.current_status', $status);
        if ($pod === 'pending') $q->where('trips.pod_status', 'Pending');
        // "Create Docket" quick-add: show trips that still need an LR number.
        if ($docket === 'pending') {
            $q->groupStart()->where('trips.lr_no', null)->orWhere('trips.lr_no', '')->groupEnd();
        }

        // Ownership scope — non-admins see trips whose parent booking's
        // CLIENT belongs to them, OR whose per-trip assigned_to = them, OR
        // whose parent booking is assigned to them. Handles the "Sharda's
        // client but Rahul is handling this specific booking/trip" case.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            $uid = (int) $this->auth->id();
            $q->groupStart()
                ->whereIn('bookings.client_id', $ownedIds ?: [0])
                ->orWhere('trips.assigned_to',    $uid)
                ->orWhere('bookings.assigned_to', $uid)
              ->groupEnd();
        }

        return $q;
    }

    public function show(int $id)
    {
        $row = (new TripModel())->withJoins()->where('trips.id', $id)->first();
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $db = \Config\Database::connect();
        $driverMessages = $db->table('driver_messages')
            ->where('trip_id', $id)
            ->orderBy('id', 'ASC')
            ->limit(500)
            ->get()->getResultArray();
        // Mark driver messages as read by staff (now that we're viewing them)
        $db->table('driver_messages')
            ->where('trip_id', $id)
            ->where('direction', 'driver')
            ->where('is_read_by_staff', 0)
            ->update(['is_read_by_staff' => 1]);

        $expModel = new TripExpenseModel();

        // Parent booking + client list — so the operator can add/edit
        // Consignor & Consignee here on the trip page (both are optional at booking time).
        $bookingRow = !empty($row['booking_id'])
            ? (new \App\Models\BookingModel())->find((int) $row['booking_id'])
            : null;
        $clientsForParty = (new \App\Models\ClientModel())
            ->where('status', 1)->orderBy('company_name')->findAll();

        return $this->render('trips/show', [
            'pageTitle'   => 'Trip ' . $row['trip_no'],
            'row'         => $row,
            'booking'     => $bookingRow,
            'clientsForParty' => $clientsForParty,
            'history'     => (new TripStatusHistoryModel())->forTrip($id),
            'documents'   => (new DocumentModel())->forModule('trip', $id),
            'drivers'     => (new DriverModel())->where('status', 1)->orderBy('driver_name')->findAll(),
            'vehicles'    => (new VehicleModel())->where('status', 1)->orderBy('vehicle_number')->findAll(),
            'expenses'    => $expModel->forTrip($id),
            'expTotals'   => $expModel->totalsForTrip($id),
            'expCategories' => (new TripExpenseCategoryModel())->active(),
            'staff'     => (new \App\Models\UserModel())->activeList(),
            'statuses'  => TripModel::STATUSES,
            'service'   => new WhatsAppService(),
            'threadType'     => 'trip',
            'threadId'       => $id,
            'threadComments' => (new \App\Models\RecordCommentModel())->thread('trip', $id),
            'stops'          => (new \App\Models\TripStopModel())->forTrip($id),
            'advances'       => (new \App\Models\TripAdvanceModel())->forTrip($id),
            'advanceTotals'  => (new \App\Models\TripAdvanceModel())->totalsForTrip($id),
            'driverMessages' => $driverMessages,
            'feedback'       => $db->table('trip_feedback')->where('trip_id', $id)->get()->getRowArray(),
        ]);
    }

    /**
     * Save Consignor & Consignee onto the trip's parent booking.
     * This lets the operator add these later on the Trip page when they
     * were left blank at booking time. Values persist on the booking row
     * so the LR/Docket picks them up automatically.
     */
    public function saveParties(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row || empty($row['booking_id'])) {
            return redirect()->to(site_url('trips/' . $id))->with('error', 'Trip or parent booking not found.');
        }

        $post = $this->request->getPost();

        // Billing Party — dropdown (Consignor / Consignee / Others) plus a
        // free-text override that only applies when "Others" is picked.
        // We store one canonical string on bookings.billing_party.
        $bpType  = (string) ($post['billing_party_type']  ?? '');
        $bpOther = trim((string) ($post['billing_party_other'] ?? ''));
        if ($bpType === 'Consignor' || $bpType === 'Consignee') {
            $billingParty = $bpType;
        } elseif ($bpType === 'Others' && $bpOther !== '') {
            $billingParty = $bpOther;
        } else {
            $billingParty = null;
        }

        $data = [
            'billing_party'       => $billingParty,
            'particulars_text'    => trim((string) ($post['particulars_text'] ?? '')) ?: null,
            'consignor_client_id' => !empty($post['consignor_client_id']) ? (int) $post['consignor_client_id'] : null,
            'consignee_client_id' => !empty($post['consignee_client_id']) ? (int) $post['consignee_client_id'] : null,
            'consignor_name'      => trim((string) ($post['consignor_name']    ?? '')) ?: null,
            'consignor_mobile'    => trim((string) ($post['consignor_mobile']  ?? '')) ?: null,
            'consignor_address'   => trim((string) ($post['consignor_address'] ?? '')) ?: null,
            'consignor_gstin'     => trim((string) ($post['consignor_gstin']   ?? '')) ?: null,
            'consignor_state'     => trim((string) ($post['consignor_state']   ?? '')) ?: null,
            'consignee_name'      => trim((string) ($post['consignee_name']    ?? '')) ?: null,
            'consignee_mobile'    => trim((string) ($post['consignee_mobile']  ?? '')) ?: null,
            'consignee_address'   => trim((string) ($post['consignee_address'] ?? '')) ?: null,
            'consignee_gstin'     => trim((string) ($post['consignee_gstin']   ?? '')) ?: null,
            'updated_by'          => $this->auth->id(),
        ];
        (new \App\Models\BookingModel())->update((int) $row['booking_id'], $data);

        return redirect()->to(site_url('trips/' . $id))->with('success', 'Parties & Billing saved on booking.');
    }

    public function assign(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $post = $this->request->getPost();
        $driverId  = !empty($post['driver_id'])  ? (int) $post['driver_id']  : null;
        $vehicleId = !empty($post['vehicle_id']) ? (int) $post['vehicle_id'] : null;

        $data = [
            'driver_id'      => $driverId,
            'vehicle_id'     => $vehicleId,
            'driver_name'    => $post['driver_name']    ?? null,
            'driver_mobile'  => $post['driver_mobile']  ?? null,
            'vehicle_number' => $post['vehicle_number'] ?? null,
            'updated_by'     => $this->auth->id(),
        ];

        // If IDs provided, copy canonical details + run compliance gate
        if ($driverId) {
            $d = (new DriverModel())->find($driverId);
            if ($d) {
                $data['driver_name']   = $d['driver_name'];
                $data['driver_mobile'] = $d['mobile'];
                // Driver compliance: license expiry + KYC
                if (!empty($d['license_expiry']) && strtotime($d['license_expiry']) < time()) {
                    return redirect()->back()->with('error', "Cannot assign — driver's licence expired on " . esc(date('d M Y', strtotime($d['license_expiry']))) . '.');
                }
                if (($d['kyc_status'] ?? 'Pending') !== 'Verified') {
                    return redirect()->back()->with('error', "Cannot assign — driver KYC is " . esc($d['kyc_status'] ?? 'Pending') . ". Verify in Drivers screen first.");
                }
            }
        }
        if ($vehicleId) {
            $v = (new VehicleModel())->find($vehicleId);
            if ($v) {
                // Vehicle compliance gate
                $today = date('Y-m-d');
                $blocks = [];
                if (!empty($v['insurance_expiry']) && $v['insurance_expiry'] < $today) $blocks[] = 'insurance expired ' . $v['insurance_expiry'];
                if (!empty($v['fitness_expiry'])   && $v['fitness_expiry']   < $today) $blocks[] = 'fitness expired '   . $v['fitness_expiry'];
                if (!empty($v['permit_expiry'])    && $v['permit_expiry']    < $today) $blocks[] = 'permit expired '    . $v['permit_expiry'];
                if ($blocks) {
                    return redirect()->back()->with('error', 'Cannot assign vehicle ' . esc($v['vehicle_number']) . ' — ' . esc(implode(', ', $blocks)) . '. Update vehicle docs first.');
                }
                $data['vehicle_number'] = $v['vehicle_number'];
            }
        }

        (new TripModel())->update($id, $data);

        // Kick off GPS tracking the moment the vehicle is assigned (SOP: track from loading point).
        // Cron also picks this up; eager call gives an instant first ping.
        $msg = 'Driver/vehicle assigned.';
        if (!empty($data['vehicle_number'])) {
            try {
                $svc = new \App\Libraries\LocoNavService();
                if ($svc->isConfigured()) {
                    $r = $svc->refreshVehicle((string) $data['vehicle_number'], $id);
                    if (!empty($r['ok'])) $msg .= ' GPS started.';
                } else {
                    $msg .= ' (GPS sync skipped — LocoNav not configured.)';
                }
            } catch (\Throwable $e) {
                log_message('warning', 'GPS kick on vehicle assign failed: ' . $e->getMessage());
            }
        }

        // Notify the client that a truck has been assigned (driver + vehicle + transporter)
        try {
            $r = (new \App\Libraries\NotificationService())->onTripAssigned($id);
            if (($r['sent'] ?? 0) > 0) $msg .= ' Client notified by email.';
        } catch (\Throwable $e) {
            log_message('warning', 'NotificationService::onTripAssigned failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('trips/' . $id))->with('success', $msg);
    }

    /** POST /trips/:id/assign — give this trip to a team member and notify. */
    /** POST /trips/:id/assign-staff — give this trip to a staff member and notify. */
    public function assignStaff(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $userId = (int) $this->request->getPost('assigned_to');
        (new TripModel())->update($id, [
            'assigned_to' => $userId > 0 ? $userId : null,
            'assigned_by' => $userId > 0 ? $this->auth->id() : null,
            'updated_by'  => $this->auth->id(),
        ]);

        if ($userId > 0) {
            \App\Libraries\Notify::toUser($userId,
                'Trip assigned to you — ' . ($row['trip_no'] ?? ('#' . $id)),
                trim(($row['loading_point'] ?? '') . ' → ' . ($row['unloading_point'] ?? ''), ' →') ?: null,
                site_url('trips/' . $id),
                ['type' => 'trip_assigned', 'icon' => 'person-check']);
        }
        return redirect()->to(site_url('trips/' . $id))->with('success', 'Trip assignment updated.');
    }

    public function changeStatus(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $newStatus = (string) $this->request->getPost('new_status');
        if (!in_array($newStatus, TripModel::STATUSES, true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        // Enforce forward-only progression. Once at "In Transit" you can't go
        // back to "Loading" etc. The dropdown hides bad options; this is the
        // server-side belt for direct POSTs and stale forms.
        $currentStatus = (string) $row['current_status'];
        $allowed = TripModel::allowedNextStatuses($currentStatus);
        if (!in_array($newStatus, $allowed, true)) {
            if (TripModel::isTerminal($currentStatus)) {
                return redirect()->back()->with('error', 'Trip is ' . $currentStatus . ' — status cannot be changed anymore.');
            }
            return redirect()->back()->with('error',
                'Cannot move from "' . $currentStatus . '" back to "' . $newStatus . '". Status can only move forward in the pipeline (or to Cancelled).');
        }

        $notes = (string) $this->request->getPost('notes');
        $delay = (string) $this->request->getPost('delay_reason');

        $now = date('Y-m-d H:i:s');
        $update = [
            'current_status' => $newStatus,
            'updated_by'     => $this->auth->id(),
        ];

        // Detention dwell-timestamp auto-fill — captures when truck arrived/left each gate
        if ($newStatus === 'Loading'    && empty($row['loading_arrived_at']))    $update['loading_arrived_at']    = $now;
        if ($newStatus === 'In Transit' && empty($row['loading_departed_at']))   $update['loading_departed_at']   = $now;
        if ($newStatus === 'Arrived'    && empty($row['unloading_arrived_at']))  $update['unloading_arrived_at']  = $now;
        if ($newStatus === 'Delivered'  && empty($row['unloading_departed_at'])) $update['unloading_departed_at'] = $now;

        if ($newStatus === 'In Transit' && empty($row['dispatch_datetime'])) {
            $update['dispatch_datetime'] = $now;
        }
        if ($newStatus === 'Delivered' && empty($row['delivery_datetime'])) {
            $update['delivery_datetime'] = $now;
        }
        if ($delay !== '') $update['delay_reason'] = $delay;

        // Recompute detention whenever any of the four timestamps change
        $merged = array_merge($row, $update);
        $det = $this->computeDetention($merged);
        $update['detention_billable_hours'] = $det['hours'];
        $update['detention_amount']         = $det['amount'];

        // Closing trip marks booking Completed
        if ($newStatus === 'Closed') {
            $booking = (new BookingModel())->find((int) $row['booking_id']);
            if ($booking) {
                (new BookingModel())->update((int) $row['booking_id'], ['booking_status' => 'Completed']);
            }
        }

        (new TripModel())->update($id, $update);
        (new TripStatusHistoryModel())->log($id, (string) $row['current_status'], $newStatus, $notes ?: null, $this->auth->id());

        // Fire workflow notifications (In Transit / Arrived / Delivered emails to client)
        try {
            (new \App\Libraries\NotificationService())->onTripStatusChanged($id, $newStatus);
        } catch (\Throwable $e) {
            log_message('warning', 'NotificationService::onTripStatusChanged failed: ' . $e->getMessage());
        }

        // In-app notifications on every trip status change → the sales owner who
        // booked it + everyone who can see trips (ops). Milestones get a richer
        // icon. Role-name agnostic via permission targeting.
        try {
            $booking = (new BookingModel())->find((int) $row['booking_id']);
            $bkNo    = $booking['booking_no'] ?? ('Booking #' . ($row['booking_id'] ?? '?'));
            $tripNo  = $row['trip_no'] ?? ('Trip #' . $id);
            $icon    = match ($newStatus) {
                'Delivered'    => 'box-seam', 'POD Received' => 'file-earmark-check',
                'In Transit'   => 'truck',    'Closed'       => 'check2-circle',
                'Cancelled'    => 'x-octagon', default        => 'geo-alt',
            };
            $title = $tripNo . ' — ' . $newStatus;
            $body  = $bkNo . ' · ' . (($row['loading_point'] ?? '') . ' → ' . ($row['unloading_point'] ?? ''));
            $link  = site_url('trips/' . $id);
            // The originating sales/booking owner
            if (!empty($booking['created_by'])) {
                \App\Libraries\Notify::toUser((int) $booking['created_by'], $title, $body, $link,
                    ['type' => 'trip_status', 'icon' => $icon]);
            }
            // Assignment loop: the trip's assignee + whoever assigned it stay in
            // the loop on every status move.
            \App\Libraries\Notify::toUsers(
                [(int) ($row['assigned_to'] ?? 0), (int) ($row['assigned_by'] ?? 0)],
                $title, $body, $link, ['type' => 'trip_progress', 'icon' => $icon]);
            // Ops team (anyone who can view trips)
            \App\Libraries\Notify::toPermission('trips', 'can_view', $title, $body, $link,
                ['type' => 'trip_status', 'icon' => $icon]);
        } catch (\Throwable $e) {
            log_message('warning', 'Notify trip status failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('trips/' . $id))->with('success', 'Status moved to ' . $newStatus . '.');
    }

    /** POST /trips/bulk — apply status change to a batch of trip IDs */
    public function bulk()
    {
        $ids    = array_map('intval', (array) $this->request->getPost('ids'));
        $action = (string) $this->request->getPost('action');
        if (empty($ids) || $action === '') return redirect()->back()->with('error', 'Select rows and an action.');
        [$kind, $value] = array_pad(explode('::', $action, 2), 2, '');
        if ($kind !== 'status' || !in_array($value, TripModel::STATUSES, true)) {
            return redirect()->back()->with('error', 'Unsupported action.');
        }
        // Hijack the single-trip handler in a loop. POST data is mutated so
        // changeStatus() reads the right new_status each iteration.
        $req = $this->request;
        $orig = $_POST;
        $changed = 0;
        foreach ($ids as $id) {
            $_POST = ['new_status' => $value, 'notes' => 'Bulk update'];
            try { $this->changeStatus($id); $changed++; } catch (\Throwable $e) {}
        }
        $_POST = $orig;
        return redirect()->to(site_url('trips'))->with('success', "$changed trip(s) updated to $value.");
    }

    public function uploadPod(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $file = $this->request->getFile('pod_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Choose a valid file.');
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return redirect()->back()->with('error', 'File too large (10MB max).');
        }
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic'];
        $ext     = strtolower($file->getExtension() ?: $file->guessExtension() ?: '');
        if (!in_array($ext, $allowed, true)) {
            return redirect()->back()->with('error', 'Only PDF/image files allowed.');
        }

        // Capture file metadata BEFORE move — after move() the temp file is gone.
        $originalName = $file->getClientName();
        $fileSize     = (int) $file->getSize();
        $mimeType     = $file->getMimeType() ?: $file->getClientMimeType();

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'trips' . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $stored = $file->getRandomName();
        $file->move($dir, $stored);

        (new DocumentModel())->insert([
            'module_name'         => 'trip',
            'module_ref_id'       => $id,
            'document_type'       => 'POD',
            'original_file_name'  => $originalName,
            'stored_file_name'    => $stored,
            'file_path'           => 'trips/' . $id . '/' . $stored,
            'file_size'           => $fileSize,
            'mime_type'           => $mimeType,
            'source_channel'      => 'upload',
            'verification_status' => 'Pending',
            'uploaded_by'         => $this->auth->id(),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        (new TripModel())->update($id, [
            'pod_status'      => 'Received',
            'pod_received_at' => date('Y-m-d H:i:s'),
            'updated_by'      => $this->auth->id(),
        ]);

        $current = (new TripModel())->find($id);
        if ($current['current_status'] !== 'POD Received' && $current['current_status'] !== 'Closed') {
            (new TripModel())->update($id, ['current_status' => 'POD Received']);
            (new TripStatusHistoryModel())->log($id, $current['current_status'], 'POD Received', 'POD uploaded', $this->auth->id());
        }

        // Notify the trip's assignee (if any), Ops team, and Accounts (POD is
        // the trigger to invoice) + admin oversight.
        $title = 'POD uploaded — ' . ($row['trip_no'] ?? ('Trip #' . $id));
        $link  = site_url('trips/' . $id);
        if (!empty($row['assigned_to'])) {
            \App\Libraries\Notify::toUser((int) $row['assigned_to'], $title, 'Proof of delivery received', $link,
                ['type' => 'pod_uploaded', 'icon' => 'file-earmark-check']);
        }
        \App\Libraries\Notify::toPermission('trips', 'can_view', $title, 'Proof of delivery received', $link,
            ['type' => 'pod_uploaded', 'icon' => 'file-earmark-check']);
        \App\Libraries\Notify::toPermission('invoices', 'can_add', $title, 'POD received — ready to invoice', $link,
            ['type' => 'pod_uploaded', 'icon' => 'file-earmark-check']);

        return redirect()->to(site_url('trips/' . $id))->with('success', 'POD uploaded.');
    }

    public function downloadDocument(int $id, int $docId)
    {
        $doc = (new DocumentModel())->find($docId);
        if (!$doc || $doc['module_name'] !== 'trip' || (int) $doc['module_ref_id'] !== $id) {
            return redirect()->to(site_url('trips'))->with('error', 'Not found.');
        }
        $full = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
        if (!is_file($full)) return redirect()->to(site_url('trips/' . $id))->with('error', 'File missing on disk.');
        return $this->response->download($full, null)->setFileName($doc['original_file_name']);
    }

    /** Send a pre-baked WhatsApp update to the client for this trip. */
    /** Generate (or rotate) a driver-phone tracking link. */
    public function startDriverTrack(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');
        if (in_array($row['current_status'], ['Closed','Cancelled'], true)) {
            return redirect()->back()->with('error', 'Cannot start tracking on a closed trip.');
        }
        $token = bin2hex(random_bytes(32));
        $update = [
            'driver_track_token'      => $token,
            'driver_track_started_at' => date('Y-m-d H:i:s'),
            'driver_track_last_ping'  => null,
        ];
        // Also generate (if missing) the client-facing live-tracking token + 14-day expiry.
        if (empty($row['client_track_token'])) {
            $update['client_track_token']      = bin2hex(random_bytes(32));
            $update['client_track_expires_at'] = date('Y-m-d H:i:s', strtotime('+14 days'));
        }
        (new TripModel())->update($id, $update);

        // Notify dispatch by email with the driver link (copy for ops record).
        // Driver is reached via WhatsApp on the trip page; email mirrors the link
        // to ops so the link is searchable in their inbox.
        try {
            (new \App\Libraries\NotificationService())->onDriverDispatchStarted($id);
        } catch (\Throwable $e) {
            log_message('warning', 'NotificationService::onDriverDispatchStarted failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('trips/' . $id))
            ->with('success', 'Driver tracking link generated. Share with the driver via WhatsApp / SMS.');
    }

    /**
     * Staff replies to a driver message thread. Multipart: optional `attachment`
     * + `body` (text). At least one must be present.
     */
    public function driverChatReply(int $id)
    {
        $trip = (new TripModel())->find($id);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $body = trim((string) $this->request->getPost('body'));
        $file = $this->request->getFile('attachment');
        if ($body === '' && (!$file || !$file->isValid())) {
            return redirect()->to(site_url('trips/' . $id) . '#driver-chat')->with('error', 'Type a message or attach a file.');
        }
        if (mb_strlen($body) > 4000) $body = mb_substr($body, 0, 4000);

        $attachmentPath = null;
        $attachmentMime = null;
        $attachmentOrig = null;

        if ($file && $file->isValid()) {
            if ($file->getSize() > 15 * 1024 * 1024) {
                return redirect()->back()->with('error', 'Attachment too large (max 15 MB).');
            }
            $allowed = ['pdf','jpg','jpeg','png','webp','heic','heif','mp4','3gp','m4a','mp3','wav','ogg'];
            $ext = strtolower($file->getExtension() ?: $file->guessExtension() ?: '');
            if (!in_array($ext, $allowed, true)) {
                return redirect()->back()->with('error', 'Only documents, images, audio, or video allowed.');
            }
            $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'trips' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'messages';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $stored = $file->getRandomName();
            $attachmentOrig = $file->getClientName();
            $attachmentMime = $file->getMimeType() ?: $file->getClientMimeType();
            $file->move($dir, $stored);
            $attachmentPath = 'trips/' . $id . '/messages/' . $stored;
        }

        \Config\Database::connect()->table('driver_messages')->insert([
            'trip_id'           => $id,
            'direction'         => 'staff',
            'user_id'           => $this->auth->id(),
            'body'              => $body !== '' ? $body : null,
            'attachment_path'   => $attachmentPath,
            'attachment_mime'   => $attachmentMime,
            'attachment_orig'   => $attachmentOrig,
            'is_read_by_staff'  => 1,
            'is_read_by_driver' => 0,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('trips/' . $id) . '#driver-chat')->with('success', 'Reply sent to driver.');
    }

    /** Serve a driver-message attachment to authenticated staff. */
    public function driverChatAttachment(int $id, int $msgId)
    {
        $db  = \Config\Database::connect();
        $msg = $db->table('driver_messages')->where('id', $msgId)->where('trip_id', $id)->get()->getRowArray();
        if (!$msg || empty($msg['attachment_path'])) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }
        $abs = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $msg['attachment_path']);
        if (!is_file($abs)) {
            return $this->response->setStatusCode(404)->setBody('File missing on disk');
        }
        return $this->response
            ->setHeader('Content-Type', $msg['attachment_mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . ($msg['attachment_orig'] ?: basename($abs)) . '"')
            ->setBody(file_get_contents($abs));
    }

    /** Revoke the driver-phone tracking link (e.g., trip closed, lost phone). */
    public function stopDriverTrack(int $id)
    {
        (new TripModel())->update($id, [
            'driver_track_token'      => null,
            'driver_track_started_at' => null,
        ]);
        return redirect()->to(site_url('trips/' . $id))->with('success', 'Driver tracking link revoked.');
    }

    public function notifyClient(int $id)
    {
        $row = (new TripModel())->withJoins()->where('trips.id', $id)->first();
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $kind = (string) $this->request->getPost('kind');
        $map  = [
            'placed'    => 'vehicle_placed',
            'in_transit'=> 'trip_in_transit',
            'delivered' => 'trip_delivered',
        ];
        if (!isset($map[$kind])) return redirect()->back()->with('error', 'Unknown notification type.');

        $template = (new WhatsappTemplateModel())->getByKey($map[$kind]);
        if (!$template) return redirect()->back()->with('error', 'Template missing: ' . $map[$kind]);

        $client = (new ClientModel())->find((int) ($row['client_id'] ?? 0));
        $to     = $client['mobile'] ?? '';
        if (!$to) return redirect()->back()->with('error', 'Client has no mobile number.');

        if ($kind === 'placed') {
            $vars = [$row['booking_no'], $row['vehicle_number'] ?? '—', $row['driver_name'] ?? '—', $row['driver_mobile'] ?? '—'];
        } elseif ($kind === 'in_transit') {
            $vars = [$row['trip_no'], $row['loading_point'] ?? ($row['route_text'] ?? '—')];
        } else {
            $vars = [$row['trip_no']];
        }

        (new WhatsAppService())->sendTemplate(
            $to,
            $template['template_name'],
            $vars,
            $template['language_code'] ?? 'en',
            [
                'module_name'   => 'trip',
                'module_ref_id' => $id,
                'audience_type' => 'client',
                'template_key'  => $template['template_key'],
            ]
        );
        return redirect()->to(site_url('trips/' . $id))->with('success', 'WhatsApp update queued to client.');
    }

    /**
     * Computes billable detention hours + amount from a trip's loading/unloading
     * dwell timestamps + the client's free-hours policy + per-hour rate.
     */
    private function computeDetention(array $trip): array
    {
        $bk = !empty($trip['booking_id']) ? (new BookingModel())->find((int) $trip['booking_id']) : null;
        $client = $bk && !empty($bk['client_id']) ? (new ClientModel())->find((int) $bk['client_id']) : null;
        if (!$client) return ['hours' => 0, 'amount' => 0];

        $freeLd = (int) ($client['detention_free_hours_loading']   ?? 4);
        $freeUl = (int) ($client['detention_free_hours_unloading'] ?? 4);
        $rate   = (float) ($client['detention_rate_per_hour'] ?? 0);

        $dwellLd = $this->dwell($trip['loading_arrived_at']   ?? null, $trip['loading_departed_at']   ?? null);
        $dwellUl = $this->dwell($trip['unloading_arrived_at'] ?? null, $trip['unloading_departed_at'] ?? null);

        $billLd = max(0, $dwellLd - $freeLd);
        $billUl = max(0, $dwellUl - $freeUl);
        $hours  = $billLd + $billUl;
        return ['hours' => round($hours, 2), 'amount' => round($hours * $rate, 2)];
    }

    private function dwell(?string $start, ?string $end): float
    {
        if (!$start || !$end) return 0;
        $a = strtotime($start); $b = strtotime($end);
        if (!$a || !$b || $b <= $a) return 0;
        return ($b - $a) / 3600.0;
    }

    /** Save cargo insurance card. */
    public function saveInsurance(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        (new TripModel())->update($id, [
            'cargo_value_inr'     => $this->request->getPost('cargo_value_inr') ?: null,
            'insurance_policy_no' => $this->request->getPost('insurance_policy_no') ?: null,
            'insurance_provider'  => $this->request->getPost('insurance_provider') ?: null,
            'updated_by'          => $this->auth->id(),
        ]);
        return redirect()->to(site_url('trips/' . $id))->with('success', 'Cargo insurance saved.');
    }

    /** Generate (or rotate) a token for the consignee e-POD signing link. */
    public function startEpod(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');
        if (in_array($row['current_status'], ['Cancelled'], true)) {
            return redirect()->back()->with('error', 'Cannot generate e-POD on a cancelled trip.');
        }
        (new TripModel())->update($id, ['epod_token' => bin2hex(random_bytes(32))]);
        return redirect()->to(site_url('trips/' . $id))->with('success', 'e-POD link generated. Share with the consignee.');
    }

    public function revokeEpod(int $id)
    {
        (new TripModel())->update($id, ['epod_token' => null]);
        return redirect()->to(site_url('trips/' . $id))->with('success', 'e-POD link revoked.');
    }

    /** Manual override of any of the 4 dwell timestamps (e.g., backdate when added late). */
    public function saveDwell(int $id)
    {
        $row = (new TripModel())->find($id);
        if (!$row) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $update = [];
        foreach (['loading_arrived_at','loading_departed_at','unloading_arrived_at','unloading_departed_at'] as $k) {
            $v = trim((string) $this->request->getPost($k));
            $update[$k] = $v !== '' ? date('Y-m-d H:i:s', strtotime($v)) : null;
        }
        $merged = array_merge($row, $update);
        $det = $this->computeDetention($merged);
        $update['detention_billable_hours'] = $det['hours'];
        $update['detention_amount']         = $det['amount'];
        $update['updated_by'] = $this->auth->id();

        (new TripModel())->update($id, $update);
        return redirect()->to(site_url('trips/' . $id))->with('success', 'Detention recalculated: ₹' . number_format($det['amount'], 2) . ' (' . $det['hours'] . ' hrs).');
    }
}

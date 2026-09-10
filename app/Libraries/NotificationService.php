<?php

namespace App\Libraries;

use App\Models\SettingModel;

/**
 * Central hub for workflow notifications (email). Each event method fetches the
 * required data, builds template variables, and sends to the appropriate
 * audience(s). All sends route through EmailService (which logs to email_logs
 * and dispatches via the configured driver).
 *
 * Phase E layers an `alert_rules` matrix on top of these calls so that each
 * (event, audience) can be toggled on/off from the Settings UI. The matrix is
 * consulted inside `audienceWants()`; if no rule exists, the channel is
 * considered enabled by default.
 */
class NotificationService
{
    private EmailService $email;
    private SettingModel $settings;
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->email    = new EmailService();
        $this->settings = new SettingModel();
        $this->db       = \Config\Database::connect();
    }

    /* ───────────────────── Event: RFQ dispatched ───────────────────── */

    /**
     * For each pending vendor on this RFQ: ensure a quote_token exists, then
     * send the vendor_rfq_invite email (if vendor has an email).
     *
     * @return array{sent:int, skipped:int, errors:array<string>}
     */
    public function onRfqDispatched(int $rfqId, ?int $onlyVendorId = null): array
    {
        $rfq = $this->db->table('rfq_master')->where('id', $rfqId)->get()->getRowArray();
        if (!$rfq) return ['sent' => 0, 'skipped' => 0, 'errors' => ['rfq not found']];

        $q = $this->db->table('rfq_vendors rv')
            ->select('rv.id AS rv_id, rv.rfq_id, rv.vendor_id, rv.quote_token, rv.sent_at, v.company_name, v.owner_name, v.email')
            ->join('vendors v', 'v.id = rv.vendor_id')
            ->where('rv.rfq_id', $rfqId);
        if ($onlyVendorId) $q = $q->where('rv.vendor_id', $onlyVendorId);
        $rows = $q->get()->getResultArray();

        $sent = 0; $skipped = 0; $errors = [];
        foreach ($rows as $r) {
            $email = trim((string) $r['email']);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++; continue;
            }
            if (!$this->audienceWants('rfq_dispatched', 'vendor', 'email')) {
                $skipped++; continue;
            }

            // Ensure a quote_token exists (idempotent)
            $token = $r['quote_token'];
            if (!$token) {
                $token = bin2hex(random_bytes(32));
                $this->db->table('rfq_vendors')->where('id', (int) $r['rv_id'])->update([
                    'quote_token'            => $token,
                    'quote_token_expires_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
                ]);
            }

            $vars = [
                'vendor_name'   => $r['company_name'] ?: $r['owner_name'] ?: 'Partner',
                'rfq_no'        => $rfq['rfq_no'],
                'route'         => trim(($rfq['pickup_city'] ?? '') . ' → ' . ($rfq['drop_city'] ?? ''), ' →'),
                'vehicle_type'  => $rfq['vehicle_type']      ?: '—',
                'material'      => $rfq['material_category'] ?: '—',
                'weight'        => trim(($rfq['weight'] ?? '') . ' ' . ($rfq['weight_unit'] ?? '')),
                'loading_date'  => $rfq['loading_date'] ? date('d M Y', strtotime($rfq['loading_date'])) : 'TBC',
                'quote_link'    => site_url('quote/' . $token),
            ];
            $r2 = $this->email->sendTemplate('vendor_rfq_invite', $email, $vars, [
                'related_module' => 'rfq', 'related_id' => $rfqId,
            ]);
            if (!empty($r2['ok']) || !empty($r2['queued'])) $sent++; else $errors[] = $r2['error'] ?? 'failed';

            // Mark the rfq_vendor sent_at if not already set
            if (empty($r['sent_at'])) {
                $this->db->table('rfq_vendors')->where('id', (int) $r['rv_id'])->update([
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        return ['sent' => $sent, 'skipped' => $skipped, 'errors' => $errors];
    }

    /* ───────────────────── Event: vendor quote selected ───────────────────── */

    public function onQuoteSelected(int $rfqId, int $quotationId): array
    {
        $sent = 0; $errors = [];

        $rfq   = $this->db->table('rfq_master')->where('id', $rfqId)->get()->getRowArray();
        $quote = $this->db->table('quotations')->where('id', $quotationId)->get()->getRowArray();
        if (!$rfq || !$quote) return ['sent' => 0, 'errors' => ['rfq or quote missing']];
        $vendor = $this->db->table('vendors')->where('id', (int) $quote['vendor_id'])->get()->getRowArray();

        // 1) Vendor awarded
        if ($vendor && $this->audienceWants('quote_selected', 'vendor', 'email')) {
            $ve = trim((string) ($vendor['email'] ?? ''));
            if ($ve !== '' && filter_var($ve, FILTER_VALIDATE_EMAIL)) {
                $vars = [
                    'vendor_name'      => $vendor['company_name'] ?: $vendor['owner_name'] ?: 'Partner',
                    'rfq_no'           => $rfq['rfq_no'],
                    'trip_no'          => '(to be created)',
                    'route'            => trim(($rfq['pickup_city'] ?? '') . ' → ' . ($rfq['drop_city'] ?? ''), ' →'),
                    'vehicle_type'     => $rfq['vehicle_type'] ?: '—',
                    'loading_date'     => $rfq['loading_date'] ? date('d M Y', strtotime($rfq['loading_date'])) : 'TBC',
                    'rate'             => number_format((float) $quote['quote_amount'], 0),
                    'consignee_name'   => '—',
                    'consignee_address'=> '—',
                    'consignee_mobile' => '—',
                    'contact_phone'    => (string) $this->settings->get('company_phone', '') ?: '—',
                ];
                $r = $this->email->sendTemplate('vendor_won_assignment', $ve, $vars, [
                    'related_module' => 'rfq', 'related_id' => $rfqId,
                ]);
                if (!empty($r['ok']) || !empty($r['queued'])) $sent++; else $errors[] = $r['error'] ?? 'failed';
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    /* ───────────────────── Event: trip created from booking ───────────────────── */

    /**
     * Fires when a trip is created (vendor assigned to a booking). Notifies the
     * client that a truck is assigned, with vendor/driver/vehicle details.
     */
    public function onTripAssigned(int $tripId): array
    {
        $sent = 0; $errors = [];
        $row = $this->loadTripContext($tripId);
        if (!$row) return ['sent' => 0, 'errors' => ['trip not found']];

        if ($this->audienceWants('trip_assigned', 'client', 'email')) {
            $ce = trim((string) ($row['client']['email'] ?? ''));
            if ($ce !== '' && filter_var($ce, FILTER_VALIDATE_EMAIL)) {
                $vars = [
                    'client_name'    => $row['client']['contact_name'] ?: $row['client']['company_name'] ?: 'Customer',
                    'booking_no'     => $row['booking']['booking_no'] ?? '',
                    'trip_no'        => $row['trip']['trip_no'] ?? '',
                    'vehicle_number' => $row['trip']['vehicle_number'] ?: '—',
                    'vehicle_type'   => $row['booking']['vehicle_type'] ?: '—',
                    'driver_name'    => $row['trip']['driver_name'] ?: '—',
                    'driver_mobile'  => $row['trip']['driver_mobile'] ?: '—',
                    'route'          => $row['booking']['route_text'] ?: '—',
                    'loading_date'   => $row['booking']['loading_date'] ? date('d M Y', strtotime($row['booking']['loading_date'])) : 'TBC',
                    'vendor_company' => $row['vendor']['company_name'] ?? '—',
                ];
                $r = $this->email->sendTemplate('client_truck_assigned', $ce, $vars, [
                    'related_module'    => 'trip',
                    'related_id'        => $tripId,
                    'related_client_id' => (int) ($row['client']['id'] ?? 0),
                ]);
                if (!empty($r['ok']) || !empty($r['queued'])) $sent++; else $errors[] = $r['error'] ?? 'failed';
            }
        }
        return ['sent' => $sent, 'errors' => $errors];
    }

    /* ───────────────────── Event: trip status changed ───────────────────── */

    public function onTripStatusChanged(int $tripId, string $newStatus): array
    {
        $sent = 0; $errors = [];
        $map = [
            'In Transit' => ['template' => 'trip_in_transit',         'event' => 'trip_in_transit'],
            'Arrived'    => ['template' => 'trip_arrived_destination','event' => 'trip_arrived'],
            'Delivered'  => ['template' => 'trip_delivered',          'event' => 'trip_delivered'],
        ];
        if (!isset($map[$newStatus])) return ['sent' => 0, 'errors' => []];

        $tplKey = $map[$newStatus]['template'];
        $eventKey = $map[$newStatus]['event'];

        $row = $this->loadTripContext($tripId);
        if (!$row) return ['sent' => 0, 'errors' => ['trip not found']];

        // Client notification
        if ($this->audienceWants($eventKey, 'client', 'email')) {
            $ce = trim((string) ($row['client']['email'] ?? ''));
            if ($ce !== '' && filter_var($ce, FILTER_VALIDATE_EMAIL)) {
                $vars = $this->tripVars($row);
                $vars['arrived_at'] = date('d M Y H:i');
                $r = $this->email->sendTemplate($tplKey, $ce, $vars, [
                    'related_module'    => 'trip',
                    'related_id'        => $tripId,
                    'related_client_id' => (int) ($row['client']['id'] ?? 0),
                ]);
                if (!empty($r['ok']) || !empty($r['queued'])) $sent++; else $errors[] = $r['error'] ?? 'failed';
            }
        }

        // Trip delivered → also fire the feedback request (once, with a fresh token)
        if ($newStatus === 'Delivered') {
            try {
                $r = $this->onTripDelivered($tripId);
                $sent += $r['sent'];
                if (!empty($r['errors'])) $errors = array_merge($errors, $r['errors']);
            } catch (\Throwable $e) {
                $errors[] = 'feedback dispatch: ' . $e->getMessage();
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    /**
     * Trip just became Delivered → generate (or reuse) a feedback token and
     * email the client a link to the feedback form. Idempotent: re-running
     * doesn't send a second email unless the existing token was already used.
     */
    public function onTripDelivered(int $tripId): array
    {
        $sent = 0; $errors = [];
        if (!$this->audienceWants('trip_feedback_request', 'client', 'email')) {
            return ['sent' => 0, 'errors' => []];
        }
        $row = $this->loadTripContext($tripId);
        if (!$row) return ['sent' => 0, 'errors' => ['trip not found']];

        $clientEmail = trim((string) ($row['client']['email'] ?? ''));
        if ($clientEmail === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => 0, 'errors' => ['client has no valid email']];
        }

        $now    = date('Y-m-d H:i:s');
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        $existing = $this->db->table('trip_feedback')->where('trip_id', $tripId)->get()->getRowArray();
        if ($existing) {
            // Already requested. If already submitted, don't re-send. If not, optionally re-send.
            if (!empty($existing['submitted_at'])) {
                return ['sent' => 0, 'errors' => []];  // already responded — no nag
            }
            $token = $existing['feedback_token'];
        } else {
            $token = bin2hex(random_bytes(32));
            $this->db->table('trip_feedback')->insert([
                'trip_id'          => $tripId,
                'feedback_token'   => $token,
                'token_expires_at' => $expiry,
                'requested_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        $r = $this->email->sendTemplate('trip_feedback_request', $clientEmail, [
            'client_name'   => $row['client']['contact_name'] ?: $row['client']['company_name'] ?: 'Customer',
            'trip_no'       => $row['trip']['trip_no'] ?? '',
            'route'         => $row['booking']['route_text'] ?? '',
            'feedback_link' => site_url('feedback/' . $token),
        ], [
            'related_module'    => 'trip',
            'related_id'        => $tripId,
            'related_client_id' => (int) ($row['client']['id'] ?? 0),
        ]);
        if (!empty($r['ok']) || !empty($r['queued'])) $sent++; else $errors[] = $r['error'] ?? 'failed';

        return ['sent' => $sent, 'errors' => $errors];
    }

    /* ───────────────────── Event: driver dispatch link ───────────────────── */

    /**
     * Fires when staff generates a driver_track_token. If the driver has an
     * email (drivers.* doesn't currently store email), we'd send there; for
     * now we also send a copy to the company_email so staff have a record.
     */
    public function onDriverDispatchStarted(int $tripId): array
    {
        $sent = 0; $errors = [];
        $row = $this->loadTripContext($tripId);
        if (!$row || empty($row['trip']['driver_track_token'])) {
            return ['sent' => 0, 'errors' => ['no token']];
        }
        $link = site_url('d/' . $row['trip']['driver_track_token']);
        $vars = [
            'driver_name'    => $row['trip']['driver_name'] ?: 'Driver',
            'trip_no'        => $row['trip']['trip_no'] ?? '',
            'lr_no'          => $row['trip']['lr_no']    ?: '—',
            'route'          => $row['booking']['route_text'] ?: '—',
            'vehicle_number' => $row['trip']['vehicle_number'] ?: '—',
            'loading_point'  => $row['trip']['loading_point']  ?: '—',
            'driver_link'    => $link,
        ];
        // Copy to staff inbox so they have an emailable copy of the link
        if ($this->audienceWants('driver_dispatch', 'internal', 'email')) {
            $to = (string) $this->settings->get('company_email', '');
            if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $r = $this->email->sendTemplate('driver_dispatch_link', $to, $vars, [
                    'related_module' => 'trip', 'related_id' => $tripId,
                ]);
                if (!empty($r['ok']) || !empty($r['queued'])) $sent++; else $errors[] = $r['error'] ?? 'failed';
            }
        }
        return ['sent' => $sent, 'errors' => $errors];
    }

    /* ───────────────────── Helpers ───────────────────── */

    private function loadTripContext(int $tripId): ?array
    {
        $trip = $this->db->table('trips')->where('id', $tripId)->where('deleted_at', null)->get()->getRowArray();
        if (!$trip) return null;
        $booking = $this->db->table('bookings')->where('id', (int) $trip['booking_id'])->get()->getRowArray();
        $client  = !empty($booking['client_id'])
            ? $this->db->table('clients')->where('id', (int) $booking['client_id'])->get()->getRowArray()
            : null;
        $vendor  = !empty($trip['vendor_id'])
            ? $this->db->table('vendors')->where('id', (int) $trip['vendor_id'])->get()->getRowArray()
            : null;
        return ['trip' => $trip, 'booking' => $booking ?: [], 'client' => $client ?: [], 'vendor' => $vendor ?: []];
    }

    private function tripVars(array $row): array
    {
        return [
            'trip_no'        => $row['trip']['trip_no'] ?? '',
            'lr_no'          => $row['trip']['lr_no']    ?? '',
            'route'          => $row['booking']['route_text'] ?? '',
            'vehicle_number' => $row['trip']['vehicle_number'] ?? '',
            'driver_name'    => $row['trip']['driver_name']  ?? '',
        ];
    }

    /**
     * Consult the `alert_rules` table (built in Phase E). If no rule exists,
     * the default is "enabled". Returns false when an explicit rule says off.
     */
    private function audienceWants(string $eventKey, string $audience, string $channel = 'email'): bool
    {
        if (!$this->db->tableExists('alert_rules')) return true;
        $row = $this->db->table('alert_rules')
            ->where('event_key', $eventKey)
            ->where('audience',  $audience)
            ->where('channel',   $channel)
            ->get()->getRowArray();
        if (!$row) return true;
        return (int) ($row['enabled'] ?? 1) === 1;
    }
}

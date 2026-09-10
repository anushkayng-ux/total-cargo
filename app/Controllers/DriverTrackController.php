<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\TripModel;
use App\Models\GpsLogModel;
use App\Models\LatestVehicleStatusModel;
use App\Models\TripStatusHistoryModel;
use App\Models\DocumentModel;

/**
 * Public, token-gated driver-phone tracking.
 *
 *   GET  /d/{token}       — render the PWA page
 *   POST /d/{token}/ping  — accept a JSON {lat,lng,acc,ts,speed}
 *   POST /d/{token}/stop  — driver opted out
 *   GET  /d/{token}/manifest.webmanifest — PWA manifest
 *   GET  /d/{token}/sw.js — service worker
 *
 * No auth filter: the unguessable 64-char token IS the credential.
 */
class DriverTrackController extends Controller
{
    protected $helpers = ['url'];

    private function trip(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $row = (new TripModel())
            ->where('driver_track_token', $token)
            ->where('deleted_at', null)
            ->first();
        if (!$row) return null;
        if (in_array($row['current_status'], ['Closed','Cancelled'], true)) return null;
        return $row;
    }

    public function index(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) {
            return $this->response->setStatusCode(404)->setBody(view('driver/invalid'));
        }

        $db = \Config\Database::connect();
        $booking = $db->table('bookings')
            ->select('route_text, client_id, consignee_name, consignee_mobile, consignee_address')
            ->where('id', (int) $trip['booking_id'])->get()->getRowArray();

        $client = null;
        if (!empty($booking['client_id'])) {
            $client = $db->table('clients')
                ->select('company_name, contact_name, mobile, alt_mobile, address, city')
                ->where('id', (int) $booking['client_id'])->get()->getRowArray();
        }

        $vendor = null;
        if (!empty($trip['vendor_id'])) {
            $vendor = $db->table('vendors')
                ->select('company_name, owner_name, mobile, alt_mobile, whatsapp_no')
                ->where('id', (int) $trip['vendor_id'])->get()->getRowArray();
        }

        $settings = [];
        $rows = $db->table('settings')->whereIn('setting_key', ['company_name','company_phone','company_email'])->get()->getResultArray();
        foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

        $contacts = [
            'agency' => [
                'role'   => 'agency',
                'name'   => $settings['company_name']  ?? env('tpt.appName', 'TPT'),
                'phone'  => $this->normalizePhone($settings['company_phone'] ?? null),
                'detail' => $settings['company_email'] ?? null,
            ],
            'transporter' => $vendor ? [
                'role'   => 'transporter',
                'name'   => $vendor['company_name'] ?? $vendor['owner_name'] ?? null,
                'phone'  => $this->normalizePhone($vendor['whatsapp_no'] ?: $vendor['mobile']),
                'alt'    => $this->normalizePhone($vendor['alt_mobile']),
                'detail' => $vendor['owner_name'] ?? null,
            ] : null,
            'pickup' => $client ? [
                'role'   => 'pickup',
                'name'   => $client['company_name'] ?? null,
                'phone'  => $this->normalizePhone($client['mobile']),
                'alt'    => $this->normalizePhone($client['alt_mobile']),
                'detail' => trim(($client['contact_name'] ?? '') . ' · ' . ($client['city'] ?? ''), ' ·'),
                'address'=> $client['address'] ?? null,
            ] : null,
            'destination' => (!empty($booking['consignee_name']) || !empty($booking['consignee_mobile'])) ? [
                'role'    => 'destination',
                'name'    => $booking['consignee_name'] ?? null,
                'phone'   => $this->normalizePhone($booking['consignee_mobile']),
                'detail'  => null,
                'address' => $booking['consignee_address'] ?? null,
            ] : null,
        ];

        $messages = $db->table('driver_messages')
            ->where('trip_id', (int) $trip['id'])
            ->orderBy('id', 'ASC')
            ->limit(200)
            ->get()->getResultArray();

        $tripDocs = $db->table('documents')
            ->select('id, document_type, original_file_name, mime_type, file_size, created_at')
            ->where('module_name', 'trip')
            ->where('module_ref_id', (int) $trip['id'])
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        // Mark staff messages as read by the driver
        $db->table('driver_messages')
            ->where('trip_id', (int) $trip['id'])
            ->where('direction', 'staff')
            ->where('is_read_by_driver', 0)
            ->update(['is_read_by_driver' => 1]);

        return $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody(view('driver/track', [
                'trip'     => $trip,
                'route'    => $booking['route_text'] ?? '',
                'token'    => $token,
                'appName'  => env('tpt.appName', 'TPT Aggregator'),
                'contacts' => $contacts,
                'messages' => $messages,
                'tripDocs' => $tripDocs,
            ]));
    }

    /**
     * Serve an uploaded trip document (PDF/image) inline. Token-gated and scoped
     * to the trip the token belongs to, so a token can't fetch unrelated docs.
     */
    public function viewDoc(string $token, int $docId)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setBody('Not found');
        $db  = \Config\Database::connect();
        $doc = $db->table('documents')
            ->where('id', $docId)
            ->where('module_name', 'trip')
            ->where('module_ref_id', (int) $trip['id'])
            ->where('deleted_at', null)
            ->get()->getRowArray();
        if (!$doc) return $this->response->setStatusCode(404)->setBody('Not found');

        $abs = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
        if (!is_file($abs)) return $this->response->setStatusCode(404)->setBody('File missing on disk');

        return $this->response
            ->setHeader('Content-Type', $doc['mime_type'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . ($doc['original_file_name'] ?: basename($abs)) . '"')
            ->setBody(file_get_contents($abs));
    }

    /**
     * Generate and stream a dispatch-pack document (LR / Trip Sheet / Loading
     * Advice / POD-blank / Gate Pass) as a PDF. Delegates to DispatchController
     * after token-validating the trip — same renderer as staff side.
     */
    public function dispatchDoc(string $token, string $kind)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setBody('Not found');
        $allowed = ['lr','trip-sheet','loading-advice','pod-blank','gate-pass'];
        if (!in_array($kind, $allowed, true)) {
            return $this->response->setStatusCode(404)->setBody('Unknown document type');
        }
        $controller = new \App\Controllers\DispatchController();
        $controller->initController(
            \Config\Services::request(),
            \Config\Services::response(),
            \Config\Services::logger()
        );
        return $controller->singlePdf((int) $trip['id'], $kind);
    }

    /** Normalize an Indian phone number to "919876543210". Returns null if unusable. */
    private function normalizePhone(?string $raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        $d = preg_replace('/\D+/', '', $raw);
        if ($d === '' || strlen($d) < 10) return null;
        if (strlen($d) === 10) return '91' . $d;
        if (strlen($d) === 12 && str_starts_with($d, '91')) return $d;
        if (strlen($d) === 11 && str_starts_with($d, '0')) return '91' . substr($d, 1);
        return $d;
    }

    public function manifest(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setBody('not found');
        $appName = env('tpt.appName', 'TPT Tracker');
        $manifest = [
            'name'             => $appName . ' — Driver Tracker',
            'short_name'       => 'Tracker',
            'start_url'        => site_url('d/' . $token),
            'display'          => 'standalone',
            'background_color' => '#0e1218',
            'theme_color'      => '#1d6cb1',
            'orientation'      => 'portrait',
            'icons'            => [],
        ];
        return $this->response
            ->setHeader('Content-Type', 'application/manifest+json')
            ->setBody(json_encode($manifest, JSON_UNESCAPED_SLASHES));
    }

    public function serviceWorker(string $token)
    {
        // Minimal SW: cache the page shell and offline-fallback to a "no internet — pings will resume later"
        $body = <<<'JS'
const CACHE = 'driver-tracker-v1';
self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => { e.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', (e) => {
  // Don't intercept ping POSTs — let them fail offline so the app's
  // localStorage buffer kicks in.
  if (e.request.method !== 'GET') return;
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request).then((m) => m || new Response('offline', {status: 503})))
  );
});
JS;
        return $this->response
            ->setHeader('Content-Type', 'application/javascript')
            ->setHeader('Cache-Control', 'no-cache')
            ->setBody($body);
    }

    /**
     * Accept a single GPS ping from the PWA. Rate-limited at the route layer.
     * Body: { lat, lng, acc, speed?, ts (unix ms or ISO) }
     */
    public function ping(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'reason' => 'invalid_or_closed']);

        $body = json_decode((string) $this->request->getBody(), true) ?? [];
        $lat  = isset($body['lat']) ? (float) $body['lat'] : null;
        $lng  = isset($body['lng']) ? (float) $body['lng'] : null;
        if ($lat === null || $lng === null
            || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'reason' => 'bad_coords']);
        }

        // Optional client-supplied timestamp; fall back to server time
        $ts = $body['ts'] ?? null;
        $tsNorm = null;
        if ($ts) {
            $u = is_numeric($ts) ? (int) ($ts > 1e12 ? $ts / 1000 : $ts) : strtotime((string) $ts);
            $tsNorm = $u ? date('Y-m-d H:i:s', $u) : null;
        }
        $tsNorm = $tsNorm ?: date('Y-m-d H:i:s');

        // Persist
        $reg = strtoupper(preg_replace('/\s+/', '', (string) $trip['vehicle_number']));
        if ($reg === '') $reg = 'PHONE-' . substr($token, 0, 6);

        (new GpsLogModel())->insert([
            'trip_id'        => (int) $trip['id'],
            'vehicle_number' => $reg,
            'latitude'       => round($lat, 7),
            'longitude'      => round($lng, 7),
            'gps_timestamp'  => $tsNorm,
            'speed'          => isset($body['speed']) ? round((float) $body['speed'], 2) : null,
            'address'        => null,
            'raw_payload'    => json_encode($body, JSON_UNESCAPED_SLASHES),
            'source'         => 'driver_phone',
        ]);
        (new LatestVehicleStatusModel())->upsert($reg, [
            'trip_id'       => (int) $trip['id'],
            'latitude'      => round($lat, 7),
            'longitude'     => round($lng, 7),
            'gps_timestamp' => $tsNorm,
            'speed'         => isset($body['speed']) ? round((float) $body['speed'], 2) : null,
            'address'       => null,
            'eta_text'      => null,
            'source'        => 'driver_phone',
        ]);

        // Bump the trip's last-ping marker so GpsRouter can skip polling
        (new TripModel())->update((int) $trip['id'], [
            'driver_track_last_ping' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['ok' => true, 'ts' => $tsNorm]);
    }

    /** Driver opts out of sharing — we keep the last fix but stop honoring the token. */
    public function stop(string $token)
    {
        $trip = $this->trip($token);
        if ($trip) {
            (new TripModel())->update((int) $trip['id'], [
                'driver_track_token'      => null,
                'driver_track_started_at' => null,
            ]);
        }
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Driver-tapped milestone. Maps a friendly stage name to a TripModel
     * status transition (or just a history-log note for "started").
     * Body: { stage }
     *   - started_to_pickup     → log note only
     *   - reached_pickup        → Loading       (sets loading_arrived_at)
     *   - goods_loaded          → In Transit    (sets loading_departed_at + dispatch_datetime)
     *   - reached_destination   → Arrived       (sets unloading_arrived_at)
     *   - unloaded              → Delivered     (sets unloading_departed_at + delivery_datetime)
     */
    public function milestone(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'reason' => 'invalid_or_closed']);

        $body  = json_decode((string) $this->request->getBody(), true) ?? [];
        $stage = (string) ($body['stage'] ?? $this->request->getPost('stage'));

        $map = [
            'started_to_pickup'   => ['status' => null,         'note' => 'Driver started toward pickup'],
            'reached_pickup'      => ['status' => 'Loading',    'note' => 'Driver reached pickup'],
            'goods_loaded'        => ['status' => 'In Transit', 'note' => 'Goods loaded · trip in transit'],
            'reached_destination' => ['status' => 'Arrived',    'note' => 'Driver reached destination'],
            'unloaded'            => ['status' => 'Delivered',  'note' => 'Unloaded at destination'],
        ];
        if (!isset($map[$stage])) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'reason' => 'unknown_stage']);
        }

        $now      = date('Y-m-d H:i:s');
        $tripId   = (int) $trip['id'];
        $current  = (string) $trip['current_status'];
        $target   = $map[$stage]['status'];
        $note     = $map[$stage]['note'];

        // No-op transitions are still acknowledged (button is idempotent if tapped twice)
        if ($target === null) {
            (new TripStatusHistoryModel())->log($tripId, $current, $current, $note, null);
            return $this->response->setJSON(['ok' => true, 'status' => $current, 'noted' => true]);
        }

        if ($current === $target) {
            return $this->response->setJSON(['ok' => true, 'status' => $current, 'already' => true]);
        }

        // Forward-only guard: a driver who has already crossed a milestone must
        // not be able to push the trip back to an earlier state by tapping a
        // completed step. Cancellation is a separate flow and not reachable
        // through this endpoint, so the linear rank order is safe.
        $rank = [
            'Booking Created' => 0, 'Vehicle Placed' => 1, 'Loading'      => 2,
            'In Transit'      => 3, 'Arrived'        => 4, 'Unloading'    => 5,
            'Delivered'       => 6, 'POD Received'   => 7, 'Closed'       => 8,
        ];
        $currentRank = $rank[$current] ?? 0;
        $targetRank  = $rank[$target]  ?? 0;
        if ($targetRank < $currentRank) {
            return $this->response->setJSON([
                'ok'      => false,
                'reason'  => 'backward_blocked',
                'status'  => $current,
                'message' => 'This milestone has already been completed. Status cannot move backwards.',
            ]);
        }

        $update = ['current_status' => $target];
        if ($target === 'Loading'    && empty($trip['loading_arrived_at']))    $update['loading_arrived_at']    = $now;
        if ($target === 'In Transit' && empty($trip['loading_departed_at']))   $update['loading_departed_at']   = $now;
        if ($target === 'Arrived'    && empty($trip['unloading_arrived_at']))  $update['unloading_arrived_at']  = $now;
        if ($target === 'Delivered'  && empty($trip['unloading_departed_at'])) $update['unloading_departed_at'] = $now;
        if ($target === 'In Transit' && empty($trip['dispatch_datetime']))     $update['dispatch_datetime']     = $now;
        if ($target === 'Delivered'  && empty($trip['delivery_datetime']))     $update['delivery_datetime']     = $now;

        (new TripModel())->update($tripId, $update);
        (new TripStatusHistoryModel())->log($tripId, $current, $target, $note, null);

        // Fire workflow notifications (client emails for In Transit / Arrived / Delivered)
        try {
            (new \App\Libraries\NotificationService())->onTripStatusChanged($tripId, $target);
        } catch (\Throwable $e) {
            log_message('warning', 'NotificationService::onTripStatusChanged (driver) failed: ' . $e->getMessage());
        }

        return $this->response->setJSON(['ok' => true, 'status' => $target]);
    }

    /**
     * Driver uploads a POD photo from the phone. Multipart: pod_file.
     * Sets pod_status=Received, transitions status to "POD Received" if not already past.
     */
    public function pod(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'reason' => 'invalid_or_closed']);

        $file = $this->request->getFile('pod_file');
        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'reason' => 'no_file']);
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->response->setStatusCode(413)->setJSON(['ok' => false, 'reason' => 'too_large']);
        }
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic'];
        $ext     = strtolower($file->getExtension() ?: $file->guessExtension() ?: '');
        if (!in_array($ext, $allowed, true)) {
            return $this->response->setStatusCode(415)->setJSON(['ok' => false, 'reason' => 'bad_type']);
        }

        $tripId       = (int) $trip['id'];
        $originalName = $file->getClientName();
        $fileSize     = (int) $file->getSize();
        $mimeType     = $file->getMimeType() ?: $file->getClientMimeType();

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'trips' . DIRECTORY_SEPARATOR . $tripId;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $stored = $file->getRandomName();
        $file->move($dir, $stored);

        (new DocumentModel())->insert([
            'module_name'         => 'trip',
            'module_ref_id'       => $tripId,
            'document_type'       => 'POD',
            'original_file_name'  => $originalName,
            'stored_file_name'    => $stored,
            'file_path'           => 'trips/' . $tripId . '/' . $stored,
            'file_size'           => $fileSize,
            'mime_type'           => $mimeType,
            'source_channel'      => 'driver_phone',
            'verification_status' => 'Pending',
            'uploaded_by'         => null,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        (new TripModel())->update($tripId, [
            'pod_status'      => 'Received',
            'pod_received_at' => date('Y-m-d H:i:s'),
        ]);

        $current = (new TripModel())->find($tripId);
        if ($current && !in_array($current['current_status'], ['POD Received', 'Closed'], true)) {
            (new TripModel())->update($tripId, ['current_status' => 'POD Received']);
            (new TripStatusHistoryModel())->log($tripId, (string) $current['current_status'], 'POD Received', 'POD uploaded by driver', null);
        }

        return $this->response->setJSON(['ok' => true, 'status' => 'POD Received']);
    }

    /**
     * Return the driver↔staff message thread for this trip.
     * GET /d/<token>/messages?since=<id>
     * Driver-side: also marks any newer staff messages as read by the driver.
     */
    public function messages(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'reason' => 'invalid_or_closed']);

        $since = (int) $this->request->getGet('since');
        $db    = \Config\Database::connect();
        $q = $db->table('driver_messages')->where('trip_id', (int) $trip['id']);
        if ($since > 0) $q = $q->where('id >', $since);
        $rows = $q->orderBy('id', 'ASC')->limit(200)->get()->getResultArray();

        if (!empty($rows)) {
            $db->table('driver_messages')
                ->where('trip_id', (int) $trip['id'])
                ->where('direction', 'staff')
                ->where('is_read_by_driver', 0)
                ->update(['is_read_by_driver' => 1]);
        }

        return $this->response->setJSON(['ok' => true, 'messages' => $rows]);
    }

    /**
     * Driver posts a message to the dispatch team. Multipart: optional `attachment`,
     * plus `body` (text). At least one must be present.
     */
    public function sendMessage(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'reason' => 'invalid_or_closed']);

        $body = trim((string) $this->request->getPost('body'));
        $file = $this->request->getFile('attachment');
        if ($body === '' && (!$file || !$file->isValid())) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'reason' => 'empty']);
        }
        if (mb_strlen($body) > 4000) $body = mb_substr($body, 0, 4000);

        $tripId = (int) $trip['id'];
        $attachmentPath = null;
        $attachmentMime = null;
        $attachmentOrig = null;

        if ($file && $file->isValid()) {
            if ($file->getSize() > 15 * 1024 * 1024) {
                return $this->response->setStatusCode(413)->setJSON(['ok' => false, 'reason' => 'too_large']);
            }
            $allowed = ['pdf','jpg','jpeg','png','webp','heic','heif','mp4','3gp','m4a','mp3','wav','ogg'];
            $ext = strtolower($file->getExtension() ?: $file->guessExtension() ?: '');
            if (!in_array($ext, $allowed, true)) {
                return $this->response->setStatusCode(415)->setJSON(['ok' => false, 'reason' => 'bad_type']);
            }
            $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'trips' . DIRECTORY_SEPARATOR . $tripId . DIRECTORY_SEPARATOR . 'messages';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $stored = $file->getRandomName();
            $attachmentOrig = $file->getClientName();
            $attachmentMime = $file->getMimeType() ?: $file->getClientMimeType();
            $file->move($dir, $stored);
            $attachmentPath = 'trips/' . $tripId . '/messages/' . $stored;
        }

        $db = \Config\Database::connect();
        $db->table('driver_messages')->insert([
            'trip_id'           => $tripId,
            'direction'         => 'driver',
            'user_id'           => null,
            'body'              => $body !== '' ? $body : null,
            'attachment_path'   => $attachmentPath,
            'attachment_mime'   => $attachmentMime,
            'attachment_orig'   => $attachmentOrig,
            'is_read_by_staff'  => 0,
            'is_read_by_driver' => 1,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        $row = $db->table('driver_messages')->where('id', $id)->get()->getRowArray();

        return $this->response->setJSON(['ok' => true, 'message' => $row]);
    }

    /**
     * Serve a driver-message attachment. Token-gated; only files inside this
     * trip's `messages` directory are accessible.
     */
    public function attachment(string $token, int $id)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setBody('Not found');
        $db  = \Config\Database::connect();
        $msg = $db->table('driver_messages')->where('id', $id)->where('trip_id', (int) $trip['id'])->get()->getRowArray();
        if (!$msg || empty($msg['attachment_path'])) return $this->response->setStatusCode(404)->setBody('Not found');

        $abs = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $msg['attachment_path']);
        if (!is_file($abs)) return $this->response->setStatusCode(404)->setBody('Not found');
        return $this->response
            ->setHeader('Content-Type', $msg['attachment_mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . ($msg['attachment_orig'] ?: basename($abs)) . '"')
            ->setBody(file_get_contents($abs));
    }
}

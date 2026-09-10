<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Public, token-gated read-only live tracking page for clients.
 *
 *   GET /track/{token}        → HTML page with Leaflet map + status timeline
 *   GET /track/{token}/data   → JSON with current position + recent path
 *
 * Tokens are generated when a trip is dispatched (see TripsController::startDriverTrack)
 * or on demand from the trip detail page. Token has an expiry — defaults to 14 days.
 */
class ClientTrackingController extends Controller
{
    protected $helpers = ['url'];

    /** Resolve token → trip context, or null. */
    private function context(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $db = \Config\Database::connect();
        $trip = $db->table('trips t')
            ->select('t.id, t.trip_no, t.lr_no, t.current_status, t.driver_name, t.vehicle_number,
                      t.dispatch_datetime, t.delivery_datetime, t.client_track_expires_at,
                      b.booking_no, b.route_text, b.client_id')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('t.client_track_token', $token)
            ->where('t.deleted_at', null)
            ->get()->getRowArray();
        if (!$trip) return null;
        if (!empty($trip['client_track_expires_at']) && strtotime($trip['client_track_expires_at']) < time()) return null;
        return $trip;
    }

    public function index(string $token)
    {
        $trip = $this->context($token);
        if (!$trip) return $this->response->setStatusCode(404)->setBody(view('client_tracking/invalid'));

        $db = \Config\Database::connect();
        $client = $trip['client_id']
            ? $db->table('clients')->where('id', (int) $trip['client_id'])->get()->getRowArray()
            : null;
        $latest = $db->table('latest_vehicle_status')->where('trip_id', (int) $trip['id'])->get()->getRowArray();
        $history = $db->table('trip_status_history')
            ->where('trip_id', (int) $trip['id'])
            ->orderBy('changed_at', 'ASC')
            ->get()->getResultArray();

        return view('client_tracking/track', [
            'token'   => $token,
            'trip'    => $trip,
            'client'  => $client,
            'latest'  => $latest,
            'history' => $history,
            'company' => (new \App\Models\SettingModel())->get('company_name', 'TPT Logistics') ?: 'TPT Logistics',
        ]);
    }

    /** JSON refresh endpoint — polled by the live map every 30s. */
    public function data(string $token)
    {
        $trip = $this->context($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);

        $db = \Config\Database::connect();
        $latest = $db->table('latest_vehicle_status')->where('trip_id', (int) $trip['id'])->get()->getRowArray();
        // Last 200 GPS pings — enough for a coherent polyline without huge payloads
        $pings = $db->table('gps_logs')
            ->select('latitude, longitude, gps_timestamp, speed')
            ->where('trip_id', (int) $trip['id'])
            ->orderBy('id', 'DESC')->limit(200)
            ->get()->getResultArray();
        $pings = array_reverse($pings); // chronological order for polyline

        return $this->response->setJSON([
            'ok'     => true,
            'status' => $trip['current_status'],
            'latest' => $latest ? [
                'lat'      => (float) $latest['latitude'],
                'lng'      => (float) $latest['longitude'],
                'ts'       => $latest['gps_timestamp'],
                'speed'    => $latest['speed'] !== null ? (float) $latest['speed'] : null,
                'address'  => $latest['address'] ?? null,
                'eta_text' => $latest['eta_text'] ?? null,
                'source'   => $latest['source'] ?? null,
            ] : null,
            'pings'  => array_map(fn ($p) => [
                'lat' => (float) $p['latitude'],
                'lng' => (float) $p['longitude'],
                'ts'  => $p['gps_timestamp'],
            ], $pings),
        ]);
    }
}

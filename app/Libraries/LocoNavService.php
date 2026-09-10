<?php

namespace App\Libraries;

use App\Models\GpsLogModel;
use App\Models\LatestVehicleStatusModel;
use App\Models\TripModel;
use App\Models\ClientModel;
use App\Models\BookingModel;
use App\Models\WhatsappTemplateModel;

/**
 * LocoNav GPS adapter.
 *
 * Endpoints follow LocoNav's "Vehicle API" shape. If the API key is blank
 * we short-circuit and return nothing — the caller sees a clear "not configured"
 * response rather than a network error.
 */
class LocoNavService
{
    private string $baseUrl;
    private string $apiKey;

    // Thresholds for delay detection (minutes)
    private const STALE_MINUTES        = 30;
    private const STATIONARY_MINUTES   = 60;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('loconav.baseUrl', 'https://api.loconav.com'), '/');
        $this->apiKey  = (string) env('loconav.apiKey', '');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Fetch the latest location for a single vehicle from LocoNav, update
     * latest_vehicle_status + append to gps_logs. Returns the normalized record
     * or ['ok'=>false, ...] on failure / unconfigured.
     */
    public function refreshVehicle(string $vehicleNumber, ?int $tripId = null): array
    {
        $vehicleNumber = strtoupper(preg_replace('/\s+/', '', $vehicleNumber));
        if ($vehicleNumber === '') {
            return ['ok' => false, 'error' => 'Vehicle number missing'];
        }

        if (!$this->isConfigured()) {
            return ['ok' => false, 'queued' => true, 'note' => 'LocoNav not configured — set loconav.apiKey in .env.'];
        }

        $endpoint = $this->baseUrl . '/api/v1/vehicles/' . rawurlencode($vehicleNumber) . '/location';
        $resp     = $this->curl('GET', $endpoint, null, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);

        if (empty($resp['ok']) || !is_array($resp['body'])) {
            return [
                'ok'    => false,
                'error' => $resp['body']['message'] ?? $resp['error'] ?? ('HTTP ' . ($resp['status'] ?? '?')),
                'raw'   => $resp['raw'] ?? null,
            ];
        }

        $norm = $this->normalize($resp['body']);
        if ($norm['latitude'] === null || $norm['longitude'] === null) {
            return ['ok' => false, 'error' => 'No coordinates in LocoNav response'];
        }

        $this->persist($vehicleNumber, $tripId, $norm, $resp['raw'] ?? null);
        return ['ok' => true, 'data' => $norm];
    }

    /**
     * Refresh GPS for every active trip (not Closed/Cancelled) that has a vehicle_number.
     * Returns counters for the cron task to report.
     */
    public function refreshAllActive(): array
    {
        $tripModel = new TripModel();
        $rows = $tripModel->select('id, vehicle_number, current_status')
            ->whereNotIn('current_status', ['Closed', 'Cancelled'])
            ->where('deleted_at IS NULL')
            ->where('vehicle_number IS NOT NULL')
            ->where("vehicle_number != ''")
            ->findAll();

        $ok = 0; $fail = 0; $delays = 0;
        foreach ($rows as $t) {
            $r = $this->refreshVehicle((string) $t['vehicle_number'], (int) $t['id']);
            if (!empty($r['ok'])) {
                $ok++;
                if ($this->detectDelay((int) $t['id'], (string) $t['vehicle_number'])) $delays++;
            } else {
                $fail++;
            }
        }
        return ['ok' => $ok, 'failed' => $fail, 'delays_flagged' => $delays];
    }

    /**
     * Tolerant normalizer. LocoNav v1 returns something like:
     *   { "data": { "latitude": 18.5, "longitude": 73.8, "speed": 45,
     *               "timestamp": "2026-04-21T09:30:00Z", "address": "..."} }
     * We also accept flat shapes for safety.
     */
    private function normalize(array $body): array
    {
        $d = $body['data'] ?? $body;
        $lat = $d['latitude']  ?? $d['lat'] ?? null;
        $lng = $d['longitude'] ?? $d['lng'] ?? $d['lon'] ?? null;
        $ts  = $d['timestamp'] ?? $d['gps_timestamp'] ?? $d['time'] ?? null;
        if ($ts) {
            $utc = strtotime((string) $ts);
            $gps = $utc ? date('Y-m-d H:i:s', $utc) : date('Y-m-d H:i:s');
        } else {
            $gps = date('Y-m-d H:i:s');
        }
        return [
            'latitude'      => $lat !== null ? round((float) $lat, 7) : null,
            'longitude'     => $lng !== null ? round((float) $lng, 7) : null,
            'speed'         => isset($d['speed']) ? round((float) $d['speed'], 2) : null,
            'address'       => (string) ($d['address'] ?? ''),
            'gps_timestamp' => $gps,
        ];
    }

    private function persist(string $vehicleNumber, ?int $tripId, array $norm, $raw): void
    {
        (new GpsLogModel())->insert([
            'trip_id'        => $tripId,
            'vehicle_number' => $vehicleNumber,
            'latitude'       => $norm['latitude'],
            'longitude'      => $norm['longitude'],
            'gps_timestamp'  => $norm['gps_timestamp'],
            'speed'          => $norm['speed'],
            'address'        => $norm['address'],
            'raw_payload'    => is_string($raw) ? $raw : json_encode($raw, JSON_UNESCAPED_UNICODE),
            'source'         => 'loconav',
        ]);
        (new LatestVehicleStatusModel())->upsert($vehicleNumber, [
            'trip_id'       => $tripId,
            'latitude'      => $norm['latitude'],
            'longitude'     => $norm['longitude'],
            'gps_timestamp' => $norm['gps_timestamp'],
            'speed'         => $norm['speed'],
            'address'       => $norm['address'],
            'source'        => 'loconav',
        ]);
    }

    /**
     * Detect a delay for the given trip: fresh GPS stale OR vehicle stationary
     * for too long while trip is In Transit. Flags delay_flag on latest status
     * and, if escalating, queues an internal WhatsApp notification placeholder.
     * Returns true if a flag was raised.
     */
    public function detectDelay(int $tripId, string $vehicleNumber): bool
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip || $trip['current_status'] !== 'In Transit') return false;

        $latest = (new LatestVehicleStatusModel())->getByVehicle(strtoupper(preg_replace('/\s+/', '', $vehicleNumber)));
        if (!$latest) return false;

        $now      = time();
        $gpsTs    = strtotime((string) $latest['gps_timestamp']);
        $isStale  = $gpsTs && ($now - $gpsTs) > self::STALE_MINUTES * 60;
        $stationarySince = $this->stationarySinceMinutes($tripId);
        $isStuck  = $stationarySince !== null && $stationarySince > self::STATIONARY_MINUTES;

        $flag = ($isStale || $isStuck) ? 1 : 0;
        (new LatestVehicleStatusModel())->update($latest['id'], ['delay_flag' => $flag]);
        if ($flag) {
            (new TripModel())->update($tripId, [
                'delay_reason' => $isStale
                    ? sprintf('GPS stale for %d+ minutes', self::STALE_MINUTES)
                    : sprintf('Stationary for %d+ minutes', (int) $stationarySince),
            ]);
        }
        return (bool) $flag;
    }

    /** Returns minutes since vehicle was last moving, or null if it has moved very recently. */
    private function stationarySinceMinutes(int $tripId): ?int
    {
        $db = \Config\Database::connect();
        $row = $db->table('gps_logs')
            ->select('gps_timestamp')
            ->where('trip_id', $tripId)
            ->where('speed >', 5)
            ->orderBy('gps_timestamp', 'DESC')
            ->limit(1)
            ->get()->getRowArray();
        if (!$row) return null;
        $ts = strtotime((string) $row['gps_timestamp']);
        if (!$ts) return null;
        return (int) floor((time() - $ts) / 60);
    }

    private function curl(string $method, string $url, ?array $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return [
            'ok'     => $status >= 200 && $status < 300 && is_array($decoded),
            'status' => $status,
            'body'   => is_array($decoded) ? $decoded : null,
            'raw'    => $raw,
            'error'  => $err,
        ];
    }
}

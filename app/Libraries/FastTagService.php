<?php

namespace App\Libraries;

use App\Models\GpsLogModel;
use App\Models\LatestVehicleStatusModel;
use App\Models\SettingModel;
use App\Models\TripModel;

/**
 * Provider-agnostic FastTag tracking client. Indian commercial trucks all carry
 * a FastTag, and providers like Vamosys / Roadcast / GPSGate / Sastra resell
 * those toll-plaza pings as an HTTP API.
 *
 * Settings (group=gps):
 *   fasttag_provider  — vamosys | roadcast | generic
 *   fasttag_endpoint  — base URL of the provider's track endpoint
 *   fasttag_api_key   — API key / bearer token (encrypted at rest)
 *
 * The expected response shape (we accept the common variants):
 *   { latitude, longitude, timestamp, address, plaza, speed, vehicle_number }
 *
 * If a provider returns nothing (no recent toll plaza pass), refreshVehicle
 * returns ['ok' => false, 'reason' => 'no_recent_ping'] and the caller can
 * try the next source.
 */
class FastTagService
{
    public function isConfigured(): bool
    {
        return $this->setting('fasttag_endpoint') !== '' && $this->setting('fasttag_api_key') !== '';
    }

    private function setting(string $k, string $default = ''): string
    {
        return (string) ((new SettingModel())->get($k, $default) ?? $default);
    }

    /** Fetch the latest plaza ping for a single vehicle. */
    public function refreshVehicle(string $vehicleNumber, ?int $tripId = null): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not_configured'];
        }
        $reg = strtoupper(preg_replace('/\s+/', '', $vehicleNumber));
        if ($reg === '') return ['ok' => false, 'reason' => 'no_vehicle'];

        $endpoint = rtrim($this->setting('fasttag_endpoint'), '/');
        $url      = $endpoint . '/track?vehicle=' . urlencode($reg);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->setting('fasttag_api_key'),
                'X-Api-Key: ' . $this->setting('fasttag_api_key'),
            ],
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) return ['ok' => false, 'reason' => 'curl_error', 'error' => $err];
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'reason' => 'http_' . $code, 'error' => substr((string) $res, 0, 400)];
        }

        $body = json_decode((string) $res, true);
        if (!is_array($body)) return ['ok' => false, 'reason' => 'bad_json'];
        $norm = $this->normalize($body);
        if (!$norm['lat'] || !$norm['lng']) return ['ok' => false, 'reason' => 'no_recent_ping'];

        // Persist
        $now = date('Y-m-d H:i:s');
        (new GpsLogModel())->insert([
            'trip_id'       => $tripId,
            'vehicle_number'=> $reg,
            'latitude'      => $norm['lat'],
            'longitude'     => $norm['lng'],
            'gps_timestamp' => $norm['ts'] ?? $now,
            'speed'         => $norm['speed'] ?? null,
            'address'       => $norm['address'] ?? null,
            'raw_payload'   => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'source'        => 'fasttag',
        ]);
        (new LatestVehicleStatusModel())->upsert($reg, [
            'trip_id'       => $tripId,
            'latitude'      => $norm['lat'],
            'longitude'     => $norm['lng'],
            'gps_timestamp' => $norm['ts'] ?? $now,
            'speed'         => $norm['speed'] ?? null,
            'address'       => $norm['address'] ?? null,
            'eta_text'      => null,
            'source'        => 'fasttag',
        ]);

        return ['ok' => true, 'data' => $norm];
    }

    /** Refresh every active trip with a vehicle_number. */
    public function refreshAllActive(): array
    {
        $tripModel = new TripModel();
        $rows = $tripModel->select('id, vehicle_number')
            ->whereNotIn('current_status', ['Closed','Cancelled'])
            ->where('deleted_at IS NULL')
            ->where('vehicle_number IS NOT NULL')
            ->where("vehicle_number != ''")
            ->findAll();

        $ok = 0; $skipped = 0;
        foreach ($rows as $t) {
            $r = $this->refreshVehicle((string) $t['vehicle_number'], (int) $t['id']);
            if (!empty($r['ok'])) $ok++; else $skipped++;
        }
        return ['ok' => $ok, 'skipped' => $skipped];
    }

    /** Tolerant normalizer for the 3 common provider response shapes. */
    private function normalize(array $body): array
    {
        $d = $body['data'] ?? $body['result'] ?? $body;
        $lat = $d['latitude']  ?? $d['lat']  ?? $d['Latitude']  ?? null;
        $lng = $d['longitude'] ?? $d['lng']  ?? $d['lon'] ?? $d['Longitude'] ?? null;
        $ts  = $d['timestamp'] ?? $d['gps_timestamp'] ?? $d['time'] ?? $d['plazaTime'] ?? null;
        $sp  = $d['speed']     ?? null;
        $addr= $d['address']   ?? $d['location'] ?? $d['plaza'] ?? null;

        $tsNorm = null;
        if ($ts) {
            $u = is_numeric($ts) ? (int) $ts : strtotime((string) $ts);
            $tsNorm = $u ? date('Y-m-d H:i:s', $u) : null;
        }
        return [
            'lat'    => $lat !== null ? round((float) $lat, 7) : null,
            'lng'    => $lng !== null ? round((float) $lng, 7) : null,
            'ts'     => $tsNorm,
            'speed'  => $sp !== null ? round((float) $sp, 2) : null,
            'address'=> $addr ? substr((string) $addr, 0, 400) : null,
        ];
    }
}

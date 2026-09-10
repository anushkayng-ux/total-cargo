<?php

namespace App\Libraries;

use App\Models\TripModel;
use App\Models\BookingModel;
use App\Models\ClientModel;
use App\Models\SettingModel;

/**
 * Indian E-Way Bill (EWB) adapter.
 *
 * EWB must be generated on the NIC portal (ewaybillgst.gov.in). Direct API access
 * is only for enrolled GSPs; in practice Indian transporters use a GSP such as
 * ClearTax, Masters India, GST-Hero, etc. This adapter targets the ClearTax GSP
 * contract which is the same one used by TPT's e-invoice adapter, so credentials
 * are shared (`cleartax.baseUrl`, `cleartax.apiKey`, `cleartax.gstin`) with an
 * optional GSP username/password for password-auth variants (`ewb.gspUserName`,
 * `ewb.gspPassword`).
 *
 * Behaviour:
 *   - When credentials are blank: the request payload is still built and logged
 *     to `ewb_logs` with status `Queued`. No network call is made. The ops team
 *     can paste an EWB generated on the NIC portal manually.
 *   - When credentials are present: calls `/ewayapi/v1.03/ewayapi` (generate),
 *     `/ewayapi/v1.03/ewayapi?action=GetEwayBill` (fetch) and cancel.
 *   - On success, writes `ewb_no`, `ewb_date`, `ewb_valid_until`, `ewb_status` onto trips.
 *
 * Reference: https://docs.ewaybillgst.gov.in/
 *            https://cleartax.in/s/eway-bill-api
 */
class EwayBillService
{
    private string $baseUrl;
    private string $apiKey;
    private string $gstin;
    private string $gspUser;
    private string $gspPass;

    public function __construct()
    {
        $this->baseUrl  = rtrim((string) env('cleartax.baseUrl', ''), '/');
        $this->apiKey   = (string) env('cleartax.apiKey', '');
        $this->gstin    = (string) env('cleartax.gstin', '');
        $this->gspUser  = (string) env('ewb.gspUserName', '');
        $this->gspPass  = (string) env('ewb.gspPassword', '');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->gstin !== '';
    }

    public function generate(int $tripId, array $extra = []): array
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return ['ok' => false, 'error' => 'Trip not found'];
        if (!empty($trip['ewb_no'])) {
            return ['ok' => false, 'error' => 'EWB already exists: ' . $trip['ewb_no']];
        }

        $booking = !empty($trip['booking_id']) ? (new BookingModel())->find((int) $trip['booking_id']) : [];
        $client  = !empty($booking['client_id']) ? (new ClientModel())->find((int) $booking['client_id']) : [];
        $company = (new SettingModel())->getAllGrouped()['company'] ?? [];

        $payload = $this->buildGeneratePayload($trip, $booking ?: [], $client ?: [], $company, $extra);

        $logId = $this->writeLog($tripId, 'generate', $payload, null, 'Queued');

        if (!$this->isConfigured()) {
            return ['ok' => false, 'queued' => true, 'log_id' => $logId,
                    'note' => 'ClearTax credentials not set — payload queued. Generate on NIC portal and enter EWB number manually.'];
        }

        $endpoint = $this->baseUrl . '/ewayapi/v1.03/ewayapi';
        $resp     = $this->curl('POST', $endpoint, $payload, $this->authHeaders());

        $body     = is_array($resp['body']) ? $resp['body'] : [];
        $ewbNo    = $body['data']['ewayBillNo']      ?? $body['ewayBillNo']      ?? null;
        $ewbDate  = $body['data']['ewayBillDate']    ?? $body['ewayBillDate']    ?? null;
        $validUpto= $body['data']['validUpto']       ?? $body['validUpto']       ?? null;

        $ok = $resp['ok'] && $ewbNo;
        $this->updateLog($logId, $body, $ok ? 'Success' : 'Failed', $ok ? null : ($body['message'] ?? $resp['error'] ?? ('HTTP ' . $resp['status'])));

        if ($ok) {
            (new TripModel())->update($tripId, [
                'ewb_no'          => (string) $ewbNo,
                'ewb_date'        => $ewbDate   ? date('Y-m-d H:i:s', strtotime((string) $ewbDate))    : date('Y-m-d H:i:s'),
                'ewb_valid_until' => $validUpto ? date('Y-m-d H:i:s', strtotime((string) $validUpto))  : null,
                'ewb_status'      => 'Active',
            ]);
        }

        return ['ok' => (bool) $ok, 'log_id' => $logId, 'ewb_no' => $ewbNo, 'valid_until' => $validUpto,
                'error' => $ok ? null : ($body['message'] ?? $resp['error'] ?? ('HTTP ' . $resp['status']))];
    }

    public function fetch(int $tripId): array
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip || empty($trip['ewb_no'])) return ['ok' => false, 'error' => 'No EWB number on trip'];

        $logId = $this->writeLog($tripId, 'fetch', ['ewbNo' => $trip['ewb_no']], null, 'Queued');
        if (!$this->isConfigured()) {
            return ['ok' => false, 'queued' => true, 'log_id' => $logId];
        }

        $endpoint = $this->baseUrl . '/ewayapi/v1.03/ewayapi?action=GetEwayBill&ewbNo=' . urlencode((string) $trip['ewb_no']);
        $resp     = $this->curl('GET', $endpoint, null, $this->authHeaders());

        $ok   = $resp['ok'];
        $body = is_array($resp['body']) ? $resp['body'] : [];
        $this->updateLog($logId, $body, $ok ? 'Success' : 'Failed', $ok ? null : ($body['message'] ?? $resp['error'] ?? ''));

        if ($ok) {
            $validUpto = $body['data']['validUpto'] ?? $body['validUpto'] ?? null;
            if ($validUpto) {
                (new TripModel())->update($tripId, [
                    'ewb_valid_until' => date('Y-m-d H:i:s', strtotime((string) $validUpto)),
                    'ewb_status'      => 'Active',
                ]);
            }
        }
        return ['ok' => $ok, 'body' => $body, 'log_id' => $logId];
    }

    public function cancel(int $tripId, string $reason = 'Duplicate'): array
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip || empty($trip['ewb_no'])) return ['ok' => false, 'error' => 'No EWB on trip'];

        $payload = ['ewbNo' => $trip['ewb_no'], 'cancelRsnCode' => 2, 'cancelRmrk' => $reason];
        $logId   = $this->writeLog($tripId, 'cancel', $payload, null, 'Queued');

        if (!$this->isConfigured()) {
            (new TripModel())->update($tripId, ['ewb_status' => 'Cancelled']);
            return ['ok' => true, 'queued' => true, 'log_id' => $logId, 'note' => 'Marked cancelled locally — also cancel on NIC portal.'];
        }

        $resp = $this->curl('POST', $this->baseUrl . '/ewayapi/v1.03/ewayapi?action=CanEWB', $payload, $this->authHeaders());
        $ok = $resp['ok'];
        $this->updateLog($logId, $resp['body'] ?: [], $ok ? 'Success' : 'Failed', $ok ? null : ($resp['body']['message'] ?? $resp['error'] ?? ''));
        if ($ok) {
            (new TripModel())->update($tripId, ['ewb_status' => 'Cancelled']);
        }
        return ['ok' => $ok, 'log_id' => $logId];
    }

    /**
     * Update Part-B vehicle details (vehicle change mid-transit).
     * NIC requires this within 24 hrs of vehicle change or EWB is invalid at checkpost.
     */
    public function updatePartB(int $tripId, string $newVehicleNo, string $reason = 'Vehicle change', ?string $reasonRemark = null): array
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip || empty($trip['ewb_no'])) return ['ok' => false, 'error' => 'No EWB on trip'];

        $payload = [
            'ewbNo'     => (int) $trip['ewb_no'],
            'vehicleNo' => strtoupper(preg_replace('/\s+/', '', $newVehicleNo)),
            'fromPlace' => $trip['loading_point'] ?? '',
            'fromState' => '',
            'reasonCode'=> 1, // 1=Due to Break Down, 2=Transhipment, 3=Others, 4=First Time
            'reasonRem' => $reasonRemark ?: $reason,
            'transDocNo'=> $trip['lr_no'] ?? '',
            'transDocDate' => date('d/m/Y', strtotime($trip['lr_generated_at'] ?? 'now')),
            'transMode' => '1', // 1 = Road
        ];
        $logId = $this->writeLog($tripId, 'partb_update', $payload, null, 'Queued');

        if (!$this->isConfigured()) {
            (new TripModel())->update($tripId, ['vehicle_number' => $newVehicleNo]);
            return ['ok' => true, 'queued' => true, 'log_id' => $logId,
                    'note' => 'Vehicle updated locally — also update Part-B on NIC portal within 24 hours.'];
        }

        $resp = $this->curl('POST', $this->baseUrl . '/ewayapi/v1.03/ewayapi?action=VEHEWB', $payload, $this->authHeaders());
        $ok = $resp['ok'];
        $this->updateLog($logId, $resp['body'] ?: [], $ok ? 'Success' : 'Failed', $ok ? null : ($resp['body']['message'] ?? $resp['error'] ?? ''));
        if ($ok) {
            (new TripModel())->update($tripId, ['vehicle_number' => $newVehicleNo]);
        }
        return ['ok' => $ok, 'log_id' => $logId];
    }

    /**
     * Extend EWB validity. Allowed within 8 hrs before / after expiry.
     * Reason codes: 1 Natural Calamity, 2 Law and Order, 3 Transhipment, 4 Accident, 99 Others
     */
    public function extendValidity(int $tripId, int $reasonCode = 99, string $reasonRemark = 'Extended due to delay', ?string $remainingDistance = null, ?string $newCity = null): array
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip || empty($trip['ewb_no'])) return ['ok' => false, 'error' => 'No EWB on trip'];

        $payload = [
            'ewbNo'             => (int) $trip['ewb_no'],
            'vehicleNo'         => strtoupper(preg_replace('/\s+/', '', $trip['vehicle_number'] ?? '')),
            'fromPlace'         => $newCity ?? ($trip['loading_point'] ?? ''),
            'remainingDistance' => $remainingDistance ?: '',
            'transMode'         => '1',
            'extnRsnCode'       => $reasonCode,
            'extnRemarks'       => $reasonRemark,
            'consignmentStatus' => 'M', // M=Movement, T=Transit
        ];
        $logId = $this->writeLog($tripId, 'extend_validity', $payload, null, 'Queued');

        if (!$this->isConfigured()) {
            return ['ok' => true, 'queued' => true, 'log_id' => $logId,
                    'note' => 'Logged locally — also extend validity on NIC portal within 8 hours of expiry.'];
        }

        $resp = $this->curl('POST', $this->baseUrl . '/ewayapi/v1.03/ewayapi?action=ExtendValidity', $payload, $this->authHeaders());
        $ok = $resp['ok'];
        $this->updateLog($logId, $resp['body'] ?: [], $ok ? 'Success' : 'Failed', $ok ? null : ($resp['body']['message'] ?? $resp['error'] ?? ''));
        if ($ok) {
            $newValid = $resp['body']['validUpto'] ?? null;
            if ($newValid) {
                (new TripModel())->update($tripId, [
                    'ewb_valid_until' => date('Y-m-d H:i:s', strtotime((string) $newValid)),
                    'ewb_status'      => 'Active',
                ]);
            }
        }
        return ['ok' => $ok, 'log_id' => $logId, 'new_valid_until' => $resp['body']['validUpto'] ?? null];
    }

    /**
     * Generate a consolidated EWB across multiple individual EWBs travelling on
     * the same vehicle. Used for full-truck-load aggregation across LRs.
     */
    public function generateConsolidated(array $tripIds, string $vehicleNo, string $fromPlace, string $fromState, ?int $userId = null): array
    {
        $tripModel = new TripModel();
        $trips = $tripModel->whereIn('id', $tripIds)->where('deleted_at', null)->find();
        $ewbs  = [];
        foreach ($trips as $t) {
            if (empty($t['ewb_no'])) continue;
            $ewbs[] = ['ewbNo' => (int) $t['ewb_no']];
        }
        if (count($ewbs) < 2) return ['ok' => false, 'error' => 'Need at least 2 trips with EWBs to consolidate'];

        $payload = [
            'fromPlace' => $fromPlace,
            'fromState' => $fromState,
            'vehicleNo' => strtoupper(preg_replace('/\s+/', '', $vehicleNo)),
            'transMode' => '1',
            'tripsheetEwbBills' => $ewbs,
        ];
        $logId = $this->writeLog(0, 'consolidate', $payload, null, 'Queued');

        $now = date('Y-m-d H:i:s');
        $consolModel = new \App\Models\ConsolidatedEwbModel();

        if (!$this->isConfigured()) {
            $localNo = 'CONSOL-' . strtoupper(bin2hex(random_bytes(4)));
            $id = $consolModel->insert([
                'consol_no'     => $localNo,
                'vehicle_no'    => $vehicleNo,
                'from_state'    => $fromState,
                'trip_ids_json' => json_encode($tripIds),
                'generated_at'  => $now,
                'status'        => 'Local',
                'created_by'    => $userId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            return ['ok' => true, 'queued' => true, 'log_id' => $logId, 'consol_no' => $localNo,
                    'consol_id' => $id, 'note' => 'Saved locally; generate the consolidated EWB on NIC portal too.'];
        }

        $resp = $this->curl('POST', $this->baseUrl . '/ewayapi/v1.03/ewayapi?action=GenConsolidatedEWB', $payload, $this->authHeaders());
        $ok = $resp['ok'];
        $consolNo = $resp['body']['cEwbNo'] ?? $resp['body']['consolEwbNo'] ?? null;
        $this->updateLog($logId, $resp['body'] ?: [], $ok ? 'Success' : 'Failed', $ok ? null : ($resp['body']['message'] ?? $resp['error'] ?? ''));
        if ($ok && $consolNo) {
            $id = $consolModel->insert([
                'consol_no'     => (string) $consolNo,
                'vehicle_no'    => $vehicleNo,
                'from_state'    => $fromState,
                'trip_ids_json' => json_encode($tripIds),
                'generated_at'  => $now,
                'status'        => 'Active',
                'raw_payload'   => json_encode($resp['body']),
                'created_by'    => $userId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            return ['ok' => true, 'log_id' => $logId, 'consol_no' => $consolNo, 'consol_id' => $id];
        }
        return ['ok' => false, 'log_id' => $logId, 'error' => $resp['body']['message'] ?? $resp['error'] ?? 'unknown'];
    }

    /** Refresh status from NIC (e.g. detect Recipient Reject). */
    public function refreshStatus(int $tripId): array
    {
        $r = $this->fetch($tripId);
        if (!empty($r['ok']) && isset($r['data']['status'])) {
            (new TripModel())->update($tripId, ['ewb_status' => (string) $r['data']['status']]);
        }
        return $r;
    }

    /** Manual entry fallback when the EWB was generated outside the system. */
    public function setManual(int $tripId, string $ewbNo, ?string $validUntil, ?string $ewbDate = null): void
    {
        $logId = $this->writeLog($tripId, 'manual', ['ewbNo' => $ewbNo, 'validUntil' => $validUntil], null, 'Success');
        (new TripModel())->update($tripId, [
            'ewb_no'          => $ewbNo,
            'ewb_date'        => $ewbDate   ? date('Y-m-d H:i:s', strtotime($ewbDate))   : date('Y-m-d H:i:s'),
            'ewb_valid_until' => $validUntil ? date('Y-m-d H:i:s', strtotime($validUntil)) : null,
            'ewb_status'      => 'Active',
        ]);
    }

    private function buildGeneratePayload(array $trip, array $booking, array $client, array $company, array $extra): array
    {
        $docNo   = $trip['lr_no'] ?: $trip['trip_no'];
        $docDate = date('d/m/Y');
        $sell    = (float) ($booking['final_sell_rate'] ?? 0);
        $gstRate = (float) ((new SettingModel())->get('default_gst_rate', '0'));
        $gstAmt  = round($sell * $gstRate / 100, 2);
        $interstate = !empty($client['state']) && !empty($company['company_state'])
            && $this->slug((string) $client['state']) !== $this->slug((string) $company['company_state']);

        return [
            'supplyType'       => 'O',        // Outward
            'subSupplyType'    => '1',        // Supply
            'docType'          => 'INV',
            'docNo'             => (string) $docNo,
            'docDate'           => $docDate,
            'fromGstin'         => (string) ($company['company_gstin'] ?? 'URP'),
            'fromTrdName'       => (string) ($company['company_name']  ?? ''),
            'fromAddr1'         => (string) ($company['company_address'] ?? '-'),
            'fromPlace'         => (string) ($company['company_state']   ?? '-'),
            'fromPincode'       => 0,
            'fromStateCode'     => 0,
            'actualFromStateCode' => 0,
            'toGstin'           => (string) ($client['gst_no'] ?? 'URP'),
            'toTrdName'         => (string) ($booking['consignee_name'] ?: ($client['company_name'] ?? '')),
            'toAddr1'           => (string) ($booking['consignee_address'] ?: ($client['address'] ?? '-')),
            'toPlace'           => (string) ($client['city'] ?? '-'),
            'toPincode'         => 0,
            'toStateCode'       => 0,
            'actualToStateCode' => 0,
            'totalValue'        => round($sell, 2),
            'cgstValue'         => $interstate ? 0 : round($gstAmt / 2, 2),
            'sgstValue'         => $interstate ? 0 : round($gstAmt / 2, 2),
            'igstValue'         => $interstate ? round($gstAmt, 2) : 0,
            'cessValue'         => 0,
            'transMode'         => '1',            // 1=Road
            'transDistance'     => (int) ($extra['distance_km'] ?? 0),
            'transporterId'     => (string) ($company['company_gstin'] ?? ''),
            'transporterName'   => (string) ($company['company_name']  ?? ''),
            'transDocNo'        => (string) ($trip['lr_no'] ?: $trip['trip_no']),
            'transDocDate'      => $docDate,
            'vehicleNo'         => str_replace(' ', '', (string) ($trip['vehicle_number'] ?? '')),
            'vehicleType'       => 'R',
            'itemList' => [[
                'productName'   => 'Freight / Transport service',
                'productDesc'   => (string) ($booking['load_details'] ?? 'Transportation by road'),
                'hsnCode'       => 996791,
                'quantity'      => 1,
                'qtyUnit'       => 'OTH',
                'taxableAmount' => round($sell, 2),
                'cgstRate'      => $interstate ? 0 : $gstRate / 2,
                'sgstRate'      => $interstate ? 0 : $gstRate / 2,
                'igstRate'      => $interstate ? $gstRate : 0,
                'cessRate'      => 0,
            ]],
        ];
    }

    private function authHeaders(): array
    {
        $h = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'gstin: ' . $this->gstin,
        ];
        if ($this->gspUser !== '') $h[] = 'username: ' . $this->gspUser;
        if ($this->gspPass !== '') $h[] = 'password: ' . $this->gspPass;
        return $h;
    }

    private function curl(string $method, string $url, ?array $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
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

    private function writeLog(int $tripId, string $action, array $req, ?array $resp, string $status): int
    {
        $db = \Config\Database::connect();
        $db->table('ewb_logs')->insert([
            'trip_id'          => $tripId,
            'action'           => $action,
            'request_payload'  => json_encode($req, JSON_UNESCAPED_UNICODE),
            'response_payload' => $resp ? json_encode($resp, JSON_UNESCAPED_UNICODE) : null,
            'status'           => $status,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    private function updateLog(int $logId, array $resp, string $status, ?string $error): void
    {
        \Config\Database::connect()->table('ewb_logs')->where('id', $logId)->update([
            'response_payload' => json_encode($resp, JSON_UNESCAPED_UNICODE),
            'status'           => $status,
            'error_message'    => $error ? substr($error, 0, 250) : null,
        ]);
    }

    private function slug(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $s));
    }
}

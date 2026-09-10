<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\TripModel;
use App\Models\EpodSignatureModel;
use App\Models\FeatureFlagModel;

/**
 * Public, token-gated e-POD signing flow for the consignee. The trip carries
 * a per-trip token that is shared via WhatsApp / link; consignee opens the URL
 * on their phone, draws their signature on a canvas, submits.
 *
 *   GET  /epod/{token}          — sign page
 *   POST /epod/{token}/submit   — accept payload
 */
class EpodController extends Controller
{
    protected $helpers = ['url'];

    private function trip(string $token): ?array
    {
        if (!FeatureFlagModel::enabled('epod')) return null;
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $row = (new TripModel())->where('epod_token', $token)->where('deleted_at', null)->first();
        if (!$row) return null;
        if (in_array($row['current_status'], ['Cancelled'], true)) return null;
        return $row;
    }

    public function index(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setBody(view('epod/invalid'));

        $existing = (new EpodSignatureModel())->forTrip((int) $trip['id']);
        $db = \Config\Database::connect();
        $bk = $db->table('bookings')->select('route_text, consignee_name')->where('id', (int) $trip['booking_id'])->get()->getRowArray();

        return $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody(view('epod/sign', [
                'trip'    => $trip,
                'token'   => $token,
                'route'   => $bk['route_text'] ?? '',
                'consignee_name_hint' => $bk['consignee_name'] ?? '',
                'existing'=> $existing,
                'appName' => env('tpt.appName', 'TPT Aggregator'),
            ]));
    }

    public function submit(string $token)
    {
        $trip = $this->trip($token);
        if (!$trip) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);

        $data = $this->request->getPost();
        $sig  = (string) ($data['signature_data'] ?? '');
        if (!preg_match('#^data:image/png;base64,#', $sig) || strlen($sig) < 200) {
            return redirect()->back()->with('error', 'Please draw a signature before submitting.');
        }
        $name = trim((string) ($data['consignee_name'] ?? ''));
        if ($name === '') {
            return redirect()->back()->with('error', 'Consignee name is required.');
        }

        (new EpodSignatureModel())->insert([
            'trip_id'         => (int) $trip['id'],
            'consignee_name'  => substr($name, 0, 200),
            'consignee_mobile'=> trim((string) ($data['consignee_mobile'] ?? '')),
            'signed_at'       => date('Y-m-d H:i:s'),
            'signature_data'  => $sig,
            'remarks'         => trim((string) ($data['remarks'] ?? '')) ?: null,
            'damage_noted'    => !empty($data['damage_noted'])   ? 1 : 0,
            'shortage_noted'  => !empty($data['shortage_noted']) ? 1 : 0,
            'ip_address'      => $this->request->getIPAddress(),
            'user_agent'      => substr((string) $this->request->getUserAgent()->getAgentString(), 0, 255),
            'geo_lat'         => $data['geo_lat'] ?? null,
            'geo_lng'         => $data['geo_lng'] ?? null,
        ]);

        // Mark trip as POD received + invalidate the token so it can't be reused
        (new TripModel())->update((int) $trip['id'], [
            'pod_status'      => 'Received',
            'pod_received_at' => date('Y-m-d H:i:s'),
            'epod_token'      => null,
        ]);

        return $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody(view('epod/done', ['appName' => env('tpt.appName', 'TPT Aggregator')]));
    }
}

<?php

namespace App\Controllers;

use App\Models\InsuranceQuoteModel;
use App\Models\TripModel;
use App\Models\BookingModel;
use App\Libraries\InsuranceService;

/** Per-trip cargo insurance. Gated by feature_flag 'trip_insurance'. */
class TripInsuranceController extends BaseController
{
    /** Request a quote for a trip's cargo. */
    public function quote(int $tripId)
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $cargo = (float) ($trip['cargo_value_inr'] ?? 0);
        if ($cargo <= 0) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Set cargo value on the trip first.');
        }

        $bk = !empty($trip['booking_id']) ? (new BookingModel())->find((int) $trip['booking_id']) : null;
        $route = (string) ($bk['route_text'] ?? '');
        $parts = preg_split('/\s*(?:→|->|to|—|-)\s*/u', $route, 2);
        $pickup = trim((string) ($parts[0] ?? $trip['loading_point'] ?? ''));
        $drop   = trim((string) ($parts[1] ?? $trip['unloading_point'] ?? ''));

        $r = (new InsuranceService())->quote($cargo, $pickup, $drop, $trip['vehicle_number'] ?? null);
        if (empty($r['ok'])) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Quote failed: ' . ($r['error'] ?? 'unknown'));
        }

        (new InsuranceQuoteModel())->insert([
            'trip_id'     => $tripId,
            'cargo_value' => $cargo,
            'premium'     => (float) $r['premium'],
            'provider'    => (string) ($r['provider'] ?? 'unknown'),
            'policy_no'   => $r['policy_no'] ?? null,
            'valid_until' => isset($r['valid_until']) ? date('Y-m-d H:i:s', strtotime((string) $r['valid_until'])) : null,
            'raw_payload' => json_encode($r['raw'] ?? $r, JSON_UNESCAPED_SLASHES),
            'status'      => 'Quoted',
            'created_by'  => $this->auth->id(),
        ]);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', sprintf(
            'Insurance quote: ₹%s premium for ₹%s cargo via %s.',
            number_format((float) $r['premium'], 2),
            number_format($cargo, 2),
            esc((string) ($r['provider'] ?? 'provider'))
        ));
    }

    public function bind(int $tripId, int $quoteId)
    {
        $model = new InsuranceQuoteModel();
        $row = $model->find($quoteId);
        if (!$row || (int) $row['trip_id'] !== $tripId) return redirect()->back()->with('error', 'Quote not found.');

        $policyNo = trim((string) $this->request->getPost('policy_no'));
        if ($policyNo === '') return redirect()->back()->with('error', 'Policy number required.');

        $model->update($quoteId, [
            'policy_no' => $policyNo,
            'valid_from'=> $this->request->getPost('valid_from')  ?: null,
            'valid_until'=> $this->request->getPost('valid_until') ?: null,
            'status'    => 'Bound',
        ]);
        // Mirror onto trip for quick display
        (new TripModel())->update($tripId, [
            'insurance_policy_no' => $policyNo,
            'insurance_provider'  => $row['provider'],
        ]);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Policy bound: ' . esc($policyNo));
    }
}

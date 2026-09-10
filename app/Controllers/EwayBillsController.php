<?php

namespace App\Controllers;

use App\Libraries\EwayBillService;
use App\Models\TripModel;

class EwayBillsController extends BaseController
{
    public function generate(int $tripId)
    {
        $dist = (int) $this->request->getPost('distance_km');
        $result = (new EwayBillService())->generate($tripId, ['distance_km' => $dist]);

        if (!empty($result['ok'])) {
            return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'EWB ' . $result['ewb_no'] . ' generated.');
        }
        if (!empty($result['queued'])) {
            return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', $result['note'] ?? 'Queued.');
        }
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'EWB failed: ' . ($result['error'] ?? 'unknown'));
    }

    public function fetch(int $tripId)
    {
        $result = (new EwayBillService())->fetch($tripId);
        if (!empty($result['ok']))    return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'EWB refreshed.');
        if (!empty($result['queued'])) return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'Fetch queued — not configured.');
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'Fetch failed.');
    }

    public function cancel(int $tripId)
    {
        $reason = (string) $this->request->getPost('reason') ?: 'Duplicate';
        $result = (new EwayBillService())->cancel($tripId, $reason);
        if (!empty($result['ok']))    return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'EWB cancelled.');
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'Cancel failed.');
    }

    public function setManual(int $tripId)
    {
        $post = $this->request->getPost();
        $ewbNo      = trim((string) ($post['ewb_no'] ?? ''));
        $validUntil = trim((string) ($post['ewb_valid_until'] ?? ''));
        $ewbDate    = trim((string) ($post['ewb_date'] ?? ''));
        if ($ewbNo === '') return redirect()->to(site_url('trips/' . $tripId))->with('error', 'EWB number required.');
        (new EwayBillService())->setManual($tripId, $ewbNo, $validUntil ?: null, $ewbDate ?: null);
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'EWB saved.');
    }

    public function clearEwb(int $tripId)
    {
        (new TripModel())->update($tripId, [
            'ewb_no' => null, 'ewb_date' => null, 'ewb_valid_until' => null, 'ewb_status' => 'None',
        ]);
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'EWB cleared.');
    }

    /** Update Part-B (vehicle change). NIC requires this within 24 hrs of vehicle swap. */
    public function partBUpdate(int $tripId)
    {
        $newVehicle = trim((string) $this->request->getPost('new_vehicle_no'));
        $reason     = trim((string) $this->request->getPost('reason')) ?: 'Vehicle change';
        if ($newVehicle === '') {
            return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'New vehicle number required.');
        }
        $r = (new EwayBillService())->updatePartB($tripId, $newVehicle, $reason, $reason);
        if (!empty($r['ok'])) {
            return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'EWB Part-B updated to ' . $newVehicle . '.');
        }
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'Part-B update failed: ' . ($r['error'] ?? 'unknown'));
    }

    /** Extend EWB validity. Allowed within 8 hrs of expiry. */
    public function extendValidity(int $tripId)
    {
        $code      = (int) ($this->request->getPost('reason_code') ?: 99);
        $remark    = trim((string) $this->request->getPost('reason_remark')) ?: 'Trip delayed';
        $remDist   = trim((string) $this->request->getPost('remaining_distance'));
        $newCity   = trim((string) $this->request->getPost('new_city'));
        $r = (new EwayBillService())->extendValidity($tripId, $code, $remark, $remDist ?: null, $newCity ?: null);
        if (!empty($r['ok'])) {
            return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('success', 'Validity extended.' . (!empty($r['new_valid_until']) ? ' New expiry: ' . $r['new_valid_until'] : ''));
        }
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with('error', 'Extend failed: ' . ($r['error'] ?? 'unknown'));
    }

    /** Generate a consolidated EWB from a list of trip IDs sharing the same vehicle. */
    public function consolidate()
    {
        $tripIds = array_filter(array_map('intval', (array) $this->request->getPost('trip_ids')));
        $vehicle = trim((string) $this->request->getPost('vehicle_no'));
        $fromCty = trim((string) $this->request->getPost('from_place'));
        $fromSt  = trim((string) $this->request->getPost('from_state'));
        if (count($tripIds) < 2 || $vehicle === '') {
            return redirect()->back()->with('error', 'Pick at least 2 trips and provide a vehicle number.');
        }
        $r = (new EwayBillService())->generateConsolidated($tripIds, $vehicle, $fromCty, $fromSt, $this->auth->id());
        if (!empty($r['ok'])) {
            return redirect()->to(site_url('trips'))->with('success', 'Consolidated EWB ' . ($r['consol_no'] ?? '') . ' created.');
        }
        return redirect()->back()->with('error', 'Consolidate failed: ' . ($r['error'] ?? 'unknown'));
    }

    /** Refresh status from NIC (e.g., detect Recipient Reject). */
    public function refreshStatus(int $tripId)
    {
        $r = (new EwayBillService())->refreshStatus($tripId);
        $msg = !empty($r['ok']) ? 'Status refreshed.' : 'Refresh failed: ' . ($r['error'] ?? 'unknown');
        return redirect()->to(site_url('trips/' . $tripId) . '#ewb')->with(!empty($r['ok']) ? 'success' : 'error', $msg);
    }
}

<?php

namespace App\Controllers;

use App\Models\TripModel;
use App\Models\LatestVehicleStatusModel;
use App\Models\GpsLogModel;
use App\Libraries\LocoNavService;

class GpsController extends BaseController
{
    /** Fleet map — one pin per active vehicle with latest known position. */
    public function index()
    {
        $db  = \Config\Database::connect();
        $rows = $db->table('trips')
            ->select('trips.id AS trip_id, trips.trip_no, trips.vehicle_number, trips.current_status, trips.driver_name, trips.driver_mobile,
                      bookings.booking_no, bookings.route_text, clients.company_name AS client_company,
                      lvs.latitude, lvs.longitude, lvs.speed, lvs.gps_timestamp, lvs.address, lvs.delay_flag, lvs.eta_text')
            ->join('latest_vehicle_status lvs', 'lvs.vehicle_number = trips.vehicle_number', 'left')
            ->join('bookings', 'bookings.id = trips.booking_id', 'left')
            ->join('clients',  'clients.id  = bookings.client_id', 'left')
            ->whereNotIn('trips.current_status', ['Closed', 'Cancelled'])
            ->where('trips.deleted_at IS NULL')
            ->where("trips.vehicle_number != ''")
            ->orderBy('trips.id', 'DESC')
            ->get()->getResultArray();

        return $this->render('gps/index', [
            'pageTitle' => 'GPS Fleet Tracker [Transportation]',
            'rows'      => $rows,
            'service'   => new LocoNavService(),
        ], retroFixedShell: true);
    }

    /** Single-trip live map with trail. */
    public function trip(int $tripId)
    {
        $trip = (new TripModel())->withJoins()->where('trips.id', $tripId)->first();
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $latest = (new LatestVehicleStatusModel())->getByVehicle(
            strtoupper(preg_replace('/\s+/', '', (string) $trip['vehicle_number']))
        );
        $logs = !empty($trip['vehicle_number'])
            ? (new GpsLogModel())->forTrip($tripId, 500)
            : [];

        return $this->render('gps/trip', [
            'pageTitle' => 'GPS Fleet Tracker [Transportation]',
            'trip'      => $trip,
            'latest'    => $latest,
            'logs'      => $logs,
            'service'   => new LocoNavService(),
        ], retroFixedShell: true);
    }

    /** Manual refresh for a single trip. */
    public function refreshTrip(int $tripId)
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return redirect()->back()->with('error', 'Trip not found.');
        if (empty($trip['vehicle_number'])) {
            return redirect()->back()->with('error', 'Assign a vehicle number first.');
        }
        $service = new LocoNavService();
        $result  = $service->refreshVehicle((string) $trip['vehicle_number'], $tripId);
        $service->detectDelay($tripId, (string) $trip['vehicle_number']);

        if (!empty($result['ok'])) {
            return redirect()->back()->with('success', 'GPS refreshed.');
        }
        $msg = !empty($result['queued'])
            ? 'LocoNav not configured — fill loconav.apiKey in .env.'
            : ('GPS refresh failed: ' . ($result['error'] ?? 'unknown'));
        return redirect()->back()->with('error', $msg);
    }

    /** Manual refresh for every active trip (fleet map button + cron). */
    public function refreshAll()
    {
        $result = (new LocoNavService())->refreshAllActive();
        $msg = sprintf('GPS refresh: %d ok, %d failed, %d delay flags raised.',
            $result['ok'], $result['failed'], $result['delays_flagged']);
        return redirect()->to(site_url('gps'))->with('success', $msg);
    }
}

<?php

namespace App\Controllers;

use App\Models\TripModel;
use App\Models\BookingModel;

/**
 * Arrival — a focused view for capturing everything that happens once the
 * truck reaches destination: unloading arrived/departed timestamps, POD
 * status, remarks, and the client-feedback trigger. Keeps ops from having
 * to hunt through the full Trip page just to log arrival details.
 */
class ArrivalController extends BaseController
{
    public function index()
    {
        $model = new TripModel();
        $q = $model->withJoins()
            ->select('trips.*, bookings.route_text, bookings.consignee_name, bookings.charge_weight_kg')
            ->whereIn('trips.current_status', ['In Transit', 'Arrived', 'Unloading', 'Delivered', 'POD Received'])
            ->where('trips.deleted_at', null)
            ->orderBy('trips.id', 'DESC');

        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            $uid = (int) $this->auth->id();
            $q->groupStart()
                ->whereIn('bookings.client_id', $ownedIds ?: [0])
                ->orWhere('trips.assigned_to',    $uid)
                ->orWhere('bookings.assigned_to', $uid)
              ->groupEnd();
        }

        $rows = $q->paginate($this->perPage());

        // Feedback per trip — LEFT JOIN would work too, but a single follow-up
        // query keeps the main SELECT simple and the field list unambiguous.
        $feedbackByTrip = [];
        if ($rows) {
            $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
            $fbRows = \Config\Database::connect()
                ->table('trip_feedback')
                ->whereIn('trip_id', $ids)
                ->get()->getResultArray();
            foreach ($fbRows as $fb) $feedbackByTrip[(int) $fb['trip_id']] = $fb;
        }

        return $this->render('arrival/index', [
            'pageTitle'      => 'Arrival Log',
            'rows'           => $rows,
            'pager'          => $model->pager,
            'feedbackByTrip' => $feedbackByTrip,
        ]);
    }

    /** POST /arrival/:id/save — quick-log arrival timestamps + POD + remarks. */
    public function save(int $id)
    {
        $trip = (new TripModel())->find($id);
        if (!$trip) return redirect()->to(site_url('arrival'))->with('error', 'Trip not found.');

        $post = $this->request->getPost();
        $data = [
            'unloading_arrived_at'  => !empty($post['unloading_arrived_at'])  ? $post['unloading_arrived_at']  : null,
            'unloading_departed_at' => !empty($post['unloading_departed_at']) ? $post['unloading_departed_at'] : null,
            'pod_status'            => in_array(($post['pod_status'] ?? ''), ['Pending','Received','Uploaded'], true) ? $post['pod_status'] : $trip['pod_status'],
            'remarks'               => trim((string) ($post['remarks'] ?? '')) ?: null,
            'updated_by'            => $this->auth->id(),
        ];
        // Auto-mark as Delivered when unloading_departed is set on a live trip.
        if (!empty($data['unloading_departed_at']) && !in_array($trip['current_status'], ['Delivered','POD Received','Closed','Cancelled'], true)) {
            $data['current_status']    = 'Delivered';
            $data['delivery_datetime'] = $trip['delivery_datetime'] ?? date('Y-m-d H:i:s');
        }
        (new TripModel())->update($id, $data);
        return redirect()->to(site_url('arrival'))->with('success', 'Arrival details saved for ' . $trip['trip_no'] . '.');
    }
}

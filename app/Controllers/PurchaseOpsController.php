<?php

namespace App\Controllers;

use App\Models\TripModel;
use App\Models\TripAdvanceModel;

/**
 * Purchase-side aggregate pages for the two cards we moved off the Trip
 * page: Driver Advance / Bhatta and Cargo Insurance. Each renders a
 * cross-trip table with quick-edit forms per row so nothing is buried
 * inside individual trip pages anymore.
 */
class PurchaseOpsController extends BaseController
{
    /** Table of all trips + inline "add advance" per row. */
    public function advances()
    {
        $model = new TripModel();
        $q = $model->withJoins()
            ->select('trips.*, bookings.route_text, bookings.client_id')
            ->whereNotIn('trips.current_status', ['Cancelled'])
            ->where('trips.deleted_at', null)
            ->orderBy('trips.id', 'DESC');

        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) $q->whereIn('bookings.client_id', $ownedIds ?: [0]);

        $trips = $q->paginate($this->perPage());
        $advM  = new TripAdvanceModel();
        // Attach advances + totals per trip so the view doesn't need to loop over the model.
        foreach ($trips as &$t) {
            $t['advances']       = $advM->forTrip((int) $t['id']);
            $t['advance_totals'] = $advM->totalsForTrip((int) $t['id']);
        }
        unset($t);

        return $this->render('purchase_ops/advances', [
            'pageTitle' => 'Driver Advance / Bhatta',
            'rows'      => $trips,
            'pager'     => $model->pager,
        ]);
    }

    /** Table of all trips + inline cargo-insurance policy fields. */
    public function insurance()
    {
        $model = new TripModel();
        $q = $model->withJoins()
            ->select('trips.*, bookings.route_text, bookings.client_id, bookings.cargo_value_inr AS booking_cargo_value')
            ->whereNotIn('trips.current_status', ['Cancelled'])
            ->where('trips.deleted_at', null)
            ->orderBy('trips.id', 'DESC');

        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) $q->whereIn('bookings.client_id', $ownedIds ?: [0]);

        return $this->render('purchase_ops/insurance', [
            'pageTitle' => 'Cargo Insurance',
            'rows'      => $q->paginate($this->perPage()),
            'pager'     => $model->pager,
        ]);
    }
}

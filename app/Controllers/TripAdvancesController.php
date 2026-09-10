<?php

namespace App\Controllers;

use App\Models\TripAdvanceModel;
use App\Models\TripModel;

/** Driver advance / bhatta given against a trip. Routes nested under /trips/:id/advances */
class TripAdvancesController extends BaseController
{
    public function store(int $tripId)
    {
        if (!(new TripModel())->find($tripId)) {
            return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');
        }
        $amount = (float) $this->request->getPost('amount');
        if ($amount <= 0) return redirect()->back()->with('error', 'Amount must be positive.');

        (new TripAdvanceModel())->insert([
            'trip_id'      => $tripId,
            'amount'       => $amount,
            'mode'         => in_array($this->request->getPost('mode'), TripAdvanceModel::MODES, true)
                              ? $this->request->getPost('mode') : 'Cash',
            'reference_no' => trim((string) $this->request->getPost('reference_no')),
            'given_to'     => trim((string) $this->request->getPost('given_to')),
            'given_at'     => $this->request->getPost('given_at') ?: date('Y-m-d H:i:s'),
            'given_by'     => $this->auth->id(),
            'notes'        => trim((string) $this->request->getPost('notes')),
        ]);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Advance recorded.');
    }

    public function settle(int $tripId, int $advId)
    {
        $model = new TripAdvanceModel();
        $row = $model->find($advId);
        if (!$row || (int) $row['trip_id'] !== $tripId) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Advance not found.');
        }
        $settledAmt = (float) $this->request->getPost('settled_amount');
        if ($settledAmt < 0) $settledAmt = 0;
        $model->update($advId, [
            'settled_amount' => $settledAmt,
            'settled_at'     => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Advance settled.');
    }

    public function delete(int $tripId, int $advId)
    {
        $model = new TripAdvanceModel();
        $row = $model->find($advId);
        if ($row && (int) $row['trip_id'] === $tripId) $model->delete($advId);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Advance removed.');
    }
}

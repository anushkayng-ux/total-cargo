<?php

namespace App\Controllers;

use App\Models\TripStopModel;
use App\Models\TripModel;

/** Multi-pickup / multi-drop stops on a trip. Routes nested under /trips/:id/stops */
class TripStopsController extends BaseController
{
    public function store(int $tripId)
    {
        if (!(new TripModel())->find($tripId)) {
            return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');
        }
        $data = [
            'trip_id'        => $tripId,
            'sequence'       => max(1, (int) ($this->request->getPost('sequence') ?: 1)),
            'stop_type'      => in_array($this->request->getPost('stop_type'), TripStopModel::TYPES, true) ? $this->request->getPost('stop_type') : 'pickup',
            'address'        => trim((string) $this->request->getPost('address')),
            'city'           => trim((string) $this->request->getPost('city')),
            'contact_name'   => trim((string) $this->request->getPost('contact_name')),
            'contact_mobile' => trim((string) $this->request->getPost('contact_mobile')),
            'gstin'          => trim((string) $this->request->getPost('gstin')),
            'planned_at'     => $this->request->getPost('planned_at') ?: null,
            'notes'          => trim((string) $this->request->getPost('notes')),
        ];
        if ($data['address'] === '') {
            return redirect()->back()->with('error', 'Address is required for a stop.');
        }
        (new TripStopModel())->insert($data);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Stop added.');
    }

    public function update(int $tripId, int $stopId)
    {
        $model = new TripStopModel();
        $row = $model->find($stopId);
        if (!$row || (int) $row['trip_id'] !== $tripId) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Stop not found.');
        }
        $action = (string) $this->request->getPost('action');
        $now = date('Y-m-d H:i:s');
        if ($action === 'arrive')      $model->update($stopId, ['arrived_at' => $now]);
        elseif ($action === 'depart')  $model->update($stopId, ['departed_at' => $now]);
        else {
            $model->update($stopId, [
                'sequence'       => max(1, (int) ($this->request->getPost('sequence') ?: $row['sequence'])),
                'stop_type'      => $this->request->getPost('stop_type') ?: $row['stop_type'],
                'address'        => $this->request->getPost('address')  ?: $row['address'],
                'city'           => $this->request->getPost('city'),
                'contact_name'   => $this->request->getPost('contact_name'),
                'contact_mobile' => $this->request->getPost('contact_mobile'),
                'gstin'          => $this->request->getPost('gstin'),
                'planned_at'     => $this->request->getPost('planned_at') ?: null,
                'notes'          => $this->request->getPost('notes'),
            ]);
        }
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Stop updated.');
    }

    public function delete(int $tripId, int $stopId)
    {
        $model = new TripStopModel();
        $row = $model->find($stopId);
        if ($row && (int) $row['trip_id'] === $tripId) $model->delete($stopId);
        return redirect()->to(site_url('trips/' . $tripId))->with('success', 'Stop removed.');
    }
}

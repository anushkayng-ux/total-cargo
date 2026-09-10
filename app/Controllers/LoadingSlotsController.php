<?php

namespace App\Controllers;

use App\Models\LoadingSlotModel;
use App\Models\BookingModel;

/** Loading-slot booking with the consignor. Gated by feature_flag 'loading_slots'. */
class LoadingSlotsController extends BaseController
{
    public function store(int $bookingId)
    {
        if (!(new BookingModel())->find($bookingId)) {
            return redirect()->to(site_url('bookings'))->with('error', 'Booking not found.');
        }
        $data = [
            'booking_id'        => $bookingId,
            'plant_name'        => trim((string) $this->request->getPost('plant_name')),
            'slot_date'         => $this->request->getPost('slot_date'),
            'slot_window_start' => $this->request->getPost('slot_window_start') ?: null,
            'slot_window_end'   => $this->request->getPost('slot_window_end') ?: null,
            'gate_pass_no'      => $this->request->getPost('gate_pass_no') ?: null,
            'status'            => 'Requested',
            'requested_at'      => date('Y-m-d H:i:s'),
            'notes'             => $this->request->getPost('notes'),
            'created_by'        => $this->auth->id(),
        ];
        if ($data['plant_name'] === '' || !$data['slot_date']) {
            return redirect()->back()->with('error', 'Plant name and slot date are required.');
        }
        (new LoadingSlotModel())->insert($data);
        return redirect()->to(site_url('bookings/' . $bookingId))->with('success', 'Loading slot requested.');
    }

    public function update(int $bookingId, int $slotId)
    {
        $model = new LoadingSlotModel();
        $row = $model->find($slotId);
        if (!$row || (int) $row['booking_id'] !== $bookingId) {
            return redirect()->to(site_url('bookings/' . $bookingId))->with('error', 'Slot not found.');
        }
        $action = (string) $this->request->getPost('action');
        $now = date('Y-m-d H:i:s');
        if ($action === 'confirm')         $model->update($slotId, ['status' => 'Confirmed',  'confirmed_at' => $now]);
        elseif ($action === 'use')         $model->update($slotId, ['status' => 'Used',       'used_at'      => $now]);
        elseif ($action === 'cancel')      $model->update($slotId, ['status' => 'Cancelled']);
        elseif ($action === 'miss')        $model->update($slotId, ['status' => 'Missed']);
        elseif ($action === 'gate-pass') {
            $gp = trim((string) $this->request->getPost('gate_pass_no'));
            $model->update($slotId, ['gate_pass_no' => $gp]);
        }
        return redirect()->to(site_url('bookings/' . $bookingId))->with('success', 'Slot updated.');
    }

    public function delete(int $bookingId, int $slotId)
    {
        $model = new LoadingSlotModel();
        $row = $model->find($slotId);
        if ($row && (int) $row['booking_id'] === $bookingId) $model->delete($slotId);
        return redirect()->to(site_url('bookings/' . $bookingId))->with('success', 'Slot removed.');
    }
}

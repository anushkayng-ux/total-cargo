<?php

namespace App\Controllers;

use App\Models\VendorBillModel;
use App\Models\VendorPaymentModel;
use App\Models\VendorModel;
use App\Models\TripModel;
use App\Models\DocumentModel;

class VendorBillsController extends BaseController
{
    public function index()
    {
        $status = (string) $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('q'));

        $model = new VendorBillModel();
        $query = $model->withJoins()->orderBy('vendor_bills.id', 'DESC');

        if ($search !== '') {
            $query->groupStart()
                ->like('vendor_bills.bill_no', $search)
                ->orLike('vendors.company_name', $search)
                ->orLike('trips.trip_no', $search)
                ->groupEnd();
        }
        if ($status !== '') $query->where('vendor_bills.status', $status);

        // Ownership scope — vendor bills link to trips → bookings → clients.
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) {
            $query->join('bookings b', 'b.id = trips.booking_id', 'left')
                  ->whereIn('b.client_id', $ownedIds ?: [0]);
        }

        return $this->render('vendor_bills/index', [
            'pageTitle' => 'Vendor Bills',
            'rows'      => $query->paginate($this->perPage()),
            'pager'     => $model->pager,
            'search'    => $search,
            'status'    => $status,
            'statuses'  => VendorBillModel::STATUSES,
        ]);
    }

    public function create()
    {
        return $this->render('vendor_bills/form', [
            'pageTitle' => 'New Vendor Bill',
            'row'       => null,
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'trips'     => (new TripModel())->orderBy('id', 'DESC')->limit(100)->findAll(),
        ]);
    }

    public function createFromTrip(int $tripId)
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $pre = [
            'vendor_id' => $trip['vendor_id'],
            'trip_id'   => $tripId,
        ];
        return $this->render('vendor_bills/form', [
            'pageTitle' => 'Vendor Bill from Trip',
            'row'       => null, 'prefill' => $pre,
            'vendors'   => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'trips'     => (new TripModel())->orderBy('id', 'DESC')->limit(100)->findAll(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $rules = [
            'vendor_id'   => 'required|integer',
            'bill_date'   => 'required|valid_date',
            'bill_amount' => 'required|numeric|greater_than[0]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $amt = (float) $post['bill_amount'];
        (new VendorBillModel())->insert([
            'vendor_id'   => (int) $post['vendor_id'],
            'trip_id'     => !empty($post['trip_id']) ? (int) $post['trip_id'] : null,
            'bill_no'     => $post['bill_no'] ?: null,
            'bill_date'   => $post['bill_date'],
            'bill_amount' => $amt,
            'amount_paid' => 0,
            'balance_due' => $amt,
            'due_date'    => $post['due_date'] ?: null,
            'status'      => 'Open',
            'notes'       => $post['notes'] ?: null,
            'created_by'  => $this->auth->id(),
        ]);
        $id = (int) \Config\Database::connect()->insertID();

        $upload = $this->handleBillUpload($id);

        // Notify Accounts a vendor bill needs processing/payment.
        \App\Libraries\Notify::toPermission('vendor_bills', 'can_view',
            'Vendor bill added — ₹' . number_format($amt, 2),
            'A vendor bill was recorded' . (!empty($post['bill_no']) ? ' (' . $post['bill_no'] . ')' : ''),
            site_url('vendor-bills/' . $id),
            ['type' => 'vendor_bill', 'icon' => 'file-earmark-text']);

        if ($upload === 'invalid') {
            return redirect()->to(site_url('vendor-bills/' . $id))->with('error', 'Bill saved, but the attached file was rejected — use PDF/image up to 10 MB.');
        }
        return redirect()->to(site_url('vendor-bills/' . $id))->with('success', 'Vendor bill created' . ($upload === 'saved' ? ' with bill copy attached.' : '.'));
    }

    public function show(int $id)
    {
        $row = (new VendorBillModel())->withJoins()->where('vendor_bills.id', $id)->first();
        if (!$row) return redirect()->to(site_url('vendor-bills'))->with('error', 'Not found.');

        return $this->render('vendor_bills/show', [
            'pageTitle' => 'Vendor Bill ' . ($row['bill_no'] ?: '#' . $id),
            'row'       => $row,
            'payments'  => (new VendorPaymentModel())->forBill($id),
            'billDoc'   => !empty($row['document_id']) ? (new DocumentModel())->find((int) $row['document_id']) : null,
        ]);
    }

    public function edit(int $id)
    {
        $row = (new VendorBillModel())->find($id);
        if (!$row) return redirect()->to(site_url('vendor-bills'))->with('error', 'Not found.');
        $existingDoc = !empty($row['document_id']) ? (new DocumentModel())->find((int) $row['document_id']) : null;
        return $this->render('vendor_bills/form', [
            'pageTitle'   => 'Edit Vendor Bill',
            'row'         => $row,
            'existingDoc' => $existingDoc,
            'vendors'     => (new VendorModel())->where('status', 1)->orderBy('company_name')->findAll(),
            'trips'       => (new TripModel())->orderBy('id', 'DESC')->limit(100)->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $post = $this->request->getPost();
        (new VendorBillModel())->update($id, [
            'vendor_id'   => !empty($post['vendor_id']) ? (int) $post['vendor_id'] : null,
            'trip_id'     => !empty($post['trip_id'])   ? (int) $post['trip_id']   : null,
            'bill_no'     => $post['bill_no']   ?: null,
            'bill_date'   => $post['bill_date'] ?: null,
            'bill_amount' => (float) ($post['bill_amount'] ?? 0),
            'due_date'    => $post['due_date'] ?: null,
            'notes'       => $post['notes']    ?: null,
        ]);
        (new VendorBillModel())->recomputeBalance($id);

        $upload = $this->handleBillUpload($id);
        $msg = 'Vendor bill updated' . ($upload === 'saved' ? ' and bill copy replaced.' : '.');
        if ($upload === 'invalid') {
            return redirect()->to(site_url('vendor-bills/' . $id))->with('error', 'Bill updated, but the new file was rejected — use PDF/image up to 10 MB.');
        }
        return redirect()->to(site_url('vendor-bills/' . $id))->with('success', $msg);
    }

    /** Stream the currently attached bill copy for this vendor bill. */
    public function billFile(int $id)
    {
        $bill = (new VendorBillModel())->find($id);
        if (!$bill || empty($bill['document_id'])) {
            return redirect()->to(site_url('vendor-bills/' . $id))->with('error', 'No bill copy attached.');
        }
        $doc = (new DocumentModel())->find((int) $bill['document_id']);
        if (!$doc) return redirect()->to(site_url('vendor-bills/' . $id))->with('error', 'Bill copy record missing.');

        $full = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
        if (!is_file($full)) return redirect()->to(site_url('vendor-bills/' . $id))->with('error', 'File missing on disk.');

        return $this->response->download($full, null)->setFileName($doc['original_file_name']);
    }

    public function removeBillFile(int $id)
    {
        $bill = (new VendorBillModel())->find($id);
        if (!$bill) return redirect()->to(site_url('vendor-bills'))->with('error', 'Not found.');
        if (!empty($bill['document_id'])) {
            (new DocumentModel())->delete((int) $bill['document_id']);
        }
        (new VendorBillModel())->update($id, ['document_id' => null]);
        return redirect()->to(site_url('vendor-bills/' . $id))->with('success', 'Bill copy removed.');
    }

    /**
     * Handle the posted `bill_file` upload if any. Returns:
     *   'none'    — no file uploaded
     *   'saved'   — file saved, document row + vendor_bill.document_id updated
     *   'invalid' — a file was posted but rejected (bad type / oversized)
     */
    private function handleBillUpload(int $billId): string
    {
        $file = $this->request->getFile('bill_file');
        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) return 'none';
        if (!$file->isValid()) return 'invalid';
        if ($file->getSize() > 10 * 1024 * 1024) return 'invalid';
        $ext = strtolower($file->getExtension() ?: $file->guessExtension() ?: '');
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic'], true)) return 'invalid';

        $originalName = $file->getClientName();
        $fileSize     = (int) $file->getSize();
        $mimeType     = $file->getMimeType() ?: $file->getClientMimeType();

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'vendor-bills' . DIRECTORY_SEPARATOR . $billId;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $stored = $file->getRandomName();
        $file->move($dir, $stored);

        $docModel = new DocumentModel();

        // Replace previous doc if one was already attached
        $existing = (new VendorBillModel())->find($billId);
        if (!empty($existing['document_id'])) {
            $docModel->delete((int) $existing['document_id']);
        }

        $docModel->insert([
            'module_name'         => 'vendor_bill',
            'module_ref_id'       => $billId,
            'document_type'       => 'Vendor Bill',
            'original_file_name'  => $originalName,
            'stored_file_name'    => $stored,
            'file_path'           => 'vendor-bills/' . $billId . '/' . $stored,
            'file_size'           => $fileSize,
            'mime_type'           => $mimeType,
            'source_channel'      => 'upload',
            'verification_status' => 'Pending',
            'uploaded_by'         => $this->auth->id(),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
        $docId = (int) \Config\Database::connect()->insertID();

        (new VendorBillModel())->update($billId, ['document_id' => $docId]);
        return 'saved';
    }

    public function cancel(int $id)
    {
        (new VendorBillModel())->update($id, ['status' => 'Cancelled']);
        return redirect()->to(site_url('vendor-bills/' . $id))->with('success', 'Cancelled.');
    }

    public function delete(int $id)
    {
        (new VendorBillModel())->delete($id);
        return redirect()->to(site_url('vendor-bills'))->with('success', 'Deleted.');
    }
}

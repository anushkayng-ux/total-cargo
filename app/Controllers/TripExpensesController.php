<?php

namespace App\Controllers;

use App\Models\TripExpenseModel;
use App\Models\TripModel;
use App\Models\DocumentModel;
use App\Models\InvoiceModel;

class TripExpensesController extends BaseController
{
    public function store(int $tripId)
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $post = $this->request->getPost();
        $rules = [
            'category' => 'required|max_length[80]',
            'amount'   => 'required|numeric|greater_than[0]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $docId = null;
        $model = new TripExpenseModel();
        $model->insert([
            'trip_id'       => $tripId,
            'expense_date'  => !empty($post['expense_date']) ? $post['expense_date'] : date('Y-m-d'),
            'category'      => (string) $post['category'],
            'description'   => $post['description'] ?? null,
            'amount'        => (float) $post['amount'],
            'is_billable'   => !empty($post['is_billable']) ? 1 : 0,
            'paid_to'       => in_array(($post['paid_to'] ?? ''), ['Driver','Vendor','Direct','Self'], true) ? $post['paid_to'] : 'Direct',
            'payment_mode'  => $post['payment_mode'] ?? null,
            'reference_no'  => $post['reference_no'] ?? null,
            'remarks'       => $post['remarks'] ?? null,
            'created_by'    => $this->auth->id(),
        ]);
        $expenseId = (int) \Config\Database::connect()->insertID();

        $this->handleReceiptUpload($expenseId, $tripId);

        return redirect()->to(site_url('trips/' . $tripId) . '#expenses')->with('success', 'Expense recorded.');
    }

    public function update(int $tripId, int $expenseId)
    {
        $model = new TripExpenseModel();
        $exp = $model->find($expenseId);
        if (!$exp || (int) $exp['trip_id'] !== $tripId) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Not found.');
        }
        if (!empty($exp['billed_on_invoice_id'])) {
            return redirect()->to(site_url('trips/' . $tripId) . '#expenses')->with('error', 'Expense already billed — unbill it first before editing.');
        }

        $post = $this->request->getPost();
        $model->update($expenseId, [
            'expense_date' => !empty($post['expense_date']) ? $post['expense_date'] : $exp['expense_date'],
            'category'     => $post['category'] ?? $exp['category'],
            'description'  => $post['description'] ?? null,
            'amount'       => (float) ($post['amount'] ?? $exp['amount']),
            'is_billable'  => !empty($post['is_billable']) ? 1 : 0,
            'paid_to'      => in_array(($post['paid_to'] ?? ''), ['Driver','Vendor','Direct','Self'], true) ? $post['paid_to'] : $exp['paid_to'],
            'payment_mode' => $post['payment_mode'] ?? null,
            'reference_no' => $post['reference_no'] ?? null,
            'remarks'      => $post['remarks'] ?? null,
            'updated_by'   => $this->auth->id(),
        ]);
        $this->handleReceiptUpload($expenseId, $tripId, $exp);

        return redirect()->to(site_url('trips/' . $tripId) . '#expenses')->with('success', 'Expense updated.');
    }

    public function delete(int $tripId, int $expenseId)
    {
        $model = new TripExpenseModel();
        $exp = $model->find($expenseId);
        if (!$exp || (int) $exp['trip_id'] !== $tripId) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Not found.');
        }
        if (!empty($exp['billed_on_invoice_id'])) {
            return redirect()->to(site_url('trips/' . $tripId) . '#expenses')->with('error', 'Already billed on an invoice — unbill first.');
        }
        if (!empty($exp['document_id'])) {
            (new DocumentModel())->delete((int) $exp['document_id']);
        }
        $model->delete($expenseId);
        return redirect()->to(site_url('trips/' . $tripId) . '#expenses')->with('success', 'Expense removed.');
    }

    public function receipt(int $tripId, int $expenseId)
    {
        $exp = (new TripExpenseModel())->find($expenseId);
        if (!$exp || (int) $exp['trip_id'] !== $tripId || empty($exp['document_id'])) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Receipt not attached.');
        }
        $doc = (new DocumentModel())->find((int) $exp['document_id']);
        if (!$doc) return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Receipt record missing.');

        $full = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
        if (!is_file($full)) return redirect()->to(site_url('trips/' . $tripId))->with('error', 'File missing on disk.');
        return $this->response->download($full, null)->setFileName($doc['original_file_name']);
    }

    /** Release a billable expense from an invoice so it can be edited or re-billed. */
    public function unbill(int $tripId, int $expenseId)
    {
        $model = new TripExpenseModel();
        $exp = $model->find($expenseId);
        if (!$exp || (int) $exp['trip_id'] !== $tripId) {
            return redirect()->to(site_url('trips/' . $tripId))->with('error', 'Not found.');
        }
        $model->update($expenseId, ['billed_on_invoice_id' => null, 'updated_by' => $this->auth->id()]);
        return redirect()->to(site_url('trips/' . $tripId) . '#expenses')->with('success', 'Expense returned to Unbilled.');
    }

    private function handleReceiptUpload(int $expenseId, int $tripId, ?array $priorExpense = null): void
    {
        $file = $this->request->getFile('receipt_file');
        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) return;
        if (!$file->isValid()) return;
        if ($file->getSize() > 10 * 1024 * 1024) return;
        $ext = strtolower($file->getExtension() ?: $file->guessExtension() ?: '');
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic'], true)) return;

        $originalName = $file->getClientName();
        $fileSize     = (int) $file->getSize();
        $mimeType     = $file->getMimeType() ?: $file->getClientMimeType();

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'trip-expenses' . DIRECTORY_SEPARATOR . $tripId;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $stored = $file->getRandomName();
        $file->move($dir, $stored);

        $docModel = new DocumentModel();
        if ($priorExpense && !empty($priorExpense['document_id'])) {
            $docModel->delete((int) $priorExpense['document_id']);
        }
        $docModel->insert([
            'module_name'         => 'trip_expense',
            'module_ref_id'       => $expenseId,
            'document_type'       => 'Expense Receipt',
            'original_file_name'  => $originalName,
            'stored_file_name'    => $stored,
            'file_path'           => 'trip-expenses/' . $tripId . '/' . $stored,
            'file_size'           => $fileSize,
            'mime_type'           => $mimeType,
            'source_channel'      => 'upload',
            'verification_status' => 'Pending',
            'uploaded_by'         => $this->auth->id(),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
        $docId = (int) \Config\Database::connect()->insertID();
        (new TripExpenseModel())->update($expenseId, ['document_id' => $docId]);
    }
}

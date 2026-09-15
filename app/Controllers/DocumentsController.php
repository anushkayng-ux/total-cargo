<?php

namespace App\Controllers;

use App\Models\DocumentModel;

class DocumentsController extends BaseController
{
    public function index()
    {
        $req     = $this->request;
        $search  = trim((string) $req->getGet('q'));
        $module  = (string) $req->getGet('module');
        $type    = (string) $req->getGet('type');
        $verified= (string) $req->getGet('verified');

        $model = new DocumentModel();
        $query = $model->select('documents.*, u.name AS uploaded_by_name')
            ->join('users u', 'u.id = documents.uploaded_by', 'left')
            ->orderBy('documents.id', 'DESC');

        if ($search !== '') {
            $query->groupStart()
                ->like('documents.original_file_name', $search)
                ->orLike('documents.document_type', $search)
                ->orLike('documents.remarks', $search)
                ->groupEnd();
        }
        if ($module   !== '') $query->where('documents.module_name', $module);
        if ($type     !== '') $query->where('documents.document_type', $type);
        if ($verified !== '') $query->where('documents.verification_status', $verified);

        // Distinct values for filter dropdowns
        $db = \Config\Database::connect();
        $modules = array_column($db->table('documents')->select('module_name')->distinct()->where('deleted_at IS NULL')->get()->getResultArray(), 'module_name');
        $types   = array_column($db->table('documents')->select('document_type')->distinct()->where('deleted_at IS NULL')->get()->getResultArray(), 'document_type');

        return $this->render('documents/index', [
            'pageTitle' => 'Documents [Transportation] — List',
            'rows'      => $query->paginate($this->perPage()),
            'pager'     => $model->pager,
            'search'    => $search,
            'module'    => $module,
            'type'      => $type,
            'verified'  => $verified,
            'modules'   => array_values(array_filter($modules)),
            'types'     => array_values(array_filter($types)),
        ], retroFixedShell: true);
    }

    public function download(int $id)
    {
        $doc = (new DocumentModel())->find($id);
        if (!$doc) return redirect()->to(site_url('documents'))->with('error', 'Not found.');

        $full = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
        if (!is_file($full)) return redirect()->to(site_url('documents'))->with('error', 'File missing on disk.');

        return $this->response->download($full, null)->setFileName($doc['original_file_name']);
    }

    public function verify(int $id)
    {
        $doc = (new DocumentModel())->find($id);
        if (!$doc) return redirect()->to(site_url('documents'))->with('error', 'Not found.');
        $status = (string) $this->request->getPost('verification_status');
        if (!in_array($status, ['Pending', 'Verified', 'Rejected'], true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }
        (new DocumentModel())->update($id, [
            'verification_status' => $status,
            'remarks'             => $this->request->getPost('remarks') ?: $doc['remarks'],
        ]);
        return redirect()->back()->with('success', 'Document marked ' . $status . '.');
    }

    public function delete(int $id)
    {
        (new DocumentModel())->delete($id);
        return redirect()->to(site_url('documents'))->with('success', 'Document removed.');
    }
}

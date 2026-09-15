<?php

namespace App\Controllers;

use App\Models\LeadSourceModel;

class LeadSourcesController extends BaseController
{
    public function index()
    {
        return $this->render('lead_sources/index', [
            'pageTitle' => 'Lead Source Master [General Masters] — List',
            'rows'      => (new LeadSourceModel())->orderBy('source_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function store()
    {
        $rules = [
            'source_name' => 'required|min_length[2]|max_length[80]',
            'source_key'  => 'permit_empty|max_length[40]|is_unique[lead_sources.source_key]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        (new LeadSourceModel())->insert([
            'source_name' => $this->request->getPost('source_name'),
            'source_key'  => $this->request->getPost('source_key') ?: null,
            'status'      => $this->request->getPost('status') ? 1 : 0,
        ]);
        return redirect()->to(site_url('lead-sources'))->with('success', 'Added.');
    }

    public function update(int $id)
    {
        $rules = [
            'source_name' => 'required|min_length[2]|max_length[80]',
            'source_key'  => "permit_empty|max_length[40]|is_unique[lead_sources.source_key,id,$id]",
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        (new LeadSourceModel())->update($id, [
            'source_name' => $this->request->getPost('source_name'),
            'source_key'  => $this->request->getPost('source_key') ?: null,
            'status'      => $this->request->getPost('status') ? 1 : 0,
        ]);
        return redirect()->to(site_url('lead-sources'))->with('success', 'Updated.');
    }

    public function delete(int $id)
    {
        (new LeadSourceModel())->delete($id);
        return redirect()->to(site_url('lead-sources'))->with('success', 'Removed.');
    }
}

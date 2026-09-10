<?php

namespace App\Controllers;

use App\Models\ClientModel;

class ClientsController extends BaseController
{
    /**
     * Admin-only bulk-assignment page. Lets an admin filter the client roster
     * and reassign one or many clients to a specific account manager in one
     * shot. Non-admins are blocked by the AuthFilter but we also gate here so
     * a URL-hop from a stale session can't bypass it.
     */
    public function assignOwners()
    {
        if (!$this->auth->isSuperAdmin() && !\App\Libraries\Notify::isAdminUser((int) $this->auth->id())) {
            return redirect()->to(site_url('clients'))->with('error', 'Admin only.');
        }
        $q      = trim((string) $this->request->getGet('q'));
        $filter = (string) $this->request->getGet('mgr');            // '' | 'unassigned' | userId
        $model  = new ClientModel();
        $query  = $model->select('clients.*, users.name AS manager_name')
            ->join('users', 'users.id = clients.account_manager_user_id', 'left')
            ->orderBy('clients.company_name', 'ASC');
        if ($q !== '') {
            $query->groupStart()
                ->like('clients.company_name', $q)
                ->orLike('clients.city', $q)
                ->orLike('clients.mobile', $q)
                ->orLike('clients.gst_no', $q)
                ->groupEnd();
        }
        if ($filter === 'unassigned') {
            $query->where('clients.account_manager_user_id', null);
        } elseif (ctype_digit($filter)) {
            $query->where('clients.account_manager_user_id', (int) $filter);
        }

        return $this->render('clients/assign_owners', [
            'pageTitle' => 'Assign Client Owners',
            'rows'      => $query->paginate(50),
            'pager'     => $model->pager,
            'users'     => (new \App\Models\UserModel())->activeList(),
            'q'         => $q,
            'filter'    => $filter,
        ]);
    }

    /** POST /clients/assign-owners — bulk-update account_manager_user_id. */
    public function assignOwnersSave()
    {
        if (!$this->auth->isSuperAdmin() && !\App\Libraries\Notify::isAdminUser((int) $this->auth->id())) {
            return redirect()->to(site_url('clients'))->with('error', 'Admin only.');
        }
        $ids     = array_filter(array_map('intval', (array) $this->request->getPost('ids')));
        $userId  = (int) $this->request->getPost('assign_to');
        if (empty($ids)) return redirect()->back()->with('error', 'Tick at least one client first.');
        (new ClientModel())
            ->whereIn('id', $ids)
            ->set(['account_manager_user_id' => $userId > 0 ? $userId : null, 'updated_by' => $this->auth->id()])
            ->update();
        return redirect()->back()->with('success', count($ids) . ' client(s) reassigned.');
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $model  = new ClientModel();
        $query  = $model->orderBy('id', 'DESC');
        if ($search !== '') {
            $query->groupStart()
                ->like('company_name', $search)
                ->orLike('contact_name', $search)
                ->orLike('mobile', $search)
                ->orLike('client_code', $search)
                ->orLike('gst_no', $search)
                ->groupEnd();
        }

        // Ownership scope — same rule everywhere: non-admins only see clients
        // where account_manager_user_id = their id. See BaseController::scopedClientIds().
        $ownedIds = $this->scopedClientIds();
        if ($ownedIds !== null) $query->whereIn('id', $ownedIds ?: [0]);

        return $this->render('clients/index', [
            'pageTitle' => 'Clients',
            'rows'      => $query->paginate(20),
            'pager'     => $model->pager,
            'search'    => $search,
        ]);
    }

    public function create()
    {
        return $this->render('clients/form', [
            'pageTitle' => 'Add Client',
            'row'       => null,
            'managers'  => $this->managerOptions(),
        ]);
    }

    public function store()
    {
        $model = new ClientModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->whitelist($this->request->getPost(), [
            'client_code','company_name','contact_name','mobile','alt_mobile',
            'email','gst_no','pan_no','address','city','state','pincode',
            'credit_limit','credit_days','status',
            'portal_enabled','kyc_status','portal_notes','account_manager_user_id',
            'gst_treatment','tds_rate',
            'detention_free_hours_loading','detention_free_hours_unloading','detention_rate_per_hour',
            'is_msme',
        ]);
        $data['is_msme'] = !empty($data['is_msme']) ? 1 : 0;
        if (empty($data['gst_treatment']) || !array_key_exists($data['gst_treatment'], ClientModel::GST_TREATMENTS)) {
            $data['gst_treatment'] = 'fcm5';
        }
        $data['client_code'] = !empty($data['client_code']) ? $data['client_code'] : $model->nextCode();
        $data['created_by']  = $this->auth->id();
        $data['status']         = !empty($data['status']) ? 1 : 0;
        $data['portal_enabled'] = !empty($data['portal_enabled']) ? 1 : 0;
        if (empty($data['kyc_status']) || !in_array($data['kyc_status'], ClientModel::KYC_STATUSES, true)) {
            $data['kyc_status'] = 'Pending';
        }
        $data['account_manager_user_id'] = !empty($data['account_manager_user_id']) ? (int) $data['account_manager_user_id'] : null;
        $model->insert($data);
        $newId = (int) \Config\Database::connect()->insertID();

        // Notify the account manager (if set) + everyone who manages clients + admin.
        $title = 'New client — ' . ($data['company_name'] ?? ('#' . $newId));
        $link  = site_url('clients/' . $newId);
        if (!empty($data['account_manager_user_id'])) {
            \App\Libraries\Notify::toUser((int) $data['account_manager_user_id'],
                'Client assigned to you — ' . ($data['company_name'] ?? ''), null, $link,
                ['type' => 'client_new', 'icon' => 'building-check']);
        }
        \App\Libraries\Notify::toPermission('clients', 'can_view', $title,
            trim(($data['city'] ?? '') . ' ' . ($data['state'] ?? '')) ?: null, $link,
            ['type' => 'client_new', 'icon' => 'building-add']);

        return redirect()->to(site_url('clients'))->with('success', 'Client added.');
    }

    /** Pulls only explicitly-named keys from $post — defends against mass-assignment. */
    private function whitelist(array $post, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) if (array_key_exists($k, $post)) $out[$k] = $post[$k];
        return $out;
    }

    public function edit(int $id)
    {
        $row = (new ClientModel())->find($id);
        if (!$row) return redirect()->to(site_url('clients'))->with('error', 'Not found.');
        return $this->render('clients/form', [
            'pageTitle' => 'Edit Client',
            'row'       => $row,
            'managers'  => $this->managerOptions(),
        ]);
    }

    /** Account-manager dropdown source: active staff users with names. */
    private function managerOptions(): array
    {
        return \Config\Database::connect()
            ->table('users')
            ->select('id, name')
            ->where('status', 1)
            ->where('deleted_at', null)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    public function update(int $id)
    {
        $model = new ClientModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->whitelist($this->request->getPost(), [
            'company_name','contact_name','mobile','alt_mobile',
            'email','gst_no','pan_no','address','city','state','pincode',
            'credit_limit','credit_days','status',
            'portal_enabled','kyc_status','portal_notes','account_manager_user_id',
            'gst_treatment','tds_rate',
            'detention_free_hours_loading','detention_free_hours_unloading','detention_rate_per_hour',
            'is_msme',
        ]);
        $data['is_msme'] = !empty($data['is_msme']) ? 1 : 0;
        if (empty($data['gst_treatment']) || !array_key_exists($data['gst_treatment'], ClientModel::GST_TREATMENTS)) {
            $data['gst_treatment'] = 'fcm5';
        }
        $data['updated_by']     = $this->auth->id();
        $data['status']         = !empty($data['status']) ? 1 : 0;
        $data['portal_enabled'] = !empty($data['portal_enabled']) ? 1 : 0;
        if (empty($data['kyc_status']) || !in_array($data['kyc_status'], ClientModel::KYC_STATUSES, true)) {
            $data['kyc_status'] = 'Pending';
        }
        $data['account_manager_user_id'] = !empty($data['account_manager_user_id']) ? (int) $data['account_manager_user_id'] : null;
        $model->update($id, $data);
        return redirect()->to(site_url('clients'))->with('success', 'Client updated.');
    }

    public function delete(int $id)
    {
        (new ClientModel())->delete($id);
        return redirect()->to(site_url('clients'))->with('success', 'Client removed.');
    }
}

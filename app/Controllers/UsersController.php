<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;

class UsersController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $model  = new UserModel();
        // Hide super-admins from the tenant admin's user list — they should be invisible.
        $query  = $model->select('users.*, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.is_super_admin', 0)
            ->orderBy('users.id', 'DESC');

        if ($search !== '') {
            $query->groupStart()
                ->like('users.name', $search)
                ->orLike('users.email', $search)
                ->orLike('users.mobile', $search)
                ->groupEnd();
        }

        return $this->render('users/index', [
            'pageTitle' => 'User Master [Administration] — List',
            'users'     => $query->paginate(20),
            'pager'     => $model->pager,
            'search'    => $search,
        ], retroFixedShell: true);
    }

    public function create()
    {
        return $this->render('users/form', [
            'pageTitle' => 'User Master [Administration] — New',
            'user'      => null,
            'roles'     => (new RoleModel())->where('status', 1)->orderBy('role_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[120]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'role_id'  => 'required|integer',
            'mobile'   => 'permit_empty|max_length[20]',
            'password' => 'required|min_length[6]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = new UserModel();
        $newId = $model->insert([
            'role_id'       => (int) $this->request->getPost('role_id'),
            'name'          => $this->request->getPost('name'),
            'email'         => $this->request->getPost('email'),
            'mobile'        => $this->request->getPost('mobile'),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'status'        => $this->request->getPost('status') ? 1 : 0,
            'created_by'    => $this->auth->id(),
        ]);
        if (!$newId) {
            return redirect()->back()->withInput()->with('error',
                'Create failed: ' . implode(' ', $model->errors() ?: ['unknown DB error']));
        }
        return redirect()->to(site_url('users'))->with('success', 'User created.');
    }

    public function edit(int $id)
    {
        $user = (new UserModel())->find($id);
        if (!$user || (int) ($user['is_super_admin'] ?? 0) === 1) {
            // Pretend it doesn't exist so tenant admin can't probe ids
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        return $this->render('users/form', [
            'pageTitle' => 'User Master [Administration] — Edit',
            'user'      => $user,
            'roles'     => (new RoleModel())->where('status', 1)->orderBy('role_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function update(int $id)
    {
        $existing = (new UserModel())->find($id);
        if (!$existing || (int) ($existing['is_super_admin'] ?? 0) === 1) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        $rules = [
            'name'    => 'required|min_length[2]|max_length[120]',
            'email'   => "required|valid_email|is_unique[users.email,id,$id]",
            'role_id' => 'required|integer',
            'mobile'  => 'permit_empty|max_length[20]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = new UserModel();
        $data  = [
            'role_id'    => (int) $this->request->getPost('role_id'),
            'name'       => $this->request->getPost('name'),
            'email'      => $this->request->getPost('email'),
            'mobile'     => $this->request->getPost('mobile'),
            'status'     => $this->request->getPost('status') ? 1 : 0,
            'updated_by' => $this->auth->id(),
        ];
        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }
        $ok = $model->update($id, $data);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error',
                'Save failed: ' . implode(' ', $model->errors() ?: ['unknown DB error']));
        }
        return redirect()->to(site_url('users'))->with('success', 'User updated.');
    }

    public function delete(int $id)
    {
        if ($id === $this->auth->id()) {
            return redirect()->to(site_url('users'))->with('error', 'You cannot delete your own account.');
        }
        $existing = (new UserModel())->find($id);
        if (!$existing || (int) ($existing['is_super_admin'] ?? 0) === 1) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        (new UserModel())->delete($id);
        return redirect()->to(site_url('users'))->with('success', 'User removed.');
    }
}

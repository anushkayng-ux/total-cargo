<?php

namespace App\Controllers;

use App\Models\HelpTopicModel;
use App\Models\RoleModel;

class HelpController extends BaseController
{
    private function currentRoleKey(): string
    {
        $roleId = (int) ($this->auth->user()['role_id'] ?? 0);
        if (!$roleId) return 'all';
        $r = (new RoleModel())->find($roleId);
        return strtolower((string) ($r['role_key'] ?? 'all'));
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $allTopics = (new HelpTopicModel())->forRole($this->currentRoleKey());

        if ($search !== '') {
            $allTopics = array_values(array_filter($allTopics, function ($t) use ($search) {
                return stripos($t['title'], $search) !== false
                    || stripos($t['body_md'], $search) !== false;
            }));
        }

        $byCategory = [];
        foreach ($allTopics as $t) $byCategory[$t['category']][] = $t;

        return $this->render('help/index', [
            'pageTitle'  => 'Help',
            'byCategory' => $byCategory,
            'search'     => $search,
            'roleKey'    => $this->currentRoleKey(),
            'isAdmin'    => $this->auth->can('help', 'can_edit'),
        ]);
    }

    public function show(string $slug)
    {
        helper('markdown');
        $row = (new HelpTopicModel())->findBySlug($slug);
        if (!$row || !$row['is_published']) {
            return redirect()->to(site_url('help'))->with('error', 'Topic not found.');
        }

        // Other topics in same category for sidebar
        $siblings = (new HelpTopicModel())->where('category', $row['category'])
            ->where('is_published', 1)->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')->find();

        return $this->render('help/show', [
            'pageTitle' => $row['title'],
            'row'       => $row,
            'rendered'  => tpt_md_render((string) $row['body_md']),
            'siblings'  => $siblings,
            'isAdmin'   => $this->auth->can('help', 'can_edit'),
        ]);
    }

    // ── Admin CRUD (gated by 'help' module .can_add/.can_edit) ─────────

    public function adminIndex()
    {
        if (!$this->auth->can('help', 'can_edit') && !$this->auth->can('help', 'can_add')) {
            return redirect()->to(site_url('help'))->with('error', 'Not allowed.');
        }
        $rows = (new HelpTopicModel())->orderBy('category')->orderBy('sort_order')->find();
        return $this->render('help/admin/index', [
            'pageTitle' => 'Manage Help Topics',
            'rows'      => $rows,
        ]);
    }

    public function create()
    {
        if (!$this->auth->can('help', 'can_add')) return redirect()->to(site_url('help'))->with('error', 'Not allowed.');
        return $this->render('help/admin/form', [
            'pageTitle' => 'New help topic',
            'row'       => null,
        ]);
    }

    public function store()
    {
        if (!$this->auth->can('help', 'can_add')) return redirect()->to(site_url('help'))->with('error', 'Not allowed.');
        $rules = [
            'title'    => 'required|min_length[3]|max_length[200]',
            'category' => 'required|max_length[60]',
            'body_md'  => 'required',
        ];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));

        $slug = $this->request->getPost('slug');
        if (empty($slug)) $slug = $this->slugify((string) $this->request->getPost('title'));
        if ((new HelpTopicModel())->where('slug', $slug)->countAllResults() > 0) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        (new HelpTopicModel())->insert([
            'slug'             => $slug,
            'title'            => trim((string) $this->request->getPost('title')),
            'category'         => trim((string) $this->request->getPost('category')),
            'body_md'          => (string) $this->request->getPost('body_md'),
            'applicable_roles' => trim((string) $this->request->getPost('applicable_roles')) ?: 'all',
            'sort_order'       => (int) ($this->request->getPost('sort_order') ?: 100),
            'is_published'     => $this->request->getPost('is_published') ? 1 : 0,
            'updated_by'       => $this->auth->id(),
        ]);
        return redirect()->to(site_url('help/admin'))->with('success', 'Topic created.');
    }

    public function edit(int $id)
    {
        if (!$this->auth->can('help', 'can_edit')) return redirect()->to(site_url('help'))->with('error', 'Not allowed.');
        $row = (new HelpTopicModel())->find($id);
        if (!$row) return redirect()->to(site_url('help/admin'))->with('error', 'Not found.');
        return $this->render('help/admin/form', [
            'pageTitle' => 'Edit ' . $row['title'],
            'row'       => $row,
        ]);
    }

    public function update(int $id)
    {
        if (!$this->auth->can('help', 'can_edit')) return redirect()->to(site_url('help'))->with('error', 'Not allowed.');
        $rules = [
            'title'    => 'required|min_length[3]|max_length[200]',
            'category' => 'required|max_length[60]',
            'body_md'  => 'required',
        ];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));

        (new HelpTopicModel())->update($id, [
            'title'            => trim((string) $this->request->getPost('title')),
            'category'         => trim((string) $this->request->getPost('category')),
            'body_md'          => (string) $this->request->getPost('body_md'),
            'applicable_roles' => trim((string) $this->request->getPost('applicable_roles')) ?: 'all',
            'sort_order'       => (int) ($this->request->getPost('sort_order') ?: 100),
            'is_published'     => $this->request->getPost('is_published') ? 1 : 0,
            'updated_by'       => $this->auth->id(),
        ]);
        return redirect()->to(site_url('help/admin'))->with('success', 'Topic updated.');
    }

    public function delete(int $id)
    {
        if (!$this->auth->can('help', 'can_delete')) return redirect()->to(site_url('help'))->with('error', 'Not allowed.');
        (new HelpTopicModel())->delete($id);
        return redirect()->to(site_url('help/admin'))->with('success', 'Topic removed.');
    }

    private function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string) $s, '-');
    }
}

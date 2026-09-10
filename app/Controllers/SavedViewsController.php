<?php

namespace App\Controllers;

/**
 * Per-user saved filter views. JSON API only — the JS widget on each list
 * page hits these.
 *
 *   GET    /_views?page=leads               → array of saved views for current page
 *   POST   /_views/save                     → name, page, querystring
 *   POST   /_views/{id}/delete
 *
 * Storage: `user_prefs` keyed `saved_views:{page}` → JSON array.
 */
class SavedViewsController extends BaseController
{
    public function index()
    {
        $page = trim((string) $this->request->getGet('page'));
        if ($page === '') return $this->response->setJSON(['views' => []]);
        return $this->response->setJSON(['views' => $this->load($page)]);
    }

    public function save()
    {
        $name = trim((string) $this->request->getPost('name'));
        $page = trim((string) $this->request->getPost('page'));
        $qs   = trim((string) $this->request->getPost('querystring'));
        if ($name === '' || $page === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'name and page required']);
        }
        $views = $this->load($page);
        $views[] = ['id' => bin2hex(random_bytes(6)), 'name' => mb_substr($name, 0, 60), 'qs' => $qs];
        $this->store($page, $views);
        return $this->response->setJSON(['ok' => true, 'views' => $views]);
    }

    public function delete()
    {
        $id   = trim((string) $this->request->getPost('id'));
        $page = trim((string) $this->request->getPost('page'));
        if ($id === '' || $page === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'id and page required']);
        }
        $views = array_values(array_filter($this->load($page), fn($v) => ($v['id'] ?? '') !== $id));
        $this->store($page, $views);
        return $this->response->setJSON(['ok' => true, 'views' => $views]);
    }

    private function load(string $page): array
    {
        $row = \Config\Database::connect()->table('user_prefs')
            ->where(['user_id' => $this->auth->id(), 'pref_key' => "saved_views:$page"])
            ->get()->getRowArray();
        if (!$row) return [];
        $data = json_decode((string) $row['pref_value'], true);
        return is_array($data) ? $data : [];
    }

    private function store(string $page, array $views): void
    {
        \Config\Database::connect()->table('user_prefs')->replace([
            'user_id'    => $this->auth->id(),
            'pref_key'   => "saved_views:$page",
            'pref_value' => json_encode($views),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

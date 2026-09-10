<?php

namespace App\Models;

class HelpTopicModel extends BaseModel
{
    protected $table         = 'help_topics';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'slug', 'title', 'category', 'body_md',
        'applicable_roles', 'sort_order', 'is_published', 'updated_by',
    ];

    /** Topics published + relevant to the given role_key (or 'all'). Ordered by category, then sort_order. */
    public function forRole(string $roleKey): array
    {
        $all = $this->where('is_published', 1)
            ->where('deleted_at', null)
            ->orderBy('category', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->find();

        return array_values(array_filter($all, function ($t) use ($roleKey) {
            $roles = array_map('trim', explode(',', strtolower((string) $t['applicable_roles'])));
            return in_array('all', $roles, true) || in_array(strtolower($roleKey), $roles, true);
        }));
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('deleted_at', null)->first();
    }
}

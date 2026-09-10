<?php

namespace App\Models;

class TripExpenseCategoryModel extends BaseModel
{
    protected $table          = 'trip_expense_categories';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = false;
    protected $useSoftDeletes = false;
    protected $allowedFields  = ['name', 'default_is_billable', 'sort_order', 'status'];

    public function active(): array
    {
        return $this->where('status', 1)->orderBy('sort_order')->orderBy('name')->findAll();
    }
}

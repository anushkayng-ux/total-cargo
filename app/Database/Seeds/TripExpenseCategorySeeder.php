<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TripExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Toll / FASTag',           0, 10],
            ['Diesel / Fuel',           0, 20],
            ['Driver Bhatta',           0, 30],
            ['Loading Charges',         1, 40],
            ['Unloading Charges',       1, 50],
            ['Detention',               1, 60],
            ['Weighbridge',             1, 70],
            ['Parking',                 0, 80],
            ['Maamul / Border',         0, 90],
            ['Escort / Pilot',          1, 100],
            ['POD Courier',             0, 110],
            ['Cleaning / Tarpaulin',    0, 120],
            ['Multi-point Charges',     1, 130],
            ['Breakdown Recovery',      0, 140],
            ['Miscellaneous',           0, 150],
        ];
        $db = \Config\Database::connect();
        $b  = $db->table('trip_expense_categories');
        foreach ($categories as $c) {
            if ($b->where('name', $c[0])->countAllResults(false) === 0) {
                $b->insert([
                    'name' => $c[0], 'default_is_billable' => $c[1], 'sort_order' => $c[2], 'status' => 1,
                ]);
            }
        }
    }
}

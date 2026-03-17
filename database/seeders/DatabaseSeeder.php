<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\GarmentCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        Admin::firstOrCreate(
            ['email' => 'admin@dazzledrys.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'role'     => 'super_admin',
            ]
        );

        // Default garment categories
        $categories = [
            ['name' => 'Shirts & Tops',   'icon' => 'shirt',    'sort_order' => 1],
            ['name' => 'Pants & Trousers', 'icon' => 'pants',    'sort_order' => 2],
            ['name' => 'Jackets & Coats',  'icon' => 'jacket',   'sort_order' => 3],
            ['name' => 'Ethnic Wear',      'icon' => 'saree',    'sort_order' => 4],
            ['name' => 'Bed Linen',        'icon' => 'bed',      'sort_order' => 5],
            ['name' => 'Curtains',         'icon' => 'curtain',  'sort_order' => 6],
            ['name' => 'Woollens',         'icon' => 'sweater',  'sort_order' => 7],
            ['name' => 'Others',           'icon' => 'others',   'sort_order' => 8],
        ];

        foreach ($categories as $cat) {
            GarmentCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}

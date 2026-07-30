<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booth;
use App\Models\Exhibition;

class BoothSeeder extends Seeder
{
    public function run(): void
    {
        $exhibitions = Exhibition::all();

        foreach ($exhibitions as $exhibition)
        {
            for ($i = 1; $i <= 4; $i++)
            {
                Booth::create([
                    'exhibition_id' => $exhibition->id,
                    'number'        => 'B-' . $i, // رقم الجناح
                    'area'          => rand(10, 50), // مساحة عشوائية
                    'status_inv'    => 'available',
                    'status'        => 'available',
                    'price'         => rand(100, 500), // سعر عشوائي
                    'location'      => 'Hall ' . chr(64 + $i), // Hall A, B, C, D
                    'services'      => json_encode(['Electricity', 'Internet']),
                    'amenities'     => json_encode(['Chairs', 'Table', 'Lighting']),
                    'image'         => 'default_image/default.png',
                    'map_x'         => rand(10, 100),
                    'map_y'         => rand(10, 100),
                    'map_z'         => rand(1, 10),
                ]);
            }
        }
    }
}

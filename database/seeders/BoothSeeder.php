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
        $statusOptions = ['available', 'booked'];

        foreach ($exhibitions as $exhibition) {
            // إنشاء عدد عشوائي من الأكشاك لكل معرض (بين 2 و 10 أكشاك)
            $boothsCount = rand(2, 10);

            for ($i = 1; $i <= $boothsCount; $i++) {
                Booth::create([
                    'exhibition_id' => $exhibition->id,
                    'number' => 'B-' . $i,
                    'area' => rand(10, 50),
                    'status_inv' => $statusOptions[array_rand($statusOptions)],
                    'status' => 'available',
                    'price' => rand(100, 500),
                    'location' => 'Hall ' . chr(64 + $i),
                    'services' => ['Electricity', 'Internet'],
                    'amenities' => ['Chairs', 'Table', 'Lighting'],
                    'image' => 'default_image/default.png',
                    'map_x' => rand(10, 100),
                    'map_y' => rand(10, 100),
                    'map_z' => rand(1, 10),
                ]);
            }
        }
    }
}


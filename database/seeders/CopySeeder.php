<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exhibition;
use App\Models\Copy;

class CopySeeder extends Seeder
{
    public function run(): void
    {
        $exhibitions = Exhibition::all();

        if ($exhibitions->isEmpty()) {
            return;
        }

        foreach ($exhibitions as $exhibition) {

            // إذا كانت النسخة موجودة مسبقًا، لا نكررها
            $existingCopy = Copy::where('exhibition_id', $exhibition->id)
                                ->where('copy_status', 'active')
                                ->first();

            if ($existingCopy) {
                continue;
            }

            Copy::create([
                'exhibition_id' => $exhibition->id,

                'year' => date('Y'),
                'start_date' => $exhibition->start_date,
                'end_date' => $exhibition->end_date,
                'copy_status' => 'active',

                'announced' => true,
                'total_booths' => $exhibition->total_booths,
                'booked_booths' => $exhibition->total_booths - $exhibition->available_booths,
                'available_booths' => $exhibition->available_booths,
                'pending_requests' => 0,

                'visitor_count' => $exhibition->visitors_count,
                'expected_visitors' => $exhibition->visitors_count + rand(200, 1000),

                'turnout_percent' => $exhibition->total_booths > 0
                    ? round(($exhibition->visitors_count / max($exhibition->total_booths, 1)) * 100, 2)
                    : 0,

                'expected_turnout_percent' => rand(40, 90),

                'revenue' => rand(50000, 150000),
                'expected_revenue' => rand(60000, 180000),

                'staff_count' => rand(10, 50),
                'sponsorship_percent' => rand(10, 70),

                'final_booked_booths' => $exhibition->total_booths - $exhibition->available_booths,
            ]);

        }
    }
}

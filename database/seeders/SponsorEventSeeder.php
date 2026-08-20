<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SponsorEvent;
use App\Models\Exhibition;
use Carbon\Carbon;

class SponsorEventSeeder extends Seeder
{
    public function run(): void
    {
        $exhibitions = Exhibition::all();

        if ($exhibitions->isEmpty()) {
            return;
        }

        foreach ($exhibitions as $exhibition) {

            // إنشاء 2 فعاليات لكل معرض
            for ($i = 1; $i <= 2; $i++) {

                $start = Carbon::parse($exhibition->start_date)->addDays($i);
                $end = (clone $start)->addHours(2);

                SponsorEvent::create([
                    'exhibition_id' => $exhibition->id,

                    'name' => "Sponsor Event $i for {$exhibition->name}",
                    'type' => 'competition', // أو workshop أو show
                    'place' => "Hall $i",

                    'start_time' => $start->format('Y-m-d H:i:s'),
                    'end_time' => $end->format('Y-m-d H:i:s'),

                    'description' => "This is a generated sponsor event number $i for exhibition {$exhibition->name}.",

                    'ticket_type' => $i === 1 ? 'invitation' : 'paid',
                    'ticket_price' => $i === 1 ? 0 : rand(20, 100),

                    'max_participants' => rand(50, 200),

                    'duration_days' => 1,
                    'duration_options' => [
                        ['day' => 1, 'price' => 100]
                    ],

                    'daily_price' => rand(100, 500),

                    'registered_count' => rand(0, 50),
                    'scanned_count' => rand(0, 30),

                    'status' => 'upcoming',
                    'copy_status' => 'draft',

                    'publish_date' => null,
                ]);
            }

        }
    }
}

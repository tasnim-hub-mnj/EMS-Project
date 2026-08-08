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

        foreach ($exhibitions as $exhibition)
        {

            // 1
            SponsorEvent::create([
                'exhibition_id' => $exhibition->id,
                'name' => 'Opening Ceremony',
                'type' => 'food&hospitality',
                'by' => 'Event Committee',
                'place' => 'Main Hall',
                'start_time' => Carbon::now()->addDays(5),
                'end_time' => Carbon::now()->addDays(5)->addHours(2),
                'description' => 'A grand opening ceremony for the exhibition.',
                'is_general_invitation' => true,
                'ticket_price' => 0,
                'max_participants' => 200,
                'duration_days' => 1,
                'duration_options' => ([
                    ['label'=>'one day','day' => 1, 'price' => 0]
                ]),
                'daily_price' => null,
                'registered_count' => 0,
                'total_seats' => 200,
                'scanned_count' => 0,
                'status' => 'upcoming',
                'copy_status' => 'active',
                'publish_date' => Carbon::now(),
            ]);

            // 2
            SponsorEvent::create([
                'exhibition_id' => $exhibition->id,
                'name' => 'Brand Showcase',
                'type' => 'Showcase',
                'by' => 'Top Sponsors',
                'place' => 'Showroom A',
                'start_time' => Carbon::now()->addDays(6),
                'end_time' => Carbon::now()->addDays(6)->addHours(3),
                'description' => 'A special showcase event for premium brands.',
                'is_general_invitation' => false,
                'ticket_price' => 25,
                'max_participants' => 150,
                'duration_days' => 2,
                'duration_options' => ([
                    ['label'=>'one day','day' => 1, 'price' => 25],
                    ['label'=>'full event','day' => 2, 'price' => 40],
                ]),
                'daily_price' => 20,
                'registered_count' => 0,
                'total_seats' => 150,
                'scanned_count' => 0,
                'status' => 'upcoming',
                'copy_status' => 'active',
                'publish_date' => Carbon::now(),
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exhibition;
use App\Models\Organizer;

class ExhibitionSeeder extends Seeder
{
    public function run(): void
    {
        $organizers = Organizer::all();

        $exhibitionsData =
        [
            [
                'name' => 'Tech Innovators Expo',
                'type' => 'Technology',
                'city' => 'Damascus',
                'location' => 'Expo Center Damascus',
                'sectors' => ['AI', 'Robotics', 'Software'],
                'extra_services' => ['VIP Lounge', 'Free WiFi'],
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-05',
                'description' => 'A leading exhibition for technology and innovation.',
                'map' => ['hall1' => 'A1', 'hall2' => 'B3'],
                'working_hours' => 8.5,
                'image' => 'default_image/exhibition.png',
            ],
            [
                'name' => 'Food & Beverage Festival',
                'type' => 'Food',
                'city' => 'Aleppo',
                'location' => 'Aleppo International Fairgrounds',
                'sectors' => ['Snacks', 'Drinks', 'Organic'],
                'extra_services' => ['Kids Area', 'Live Cooking Shows'],
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-15',
                'description' => 'A vibrant festival showcasing food and beverage brands.',
                'map' => ['zoneA' => 'Food Court', 'zoneB' => 'Live Stage'],
                'working_hours' => 10,
                'image' => 'default_image/exhibition.png',
            ],
            [
                'name' => 'Fashion & Style Week',
                'type' => 'Fashion',
                'city' => 'Latakia',
                'location' => 'Latakia Exhibition Hall',
                'sectors' => ['Clothing', 'Accessories', 'Design'],
                'extra_services' => ['Photo Studio', 'VIP Fashion Shows'],
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-25',
                'description' => 'A glamorous week dedicated to fashion and style.',
                'map' => ['runway' => 'Main Hall', 'booths' => 'C2'],
                'working_hours' => 7,
                'image' => 'default_image/exhibition.png',
            ],
            [
                'name' => 'Startup & Business Summit',
                'type' => 'Business',
                'city' => 'Homs',
                'location' => 'Homs Business Center',
                'sectors' => ['Startups', 'Finance', 'Marketing'],
                'extra_services' => ['Mentorship Rooms', 'Pitch Stage'],
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-03',
                'description' => 'A summit for entrepreneurs and business leaders.',
                'map' => ['stage' => 'Hall A', 'meetingRooms' => 'Hall B'],
                'working_hours' => 9,
                'image' => 'default_image/exhibition.png',
            ],
            [
                'name' => 'Art & Culture Exhibition',
                'type' => 'Art',
                'city' => 'Tartous',
                'location' => 'Tartous Cultural Center',
                'sectors' => ['Painting', 'Sculpture', 'Handcrafts'],
                'extra_services' => ['Workshops', 'Live Art Shows'],
                'start_date' => '2026-12-05',
                'end_date' => '2026-12-10',
                'description' => 'An exhibition celebrating art and culture.',
                'map' => ['gallery1' => 'G1', 'gallery2' => 'G2'],
                'working_hours' => 6,
                'image' => 'default_image/exhibition.png',
            ],
        ];

        foreach ($organizers as $index => $org)
        {
            $data = $exhibitionsData[$index];

            Exhibition::create([
                'organizer_id' => $org->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'location' => $data['location'],
                'description' => $data['description'],
                'city' => $data['city'],
                'status' => 'upcoming',
                'copy_status' => 'draft',
                'available_booths' => 0,
                'total_booths' => 0,
                'total_sponser_events' => 0,
                'visitors_count' => 0,
                'sectors' => json_encode($data['sectors']),
                'extra_services' => json_encode($data['extra_services']),
                'is_paid' => false,
                'ticket_price' => null,
                'map' => json_encode($data['map']),
                'working_hours' => $data['working_hours'],
                'image' => $data['image'],
            ]);
        }
    }
}

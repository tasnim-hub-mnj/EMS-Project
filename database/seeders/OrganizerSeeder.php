<?php

namespace Database\Seeders;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizers = User::where('role', 'organizer')->get();

        foreach ($organizers as $index => $user)
        {
            Organizer::create([
                'user_id'       => $user->id,
                'company_name'  => "Organizer Company " . ($index + 1),
                'category'      => 'Technology',
                'headquarters'  => 'Damascus - Syria',
                'reg_number'    => 1000 + $index,
                'location'      => 'Expo Center Damascus',
                'logo'          => 'default_image/organizer_logo.png',
                'legal_document' => 'contract_' . ($index + 1) . '.pdf',
                'description'   => 'Professional organizer for exhibitions and events.',
            ]);
        }
    }
}

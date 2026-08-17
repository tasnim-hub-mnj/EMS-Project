<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // UserSeeder::class,
            // OrganizerSeeder::class,
            // ExhibitionSeeder::class,
            // CopySeeder::class,
            // BoothSeeder::class,
            // BoothBookingSeeder::class,
            // SponsorEventSeeder::class,
            // EventSeeder::class,
            // TicketEventSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizers_user =
            [
                [
                    'email' => 'organizer1@example.com',
                    'phone' => '0991000001',
                    'password' => Hash::make('password123'),
                    'role' => 'organizer',
                    'status' => 'approved',
                    'is_verified' => true,
                ],
                [
                    'email' => 'organizer2@example.com',
                    'phone' => '0991000002',
                    'password' => Hash::make('password123'),
                    'role' => 'organizer',
                    'status' => 'approved',
                    'is_verified' => true,
                ],
                [
                    'email' => 'organizer3@example.com',
                    'phone' => '0991000003',
                    'password' => Hash::make('password123'),
                    'role' => 'organizer',
                    'status' => 'approved',
                    'is_verified' => false,
                ],
                [
                    'email' => 'organizer4@example.com',
                    'phone' => '0991000004',
                    'password' => Hash::make('password123'),
                    'role' => 'organizer',
                    'status' => 'approved',
                    'is_verified' => true,
                ],
                [
                    'email' => 'organizer5@example.com',
                    'phone' => '0991000005',
                    'password' => Hash::make('password123'),
                    'role' => 'organizer',
                    'status' => 'approved',
                    'is_verified' => false,
                ],
            ];

        foreach ($organizers_user as $org) 
        {
            User::create($org);
        }
        //========================================================
        // 2. الزوّار
        $visitors_user = [
            [
                'email' => 'visitor1@example.com',
                'phone' => '0988000001',
                'password' => Hash::make('password123'),
                'role' => 'visitor',
                'status' => 'approved',
                'is_verified' => true,
            ],
            [
                'email' => 'visitor2@example.com',
                'phone' => '0988000002',
                'password' => Hash::make('password123'),
                'role' => 'visitor',
                'status' => 'approved',
                'is_verified' => true,
            ],
        ];

        foreach ($visitors_user as $visitor) 
        {
            User::create($visitor);
        }
        //========================================================
        // 3. investor
        $investor_user = 
        [
            [
                'email' => 'investor1@example.com',
                'phone' => '0988044001',
                'password' => Hash::make('password123'),
                'role' => 'investor',
                'status' => 'approved',
                'is_verified' => true,
            ],
            [
                'email' => 'investor2@example.com',
                'phone' => '0988044002',
                'password' => Hash::make('password123'),
                'role' => 'investor',
                'status' => 'approved',
                'is_verified' => true,
            ],
        ];

        foreach ($investor_user as $investor) 
        {
            User::create($investor);
        }
    }

}


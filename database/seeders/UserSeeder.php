<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ================================
        // 1. Super Admin
        // ================================
        User::create([
            'email' => 'superadmin@example.com',
            'phone' => '0999000000',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        // ================================
        // 2. Organizers
        // ================================
        $organizers = [
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

        foreach ($organizers as $org) {
            User::create($org);
        }

        // ================================
        // 3. Investors
        // ================================
        $investors = [
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

        foreach ($investors as $inv) {
            User::create($inv);
        }

        // ================================
        // 4. Visitors
        // ================================
        $visitors = [
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

        foreach ($visitors as $visitor) {
            User::create($visitor);
        }

        // ================================
        // 5. Staff
        // ================================
        $staff = [
            [
                'email' => 'staff1@example.com',
                'phone' => '0977000001',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'status' => 'approved',
                'is_verified' => true,
            ],
            [
                'email' => 'staff2@example.com',
                'phone' => '0977000002',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'status' => 'approved',
                'is_verified' => true,
            ],
        ];

        foreach ($staff as $s) {
            User::create($s);
        }

        // ================================
        // 6. External Team Leaders (اختياري)
        // ================================
        // $externalTeamLeaders = [
        //     [
        //         'email' => 'external1@example.com',
        //         'phone' => '0973000001',
        //         'password' => Hash::make('password123'),
        //         'role' => 'external_team_leader',
        //         'status' => 'approved',
        //         'is_verified' => true,
        //     ],
        // ];

        // foreach ($externalTeamLeaders as $ext) {
        //     User::create($ext);
        // }
    }
}

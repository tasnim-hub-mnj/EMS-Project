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
                'email'      => 'organizer1@example.com',
                'phone'      => '0991000001',
                'password'   => Hash::make('password123'),
                'role'       => 'organizer',
                'status'     => 'approved',
                'is_verified'=> true,
                'fcm_token'  => 'token_organizer_1',
            ],
            [
                'email'      => 'organizer2@example.com',
                'phone'      => '0991000002',
                'password'   => Hash::make('password123'),
                'role'       => 'organizer',
                'status'     => 'approved',
                'is_verified'=> true,
                'fcm_token'  => 'token_organizer_2',
            ],
            [
                'email'      => 'organizer3@example.com',
                'phone'      => '0991000003',
                'password'   => Hash::make('password123'),
                'role'       => 'organizer',
                'status'     => 'approved',
                'is_verified'=> false,
                'fcm_token'  => 'token_organizer_3',
            ],
            [
                'email'      => 'organizer4@example.com',
                'phone'      => '0991000004',
                'password'   => Hash::make('password123'),
                'role'       => 'organizer',
                'status'     => 'approved',
                'is_verified'=> true,
                'fcm_token'  => 'token_organizer_4',
            ],
            [
                'email'      => 'organizer5@example.com',
                'phone'      => '0991000005',
                'password'   => Hash::make('password123'),
                'role'       => 'organizer',
                'status'     => 'approved',
                'is_verified'=> false,
                'fcm_token'  => 'token_organizer_5',
            ],
        ];

        foreach ($organizers_user as $org)
        {
            User::create($org);
        }

    }
}

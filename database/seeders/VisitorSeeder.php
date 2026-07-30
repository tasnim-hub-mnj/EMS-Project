<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VisitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $visitors = User::where('role', 'visitor')->get();

        foreach ($visitors as $index => $user) {
            Visitor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => 'زائر ' . ($index + 1),
                    'last_name' => 'التجريبي',
                    'profession' => 'تطوير البرمجيات',
                    'city' => 'دمشق',
                    'hobby' => 'القراءة والتكنولوجيا',
                    'interests' => ['تكنولوجيا', 'معارض'],
                    'avatar_url' => null,
                ]
            );
        }
    }
}


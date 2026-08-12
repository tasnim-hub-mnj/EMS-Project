<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoothBooking;
use App\Models\Booth;
use App\Models\Investor;
use App\Models\User;

class BoothBookingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب أول مستثمر أو إنشاء واحد جديد مرتبط بـ User
        $investor = Investor::first();

        // if (!$investor) {
        //     $user = User::first() ?? User::create([
        //         'name' => 'Investor User',
        //         'email' => 'investor_test@example.com',
        //         'password' => bcrypt('password'),
        //     ]);

        //     $investor = Investor::create([
        //         'user_id' => $user->id,
        //         'company_name' => 'Default Company Ltd',
        //         'location' => 'Main Office',
        //     ]);
        // }

        // 2. جلب الأكشاك المتاحة
        $booths = Booth::all();

        if ($booths->isEmpty())
        {
            $this->command->warn('لا توجد أكشاك في جدول booths، يرجى تشغيل BoothSeeder أولاً.');
            return;
        }

        // 3. إنشاء حجوزات الأكشاك
        foreach ($booths as $booth)
        {
            BoothBooking::firstOrCreate(
                ['booth_id' => $booth->id],
                [
                    'investor_id' => $investor->id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays(5)->toDateString(),
                    'days' => 5,
                    'total_price' => 1000.00,
                    'status' => 'approved',
                ]
            );
        }
    }
}

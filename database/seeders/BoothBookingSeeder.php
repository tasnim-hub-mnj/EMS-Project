<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoothBooking;
use App\Models\Booth;
use App\Models\Copy;
use App\Models\Investor;
use App\Models\User;

class BoothBookingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب أول مستثمر أو إنشاء واحد جديد مرتبط بـ User
        $investor = Investor::first();

        if (!$investor) 
        {
            $user = User::where('role','investor')->first();

            $investor = Investor::create([
                'user_id' => $user->id,
                'company_name' => 'Default Company Ltd',
                'location' => 'Main Office',
            ]);
        }

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
            $copy_id = Copy::where('exhibition_id', $booth->exhibition_id)
                    ->where('copy_status', 'active')
                    ->first()?->id;

            BoothBooking::firstOrCreate(
                [
                    'booth_id' => $booth->id,
                    'copy_id' => $copy_id,
                ],
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

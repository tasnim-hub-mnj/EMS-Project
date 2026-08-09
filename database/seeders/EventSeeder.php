<?php

namespace Database\Seeders;

use App\Models\BoothBooking;
use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // جلب جميع حجوزات الأكشاك مع العلاقة كشك
        $bookings = BoothBooking::with('booth')->get();

        if ($bookings->isEmpty()) {
            $this->command->warn('لم يتم العثور على حجوزات أكشاك في جدول BoothBooking.');
            return;
        }

        foreach ($bookings as $booking) {
            if (!$booking->booth) {
                continue;
            }

            Event::updateOrCreate(
                ['booth_booking_id' => $booking->id],
                [
                    'name' => 'Booth ' . ($booking->booth->number ?? $booking->booth->id) . ' Special Event',
                    'type' => 'Seminar',
                    'time' => '10:00:00',
                    'place' => $booking->booth->location ?? 'Main Hall',
                    'duration_days' => $booking->days ?? 1,
                    'description' => 'A special event held during the booth booking period.',
                    'video_promo_url' => null,
                    'is_general_invitation' => true,
                    'has_bookable_seats' => true,
                    'max_participants' => 50,
                    'requires_booking' => false,
                    'ticket_price' => 0,
                    'registered_count' => 30,
                    'total_seats' => 50,
                    'scanned_count' => 0,
                    'status' => 'upcoming',
                    'current_day' => 1,
                ]
            );
        }
    }
}


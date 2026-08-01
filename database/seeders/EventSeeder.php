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
        // Fetch all booth bookings
        $bookings = BoothBooking::with('booth')->get();

        foreach ($bookings as $booking)
        {
            Event::create([
                'booth_booking_id'   => $booking->id,

                // Basic event info
                'name'               => 'Booth ' . $booking->booth->number . ' Special Event',
                'type'               => 'Seminar',

                // Dates & time
                'start_date'         => $booking->start_date,
                'end_date'           => $booking->end_date,
                'time'               => '10:00:00',

                // Location
                'place'              => $booking->booth->location,

                // Duration
                'duration_days'      => $booking->days,

                // Description
                'description'        => 'A special event held during the booth booking period.',

                // Promo video (optional)
                'video_promo_url'    => null,

                // Invitation settings
                'is_general_invitation' => true,

                // Seat settings
                'has_bookable_seats' => false,
                'max_participants'   => null,
                'requires_booking'   => false,

                // Ticket settings
                'ticket_price'       => 0,
                'ticket_type'        => 'free',
                'free_ticket_limit'  => null,

                // Statistics
                'registered_count'   => 0,
                'total_seats'        => null,
                'scanned_count'      => 0,

                // Status
                'status'             => 'upcoming',
                'current_day'        => 1,

                // Daily attendees
                'daily_attendees'    => null,
            ]);
        }
    }
}


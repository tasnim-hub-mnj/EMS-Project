<?php

namespace App\Http\Controllers;

use App\Models\SponsorshipBooking;
use Illuminate\Support\Facades\Auth;

class SponsorEventController extends Controller
{
    public function getMySponsorshipAll()// عرض كل حجوزات الفعاليات الإعلانية للمستثمر
    {
        $investor = Auth::user()->investor;

        $bookings = SponsorshipBooking::with('sponsorEvent.exhibition')
            ->where('investor_id', $investor->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b)
            {
                return [
                    'id'            => $b->id,
                    'status'        => $b->status,
                    'price'         => $b->price,
                    'selected_days' => $b->selected_days,
                    'booked_at'     => $b->booked_at,

                    // event info
                    'event' => [
                        'id'          => $b->sponsorEvent?->id,
                        'name'        => $b->sponsorEvent?->name,
                        'type'        => $b->sponsorEvent?->type,
                        'date'        => $b->sponsorEvent?->date,
                        'start_time'  => $b->sponsorEvent?->start_time,
                        'end_time'    => $b->sponsorEvent?->end_time,
                        'place'       => $b->sponsorEvent?->place,
                        'exhibition'  => $b->sponsorEvent?->exhibition?->name,
                    ],

                    // analytics
                    'analytics' => [
                        'total_visitors'  => $b->total_visitors,
                        'total_attendees' => $b->total_attendees,
                    ]
                ];
            });

        return response()->json([
            'All' => $bookings
        ], 200);
    }
    //===============================================================
    public function showSponsorshipAdDetails($bookingId)//عرض تفاصيل حجز فعالية إعلانية معيّنة
    {
        $investor = Auth::user()->investor;

        $booking = SponsorshipBooking::with('sponsorEvent.exhibition')
            ->where('investor_id', $investor->id)
            ->findOrFail($bookingId);

        $event = $booking->sponsorEvent;

        return response()->json([

            // event details
            'event' => [
                'id'          => $event?->id,
                'name'        => $event?->name,
                'type'        => $event?->type,
                'date'        => $event?->date,
                'start_time'  => $event?->start_time,
                'end_time'    => $event?->end_time,
                'place'       => $event?->place,
                'listing_days'=> $event?->listing_days,
                'description' => $event?->description,
                'duration_options' => $event?->duration_options,
                'exhibition'  => [
                    'id'   => $event?->exhibition?->id,
                    'name' => $event?->exhibition?->name,
                ]
            ],

            // booking details
            'booking' => [
                'id'                    => $booking->id,
                'status'                => $booking->status,
                'price'                 => $booking->price,
                'booked_at'             => $booking->booked_at,
                'selected_days'         => $booking->selected_days,
                'selected_duration_label'=> $booking->selected_duration_label,
                'company_name'          => $booking->company_name,
                'company_website'       => $booking->company_website,
                'company_phone'         => $booking->company_phone,
                'product_names'         => $booking->product_names,
            ],

            // analytics
            'analytics' => [
                'total_visitors'  => $booking->total_visitors,
                'total_attendees' => $booking->total_attendees,
                'daily_visitors'  => $booking->daily_visitors,
                'current_day'     => $booking->current_day,
                'total_days'      => $booking->total_days,
            ]

        ], 200);
    }
}

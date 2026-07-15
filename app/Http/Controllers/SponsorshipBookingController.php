<?php

namespace App\Http\Controllers;

use App\Models\SponsorshipBooking;
use App\Models\SponsorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SponsorshipBookingController extends Controller
{
    public function getAllSponsorshipBookings($sponsor_event_id)//o
    {
        $sponsorship_bookings = SponsorshipBooking::where('sponsor_event_id', $sponsor_event_id)->get();

        $revenue = $sponsorship_bookings->clone()->whereIn('status', ['approved','ended'])->sum('amount');

        $sponsorship_bookings_data = $sponsorship_bookings->map(function ($sp_bo)
        {
            return
            [
                'company_name' => $sp_bo->investor->company_name,
                'status' => $sp_bo->status,
                'company_email' => $sp_bo->investor->email,
                'company_phone' => $sp_bo->investor->phone,
                'amount' => $sp_bo->amount,
                'description' => $sp_bo->description,
            ];

        });

        return response()->json([
            'revenue' => $revenue,
            'sponsorship_bookings' => $sponsorship_bookings_data
        ], 200);
    }
    //===============================================================
    public function approveBooking($booking_id)//قبول الرعاية/o
    {
        $booking = SponsorshipBooking::findOrFail($booking_id);

        if (in_array($booking->status, ['approved', 'ended']))
        {
            return response()->json([
                'message' => 'Cannot approve this booking'
            ], 403);
        }

        $booking->status = 'approved';
        $booking->save();

        return response()->json([
            'message' => 'Booking approved successfully',
            'booking' => $booking
        ], 200);
    }
    //===============================================================
    public function rejectBooking($booking_id)//رفض الرعاية/o
    {
        $booking = SponsorshipBooking::findOrFail($booking_id);

        if (in_array($booking->status, ['rejected', 'ended']))
        {
            return response()->json([
                'message' => 'Cannot reject this booking'
            ], 403);
        }

        $booking->status = 'rejected';
        $booking->save();

        return response()->json([
            'message' => 'Booking rejected successfully',
            'booking' => $booking
        ], 200);
    }
    //===============================================================
    //i
    //===============================================================
    //عرض تفاصيل الفعالية الاعلانية للحجز
    //===============================================================
    public function storeBooking(Request $request, $sponsor_event_id)//حجز رعاية/i
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_phone'=> 'nullable|string|max:255',
            'company_website'=> 'nullable|string|max:255',
            'product_names'=> 'nullable|string',
            'selected_duration_label'=> 'nullable|string',
            'selected_days'=> 'integer|min:1',
        ]);

        $investor = Auth::user()->investor;

        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

        $booking = SponsorshipBooking::create([
            'investor_id' => $investor->id,
            'sponsor_event_id' => $sponsor_event->id,
            'company_name' => $request->company_name,
            'company_phone'=> $request->company_phone,
            'company_website'=> $request->company_website,
            'product_names'=> $request->product_names,
            'selected_duration_label'=> $request->selected_duration_label,
            'selected_days'=> $request->selected_days,
            'price'=> 0,
            'status'=> 'pending',
            'booked_at'=> now()->format('Y-m-d'),
        ]);

        return response()->json([
            'message' => '',
            'booking' => $booking
        ], 201);
    }
    //===============================================================
    public function myBookings()//رعاياتي/i
    {
        $investor = Auth::user()->investor;

        $bookings = SponsorshipBooking::where('investor_id', $investor->id)->get();

        $bookings_data = $bookings->map(function($bo)
        {
            return
            [
                'id' => $bo->id,
                'name' => $bo->sponsorEvent->name,
                'type' => $bo->sponsorEvent->type,
                'exhibition_name' => $bo->sponsorEvent->exhibition->name,
                'start_date' => Carbon::parse($bo->sponsorEvent->start_time)->format('Y-m-d'),
                'place' => $bo->sponsorEvent->place,
                'status' => $bo->status,
                'days' => $bo->days,

                'registered_count' => $bo->sponsorEvent->registered_count,
                'scanned_count' => $bo->sponsorEvent->scanned_count,
                'amount' => $bo->amount,
            ];
        });

        return response()->json([
            'bookings' => $bookings_data
        ], 200);
    }
    //===============================================================
    public function showBooking($booking_id)//i
    {
        $investor = Auth::user()->investor;

        $booking = SponsorshipBooking::where('investor_id', $investor->id)->findOrFail($booking_id);

        $booking_data =
        [
            'id' => $booking->id,
            'name' => $booking->sponsorEvent->name,
            'status' => $booking->status,
            'day_rate' =>,

            'type' => $booking->sponsorEvent->type,
            'exhibition_name' => $booking->sponsorEvent->exhibition->name,
            'start_date' => Carbon::parse($booking->sponsorEvent->start_time)->format('Y-m-d'),
            'time' => Carbon::parse($booking->sponsorEvent->start_time)->format('h:i'),
            'place' => $booking->sponsorEvent->place,

            'days' => $booking->days,
            'amount' => $booking->amount,
            'booked_at' => $booking->booked_at,

            'registered_count' => $booking->sponsorEvent->registered_count,
            'scanned_count' => $booking->sponsorEvent->scanned_count,
        ];

        return response()->json([
            'booking' => $booking_data
        ], 200);
    }
    //===============================================================
    // public function showSponsorshipAdDetails($bookingId)//عرض تفاصيل حجز فعالية إعلانية معيّنة
    // {
    //     $investor = Auth::user()->investor;

    //     $booking = SponsorshipBooking::with('sponsorEvent.exhibition')
    //         ->where('investor_id', $investor->id)
    //         ->findOrFail($bookingId);

    //     $event = $booking->sponsorEvent;

    //     return response()->json([

    //         // event details
    //         'event' => [
    //             'id'          => $event?->id,
    //             'name'        => $event?->name,
    //             'type'        => $event?->type,
    //             'date'        => $event?->date,
    //             'start_time'  => $event?->start_time,
    //             'end_time'    => $event?->end_time,
    //             'place'       => $event?->place,
    //             'listing_days'=> $event?->listing_days,
    //             'description' => $event?->description,
    //             'duration_options' => $event?->duration_options,
    //             'exhibition'  => [
    //                 'id'   => $event?->exhibition?->id,
    //                 'name' => $event?->exhibition?->name,
    //             ]
    //         ] ,

    //         // booking details
    //         'booking' => [
    //             'id'                    => $booking->id,
    //             'status'                => $booking->status,
    //             'price'                 => $booking->price,
    //             'booked_at'             => $booking->booked_at,
    //             'selected_days'         => $booking->selected_days,
    //             'selected_duration_label'=> $booking->selected_duration_label,
    //             'company_name'          => $booking->company_name,
    //             'company_website'       => $booking->company_website,
    //             'company_phone'         => $booking->company_phone,
    //             'product_names'         => $booking->product_names,
    //         ],

    //         // analytics
    //         'analytics' => [
    //             'total_visitors'  => $booking->total_visitors,
    //             'total_attendees' => $booking->total_attendees,
    //             'daily_visitors'  => $booking->daily_visitors,
    //             'current_day'     => $booking->current_day,
    //             'total_days'      => $booking->total_days,
    //         ]

    //     ], 200);
    // }
}

<?php

namespace App\Http\Controllers;

use App\Models\SponsorshipBooking;
use App\Models\SponsorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SponsorshipBookingController extends Controller
{
    public function store(Request $request, $eventId)
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

        $event = SponsorEvent::findOrFail($eventId);

        $booking = SponsorshipBooking::create([
            'investor_id' => $investor->id,
            'sponsor_event_id' => $event->id,
            'company_name' => $request->company_name,
            'company_phone'=> $request->company_phone,
            'company_website'=> $request->company_website,
            'product_names'=> $request->product_names,
            'selected_duration_label'=> $request->selected_duration_label,
            'selected_days'=> $request->selected_days,
            'price'=> 0,
            'status'=> 'pending',
            'booked_at'=> now(),
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الحجز',
            'booking' => $booking
        ], 201);
    }

    public function myBookings()
    {
        $investor = Auth::user()->investor;

        $bookings = SponsorshipBooking::where('investor_id', $investor->id)
                                      ->with('sponsorEvent')
                                      ->get();

        return response()->json(['bookings' => $bookings], 200);
    }

    public function show($id)
    {
        $investor = Auth::user()->investor;

        $booking = SponsorshipBooking::where('investor_id', $investor->id)
                                     ->with('sponsorEvent')
                                     ->findOrFail($id);

        return response()->json(['booking' => $booking], 200);
    }

    public function cancel($id)
    {
        $investor = Auth::user()->investor;

        $booking = SponsorshipBooking::where('investor_id', $investor->id)
                                     ->findOrFail($id);

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'تم إلغاء الحجز'], 200);
    }
}

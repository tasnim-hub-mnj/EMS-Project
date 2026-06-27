<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingVisitorRequest;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    //عرض الحجوزات للمستخدم الحالي
    public function index(Request $request)
    {
        return $request->user()->bookings()->with(['exhibition', 'event'])->get();
    }
    //=================================

    //انشاء حجز جديد

    public function store(BookingVisitorRequest $request)
    {
        $data = $request->validated();

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'exhibition_id' => $data['type'] === 'exhibition' ? $data['exhibition_id'] : null,
            'event_id' => $data['type'] === 'event' ? $data['event_id'] : null,
            'type' => $data['type'],
            'amount' => $data['amount'] ?? 0,
            'status' => 'confirmed'
        ]);

        return response()->json([
            'message' => 'تم انشاء الحجز بنجاح',
            'data' => $booking,
        ], 201);
    }
    //=================================

    public function bookExhibition(Request $request)
    {
        $data = $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'ticket_type' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'exhibition_id' => $data['exhibition_id'],
            'type' => 'exhibition',
            'amount' => 0,
            'status' => 'confirmed',
            'booked_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم حجز تذكرة للمعرض بنجاح',
            'data' => $booking,
        ], 201);
    }
    //=================================

    public function bookEvent(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'event_id' => $data['event_id'],
            'type' => 'event',
            'amount' => 0,
            'status' => 'confirmed',
            'booked_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم حجز تذكرة للحدث بنجاح',
            'data' => $booking,
        ], 201);
    }
    //=================================

    // إلغاء حجز
    public function destroy(Request $request, $id)
    {
        $booking = Booking::where('user_id', $request->user()->id)->findOrFail($id);
        $booking->status = 'cancelled';
        $booking->save();
        return response()->json(['message' => 'Cancelled']);
    }
}

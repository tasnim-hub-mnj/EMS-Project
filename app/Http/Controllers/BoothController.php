<?php

namespace App\Http\Controllers;

use App\Models\BoothBooking;
use App\Models\Booth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class BoothController extends Controller
{
    public function boothBasicDetails($booth_id)//عرض جناح على لخريطة
    {
        $booth = Booth::select(
            'id',
            'number',
            'image_url',
            'area',
            'price',
            'status',
            'location',
            'amenities',
            'hall_id',
            'row',
            'col'
        )->find($booth_id);

        if (!$booth)
        {
            return response()->json(['message' => 'Booth not found'], 404);
        }

        return response()->json([
            'booth' => $booth
        ], 200);
    }
    //==============================================================
    public function bookBooth(Request $request,$booth_id)//حجز
    {
        $request->validate([
            'duration_days'  => 'required|integer|min:1',
            'notes'          => 'nullable|string',
            'screen_service' => 'boolean',
            'setup_service'  => 'boolean',
            'security_service' => 'boolean',
            'cleaning_service' => 'boolean',
        ]);

        $investor = Auth::user()->investor;
        $booth = Booth::find($booth_id);

        //التحقق من توفر البوث
        if ($booth->status !== 'available')
        {
            return response()->json([
                'message' => 'This booth is not available for booking.'
            ], 400);
        }

        //التحقق من عدم وجود حجز سابق
        $existing = BoothBooking::where('investor_id', $investor->id)
                        ->where('booth_id', $booth->id)
                        ->whereIn('status', ['pending', 'approved'])
                        ->first();

        if ($existing)
        {
            return response()->json([
                'message' => 'You already have a booking for this booth.'
            ], 400);
        }

        //إنشاء الحجز
        $booking = BoothBooking::create([
            'investor_id'      => $investor->id,
            'booth_id'         => $booth->id,
            'duration_days'    => $request->duration_days,
            'notes'            => $request->notes,
            'screen_service'   => $request->screen_service ?? false,
            'setup_service'    => $request->setup_service ?? false,
            'security_service' => $request->security_service ?? false,
            'cleaning_service' => $request->cleaning_service ?? false,
            'total_price'
        ]);

        //تغيير حالة البوث
        $booth->update(['status' => 'pending']);

        return response()->json([
            'message' => 'Booth booked successfully. Awaiting approval.',
            'booking' => $booking
        ], 201);
    }

    //==============================================================
    public function myBookings()//عرض كل الاجنحة يلي حجزها هاد المستثمر
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::with([
            'booth:id,number,image_url,area,price,location,hall_id,row,col,exhibition_id',
            'booth.exhibition:id,name,start_date,end_date,city'
        ])
        ->where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'bookings' => $bookings
        ], 200);
    }

    //==============================================================
    public function activeBookings()//الحجوزات النشطة
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
            ->where('investor_id', $investor->id)
            ->where('status', 'approved')
            ->get();

        return response()->json(['bookings' => $bookings], 200);
    }

    //==============================================================
    public function pendingBookings()//الحجوزات قيد المراجعة
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
            ->where('investor_id', $investor->id)
            ->where('status', 'pending')
            ->get();

        return response()->json(['bookings' => $bookings], 200);
    }

    //==============================================================
    public function rejectedBookings()//الحجوزات المرفوضة
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
            ->where('investor_id', $investor->id)
            ->where('status', 'rejected')
            ->get();

        return response()->json(['bookings' => $bookings], 200);
    }
    //==============================================================
    public function finishedBookings()//الحجوزات المنتهية
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
            ->where('investor_id', $investor->id)
            ->where('status', 'Finished')
            ->get();

        return response()->json(['bookings' => $bookings], 200);
    }
    //==============================================================
    public function boothDetails($bookingId)//عرض
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'booth',
            'booth.exhibition',
            'booth.profile',
            'booth.events'
        ])
        ->where('investor_id', $investor->id)
        ->find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json([
            'booking' => $booking
        ], 200);
    }

    //==============================================================
    //==============================================================

}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingBoothRequest;
use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoothBookingController extends Controller
{
    public function bookBooth(BookingBoothRequest $request, $booth_id)//حجز
    {
        $user_id = Auth::User()->id;
        $data=$request->validated();

        $investor = Auth::user()->investor;
        $booth = Booth::findOrfail($booth_id);

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

        if ($existing) {
            return response()->json([
                'message' => 'You already have a booking for this booth.'
            ], 400);
        }

        //إنشاء الحجز
        $booking = BoothBooking::create([
            'booth_id' => $booth->id,
            'duration_days' => $request->duration_days,
            'notes' => $request->notes,
            'services' => $request->services,
            'total_price' => BoothBooking::booted(),

        ]);

        $user = User::findOrfail($user_id);
        $title = "تم قبول طلبك رقم #520";
        $body = "مرحباً " . $user->name . "، لقد تم قبول طلبك وجاري تحضيره الآن.";

        // 3. إرسال الإشعار وتمرير المتغيرات له مباشرة
        $user->notify(new OrderStatusNotification($title, $body));


        //تغيير حالة البوث
        $booth->update(['status' => 'pending']);

        return response()->json([
            'message' => 'Booth booked successfully. Awaiting approval.',
            'booking' => $booking
        ], 201);
    }

    //==============================================================
    public function cancelBooking($bookingId)
    {
        $booking = BoothBooking::findOrfail($bookingId);
        $booking->status->update('canceled');
        return response()->json([
            'message' => 'Booth canceled',
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
    public function boothDetails($bookingId)//عرض تفاصيل الجناح المحجوز
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
}

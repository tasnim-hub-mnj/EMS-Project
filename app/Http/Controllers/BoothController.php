<?php

namespace App\Http\Controllers;

use App\Models\BoothBooking;
use App\Models\Booth;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use App\Models\User;
use App\Notifications\OrderStatusNotification;


class BoothController extends Controller
{
    public function getBothsExhibition($exhibition_id)//عرض كل الاجنحة الخاصة بمعرض معين
    {
        $exhibition = Exhibition::findOrfail($exhibition_id);
        $booths = $exhibition->booths;

        return response()->json([
            'booths' => $booths
        ], 200);
    }
    //=================================================================================
    // public function boothBasicDetails($booth_id)//عرض جناح على لخريطة
    // {
    //     $booth = Booth::findOrfail($booth_id);

    //     if (!$booth)
    //     {
    //         return response()->json(['message' => 'Booth not found'], 404);
    //     }

    //     $booth_map=$booth->map(function($b)
    //     {
    //         return
    //         [
    //             'id' => $b->id,
    //             'number'=>$b->number,
    //             'area'=>$b->area,
    //             'price'=>$b->price,
    //             'location'=>$b->location,
    //             'high'=>$b->map_y,
    //             'X # Y'=>[$b->map_x.'#'.$b->map_z],
    //             'services'=>$b->services
    //         ];
    //     });
    //     return response()->json([
    //         'booth' => $booth_map
    //     ], 200);
    // }
    //==============================================================
    public function bookBooth(Request $request, $booth_id)//حجز
    {
        $user_id = Auth::User()->id;

        $request->validate([
            'duration_days' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'services' => 'required|json'
        ]);

        $investor = Auth::user()->investor;
        $booth = Booth::findOrfail($booth_id);

        //التحقق من توفر البوث
        if ($booth->status !== 'available') {
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




    //*****************************************************************************
//**********************************HANAN😁***********************************
//*****************************************************************************


    //===============الزائر======================//
    // عرض الاجنحة كاملة مع امكانية البحث وبجيب الاجنحة مع المعارض المرتبطة فيهن
    public function AllBooths(Request $request)
    {
        $query = Booth::with('exhibition')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('number', 'LIKE', "%$search%")
                    ->orWhere('location', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('exhibition_id')) {
            $query->where('exhibition_id', $request->input('exhibition_id'));
        }

        return response()->json($query->get());
    }
    //===================================================


    // عرض كشك معين
    public function showBooth($id)
    {
        $booth = Booth::with([
            'exhibition',
            'profile',
            'images',
            'bookings',
            'reviews.user'
        ])->find($id);

        if (!$booth) {
            return response()->json(['message' => 'الكشك غير موجود'], 404);
        }

        return response()->json($booth);
    }


}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingBoothRequest;
use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\BoothBookingImage;
use App\Models\Exhibition;
use App\Models\ProductBookingImage;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BoothBookingController extends Controller
{
    public function bookBooth(BookingBoothRequest $request, $booth_id)//حجز
    {
        $investor = Auth::user()->investor;
        $booth = Booth::findOrFail($booth_id);
        //-----------------------------------
        $exhibition = $booth->exhibition;
        $request->merge([
            'exhibition_start' => $exhibition->start_date,
            'exhibition_end'   => $exhibition->end_date,
        ]);
        //-----------------------------------
        $data = $request->validated();

        if ($booth->status !== 'available' || $booth->status_inv == 'booked')
        {
            return response()->json([
                'message' => 'This booth is not available for booking or is booked.'
            ], 400);
        }

        // التحقق من عدم وجود حجز سابق لنفس الشخص و الجناح
        $existing = BoothBooking::where('investor_id', $investor->id)
            ->where('booth_id', $booth->id)
            ->where('status', 'pending')
            ->first();

        if ($existing)
        {
            return response()->json([
                'message' => 'You already have a booking for this booth.'
            ], 400);
        }

        //-----------------------------------
        $totalPrice = $booth->price;
        $boothServices = json_decode($booth->services, true) ?? [];
        $selectedServices = json_decode($data['additional_services'] ?? '[]', true);

        foreach ($selectedServices as $serviceName)
        {
            $service = collect($boothServices)->firstWhere('name', $serviceName);
            if ($service)
            {
                $totalPrice += $service['price'];
            }
        }
        $totalPrice *= $data['duration_days'];
        //-----------------------------------
        $startDate = Carbon::parse($data['start_date']);
        $endDate   = $startDate->copy()->addDays($data['duration_days'] - 1);

        $booking = BoothBooking::create([
            'investor_id' => $investor->id,
            'booth_id' => $booth->id,
            'start_date' => $startDate,
            'duration_days' => $data['duration_days'],
            'end_date' => $endDate,
            'additional_services' => $data['additional_services'],
            'notes' => $data['notes'],
            'total_price' => $totalPrice,
            'services_products' => $data['services_products'],
            'booked_at' => now()->format('Y-m-d'),

        ]);

        $images_b = [];
        if ($request->hasFile('image_b'))
        {
            foreach ($request->file('image_b') as $img)
            {
                $path = $img->store('booth_booking_images', 'public');

                $images_b[] = BoothBookingImage::create([
                    'booth_booking_id' => $booking->id,
                    'image_b' => $path,
                ]);
            }
        }
        //-----------------------------------
        $images_p = [];
        if ($request->hasFile('image_p'))
        {
            foreach ($request->file('image_p') as $img)
            {
                $path = $img->store('product_booking_images', 'public');

                $images_p[] = ProductBookingImage::create([
                    'booth_booking_id' => $booking->id,
                    'image_p' => $path,
                ]);
            }
        }

        // $user = User::findOrfail($user_id);
        // $title = "تم قبول طلبك رقم #520";
        // $body = "مرحباً " . $user->name . "، لقد تم قبول طلبك وجاري تحضيره الآن.";

        // // 3. إرسال الإشعار وتمرير المتغيرات له مباشرة
        // $user->notify(new OrderStatusNotification($title, $body));

        return response()->json([
            'message' => 'Booth booked successfully. Awaiting approval.',
            'booking' => $booking,
            'booth_images' => $images_b,
            'product_images' => $images_p,
        ], 201);
    }

    //==============================================================
    public function myBookings()//عرض كل الاجنحة يلي حجزها هاد المستثمر
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::where('investor_id', $investor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $bookings_data = $bookings->map(function ($booking)
        {
            return
            [
                'id' => $booking->id,
                'booth_id' => $booking->booth_id,
                'start_date' => $booking->start_date,
                'duration_days' => $booking->duration_days,
                'end_date' => $booking->end_date,
                'additional_services' => $booking->additional_services,
                'notes' => $booking->notes,
                'total_price' => $booking->total_price,
                'paid_amount' => $booking->paid_amount,
                'services_products' => $booking->services_products,
                'status' => $booking->status,
                'booked_at' => $booking->booked_at,
                'booth_BookingImages'=>$booking->boothBookingImages,
                'product_BookingImages'=>$booking->productBookingImages,
                'booth' => $booking->booth,
                'exhibition'=>$booking->booth->exhibition,
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booking->booth_id)
                    ->where('favoritable_type', Booth::class)
                    ->exists()
            ];

        });

        return response()->json([
            'bookings' => $bookings_data,
        ], 200);
    }
    //==============================================================
    public function showBooking($booking_id)//عرض تفاصيل الجناح المحجوز
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'boothBookingImages',
            'productBookingImages',
            'booth',
            'booth.exhibition',
            'investor.socialLinks',
            'booth.events',
        ])
            ->where('investor_id', $investor->id)
            ->find($booking_id);

        if (!$booking)
        {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $is_favorite = Auth::user()->favorites()
        ->where('favoritable_id', $booking->booth_id)
        ->where('favoritable_type', Booth::class)
        ->exists();

        return response()->json([
            'booking' => $booking,
            'is_favorite' => $is_favorite
        ], 200);
    }
    //==============================================================
    public function getAllBooking($exhibition_id)//عرض كل الحجوزات الخاصة بمعرض ما//o
    {
        $exhibition = Exhibition::with(
            'booths'
        )->findOrFail($exhibition_id);

        $bookings = BoothBooking::whereIn('booth_id', $exhibition->booths->pluck('id'))
            ->with(['booth', 'investor.user','investor'])
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            // 'exhibition_id' => $exhibition->id,
            // 'exhibition_name' => $exhibition->name,
            'total_bookings' => $bookings->count(),
            'bookings' => $bookings
        ], 200);
    }
    //==============================================================
    public function approveBooking($booking_id)//o
    {
        $booking = BoothBooking::findOrFail($booking_id);
        $booth = $booking->booth;

        if ($booking->status === 'approved')
        {
            return response()->json([
                'message' => 'Booking already approved'
            ], 400);
        }


        $booking->update(['status' => 'approved']);
        $booth->update(['status_inv' => 'booked']);

        //رفض التضارب
        $approvedStart = $booking->start_date;
        $approvedEnd   = $booking->end_date;

        BoothBooking::where('booth_id', $booth->id)
            ->where('id', '!=', $booking->id)
            ->where('status', 'pending')
            ->where(function ($q) use ($approvedStart, $approvedEnd)
            {
                $q->where('start_date', '<=', $approvedEnd)
                ->where('end_date', '>=', $approvedStart);
            })
            ->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Booking approved successfully',
            'booth' => $booth,
            'booking' => $booking
        ], 200);
    }
    //==============================================================
    public function rejectBooking($booking_id)//o
    {
        $booking = BoothBooking::findOrFail($booking_id);
        $booth = $booking->booth;

        if ($booking->status === 'approved')
        {
            $booking->booth->update(['status_inv' => 'available']);
        }


        $booking->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Booking rejected successfully',
            'booth' => $booth,
            'booking' => $booking
        ], 200);
    }
    //==============================================================
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
    //==============================================================
    // public function cancelBooking($bookingId)
    // {
    //     $booking = BoothBooking::findOrfail($bookingId);
    //     $booking->status->update('canceled');
    //     return response()->json([
    //         'message' => 'Booth canceled',
    //         'booking' => $booking
    //     ], 201);
    // }
    //==============================================================
}

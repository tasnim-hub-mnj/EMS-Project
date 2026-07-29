<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingBoothRequest;
use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\BoothBookingImage;
use App\Models\Event;
use App\Models\Exhibition;
use App\Models\ProductBookingImage;
use App\Models\SocialLink;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $startDate = Carbon::parse($data['start_date']);
        $endDate   = Carbon::parse($data['end_date']);
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
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
        $totalPrice *= $days;
        //-----------------------------------
        $booking = BoothBooking::create([
            'investor_id' => $investor->id,
            'booth_id' => $booth->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
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
    public function cancelBooking($booking_id)
    {
        $booking = BoothBooking::findOrfail($booking_id);
        $booking->status->update('canceled');
        return response()->json([
            'message' => 'Booking Booth canceled',
            'booking' => $booking
        ], 201);
    }
    //==============================================================
    public function myBookings()//عرض كل الاجنحة يلي حجزها هاد المستثمر/حجوزاتي
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
                'status' => $booking->status,
                'booth_number' => $booking->booth->number,
                'exhibition_name'=>$booking->booth->exhibition->name,
                'booth_area' => $booking->booth->area,
                'booth_price' => $booking->booth->price,
                'booth_image' => $booking->booth->image,
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
    public function getBoothProfile($boothId)//عرض تفاصيل الجناح المحجوز
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'boothBookingImages',
            'productBookingImages',
            'booth',
            'booth.exhibition',
            'investor.user',
            'investor.socialLinks',
        ])
            ->where('investor_id', $investor->id)
            ->where('booth_id', $boothId)
            ->first();

        if (!$booking)
        {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking_data =
        [
            'id' => $booking->id,
            'booth_id' => $booking->booth_id,
            'status' => $booking->status,
            'booth_number' => $booking->booth->number,
            'exhibition_name'=>$booking->booth->exhibition->name,
            'booth_area' => $booking->booth->area,
            'booth_location' => $booking->booth->location,
            'price' => $booking->total_price,
            'end_date' => $booking->end_date,
            'booth_image' => $booking->booth->image,

            'company_name' => $booking->investor->company_name,
            'company_email' => $booking->investor->user->email,
            'activity_type' => $booking->investor->activity_type,
            'services_products' => $booking->services_products,
            'company_location' => $booking->investor->location,
            'socialLinks' => $booking->investor->socialLinks,

            'product_BookingImages'=>$booking->productBookingImages,
            'booth_BookingImages'=>$booking->boothBookingImages,

            'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booking->booth_id)
                    ->where('favoritable_type', Booth::class)
                    ->exists()
        ];

        return response()->json([
            'boothProfile' => $booking_data,
        ], 200);

    }
    //==============================================================
    public function updateBoothProfile(Request $request, $boothId)
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'boothBookingImages',
            'productBookingImages',
            'investor.socialLinks'
        ])
            ->where('investor_id', $investor->id)
            ->where('booth_id', $boothId)
            ->first();

        if (!$booking)
        {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        //تعديل بيانات الشركة
        if ($request->filled('company_nature'))
        {
            $booking->investor->activity_type = $request->company_nature;
            $booking->investor->save();
        }

        if ($request->filled('headquarters'))
        {
            $booking->investor->location = $request->headquarters;
            $booking->investor->save();
        }


        if ($request->filled('services_products'))
        {
            $booking->services_products = $request->services_products;
        }

        //تعديل صور الجناح
        if ($request->filled('delete_booth_images'))
        {
            foreach ($request->delete_booth_images as $imgId)
            {
                $img = BoothBookingImage::where('booth_booking_id', $booking->id)->find($imgId);
                if ($img)
                {
                    Storage::disk('public')->delete($img->image_b);
                    $img->delete();
                }
            }
        }


        if ($request->hasFile('booth_images'))
        {
            foreach ($request->file('booth_images') as $img)
            {
                $path = $img->store('booth_booking_images', 'public');

                BoothBookingImage::create([
                    'booth_booking_id' => $booking->id,
                    'image_b' => $path,
                ]);
            }
        }

        //تعديل صور المنتجات
        if ($request->filled('delete_product_images'))
        {
            foreach ($request->delete_product_images as $imgId)
            {
                $img = ProductBookingImage::where('booth_booking_id', $booking->id)->find($imgId);
                if ($img)
                {
                    Storage::disk('public')->delete($img->image_p);
                    $img->delete();
                }
            }
        }


        if ($request->hasFile('product_images'))
        {
            foreach ($request->file('product_images') as $img)
            {
                $path = $img->store('product_booking_images', 'public');

                ProductBookingImage::create([
                    'booth_booking_id' => $booking->id,
                    'image_p' => $path,
                ]);
            }
        }

        //تعديل الروابط
        if ($request->filled('delete_links'))
        {
            foreach ($request->delete_links as $linkId)
            {
                $link = SocialLink::where('investor_id', $investor->id)->find($linkId);
                if ($link)
                {
                    $link->delete();
                }
            }
        }


        if ($request->filled('social_links'))
        {
            foreach ($request->social_links as $link)
            {
                if ($link)
                {
                    SocialLink::create([
                        'investor_id' => $investor->id,
                        'link' => $link,
                    ]);
                }
            }
        }

        $booking->save();

        return response()->json([
            'message' => 'Booth profile updated successfully.',
            'booking' => $booking->load([
                'boothBookingImages',
                'productBookingImages',
                'investor.socialLinks'
            ])
        ], 200);
    }
    //==============================================================
    //o
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
        $booking->update(['approved_at' => now()->format('Y-m-d')]);

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

    //==============================================================
}

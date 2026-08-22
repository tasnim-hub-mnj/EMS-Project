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
use App\BookingConflictTrait;
use App\Http\Requests\RejectBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Copy;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BoothBookingController extends Controller
{
    use BookingConflictTrait;

    private function investorBoothStatus(BoothBooking $booking): string
    {
        if ($booking->status === 'approved') {
            return $booking->end_date && !Carbon::parse($booking->end_date)->isPast()
                ? 'active'
                : 'ended';
        }

        return match ($booking->status) {
            'pending' => 'pending',
            'rejected' => 'rejected',
            'cancelled', 'canceled' => 'cancelled',
            'finished' => 'ended',
            default => $booking->status,
        };
    }

    private function servicePrices(BoothBooking $booking): array
    {
        $services = $booking->services_products;
        if (is_string($services)) {
            $services = json_decode($services, true);
        }
        return is_array($services) ? $services : [];
    }

    //===============================================================
    //**************************----i----****************************
    //===============================================================
    public function bookBooth(BookingBoothRequest $request)//✅
    {
        $data = $request->validated();

        $investor = Auth::user()->investor;
        $booth = Booth::with(['exhibition', 'boothImages'])->findOrFail($data['booth_id']);
        $exhibition_id = $booth->exhibition->id;
        $copy = Copy::where('exhibition_id', $exhibition_id)
            ->where('copy_status', 'active')
            ->first();


        if ($booth->status_inv !== 'available' || $booth->status !== 'available')
        {
            return response()->json([
                'message' => 'This booth is not available for booking.'
            ], 400);
        }

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $days  = $start->diffInDays($end) + 1;
        $selectedServices = collect($data['services'] ?? [])
            ->filter(fn ($selected) => $selected === true)
            ->keys()
            ->mapWithKeys(function ($name) use ($booth) {
                return [(string) $name => (float) (($booth->services ?? [])[$name] ?? 0)];
            })
            ->all();
        $servicesPrice = array_sum($selectedServices);
        $boothPrice = $booth->pricing_type === 'daily'
            ? (float) $booth->price * $days
            : (float) $booth->price;
        $totalPrice = $boothPrice + $servicesPrice;

        //منع الحجز إذا كان هناك حجز مقبول متضارب
        if ($this->hasApprovedConflict($booth->id, $start, $end))
        {
            return response()->json([
                'message' => 'This booth is already booked in this period.'
            ], 409);
        }

        $booking = BoothBooking::create([
            'investor_id'        => $investor->id,
            'booth_id'           => $booth->id,
            'copy_id'            => $copy->id,
            'start_date'         => $start,
            'end_date'           => $end,
            'days'               => $days,
            'additional_services'=> $data['services'] ?? [],
            'services_products'  => $selectedServices,
            'notes'              => $data['notes'],
            'total_price'        => $totalPrice,
            'paid_amount'        => 0,
            'booked_at'          => now()->format('Y-m-d'),
            'status'             => 'pending',
        ]);

        $this->notifyBooking($booth->exhibition_id, 'تم استلام حجز جديد', 'تم استلام طلب حجز جديد لأحد الأجنحة.', 'booking.created');
        $this->notifyInvestorBooking(
            $booking,
            'تم استلام طلب الحجز',
            'تم استلام طلب حجز الجناح وسيتم مراجعته من إدارة المعرض.',
            'booking.created'
        );

        $services = $booth->services ?? [];
        $amenities = $booth->amenities ?? [];
        $bookedServices = $booking->additional_services ?? [];

        // $booth->status_inv = 'booked';عند القبول
        // $booth->save();

        // $user = User::findOrfail($user_id);
        // $title = "تم قبول طلبك رقم #520";
        // $body = "مرحباً " . $user->name . "، لقد تم قبول طلبك وجاري تحضيره الآن.";

        // // 3. إرسال الإشعار وتمرير المتغيرات له مباشرة
        // $user->notify(new OrderStatusNotification($title, $body));

        return response()->json([
            'data' =>
            [
                //booth
                'id' => $booth->id,
                'number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name,
                'image_url' => $booth->boothImages->first()?->image
                    ? asset('storage/' . $booth->boothImages->first()->image)
                    : null,
                'area' => $booth->area,
                'status' => $this->investorBoothStatus($booking),
                'price' => $booth->price,
                'pricing_type' => $booth->pricing_type,
                'start_date' => Carbon::parse($booking->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($booking->end_date)->format('Y-m-d'),
                'location' => $booth->location,
                'amenities' => $amenities,
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->exists(),
                'services' => $services,

                //investor
                'company_name' => $investor->company_name,
                'company_email' => $investor->user->email,
                'company_initials' => mb_substr($investor->company_name, 0, 2),

                //booking
                'booking_id' => $booking->id,
                'booking_number' => 'BK-' . $booking->id,
                'booked_at' => Carbon::parse($booking->booked_at)->format('Y-m-d'),
                'duration_days' => $booking->days,
                'services_price' => $servicesPrice,
                'total_price' => $totalPrice,
                'paid_amount' => $booking->paid_amount,
                'remaining_amount' => $booking->total_price - $booking->paid_amount,
                'booked_services' => $data['services'] ?? [],
                'notes' => $booking->notes,
            ]
        ], 201);
    }
    //==============================================================
    public function cancelBooking($booking_id)//✅
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::findOrFail($booking_id);

        if ($booking->investor_id !== $investor->id)
        {
            return response()->json([
                'message' => 'You are not allowed to cancel this booking.'
            ], 403);
        }

        if ($booking->status === 'canceled')
        {
            return response()->json([
                'message' => 'This booking is already canceled.'
            ], 400);
        }

        if ($booking->status === 'rejected')
        {
            return response()->json([
                'message' => 'Rejected bookings cannot be canceled.'
            ], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        $this->notifyBooking(
            $booking->booth->exhibition_id,
            'تم إلغاء حجز',
            'تم إلغاء حجز جناح من قبل المستثمر.',
            'booking.cancelled'
        );

        return response()->json([
            'message' => 'Booking canceled successfully',
            'booking' => $booking
        ], 200);
    }
    //==============================================================
    public function myBookings()//✅حجوزاتي
    {
        $investor = Auth::user()->investor;

        $bookings = BoothBooking::with([
            'booth.exhibition',
            'booth.boothImages',
            'investor.user'
        ])
        ->where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();

        $data = $bookings->map(function ($booking)
        {
            $booth = $booking->booth;
            return
            [
                //booth
                'id' => $booth->id,
                'number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name,
                'image_url' => $booth->boothImages->first()?->image
                    ? asset('storage/' . $booth->boothImages->first()->image)
                    : null,
                'area' => $booth->area,
                'status' => $this->investorBoothStatus($booking),
                'price' => $booth->price,
                'pricing_type' => $booth->pricing_type,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'location' => $booth->location,
                'amenities' => $booth->amenities ?? [],
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->exists(),
                'services' => $booth->services ?? [],

                //investor
                'company_name' => $booking->investor->company_name,
                'company_email' => $booking->investor->user->email,
                'company_initials' => mb_substr($booking->investor->company_name, 0, 2),

                //booking
                'booking_id' => $booking->id,
                'booking_number' => 'BK-' . $booking->id,
                'booked_at' => Carbon::parse($booking->booked_at)->format('Y-m-d'),
                'duration_days' => $booking->days,
                'services_price' => array_sum($this->servicePrices($booking)),
                'total_price' => $booking->total_price,
                'paid_amount' => $booking->paid_amount,
                'remaining_amount' => $booking->total_price - $booking->paid_amount,
                'booked_services' => $booking->additional_services ?? [],
                'notes' => $booking->notes,
            ];
        });

        return response()->json([
            'data' => $data
        ], 200);
    }
    //==============================================================
    public function getBookingDetail($booth_booking_id)//✅
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'booth.exhibition',
            'booth.boothImages',
            'investor.user'
        ])
        ->where('investor_id', $investor->id)
        ->find($booth_booking_id);

        if (!$booking)
        {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booth = $booking->booth;

        $services = $booth->services ?? [];
        $amenities = $booth->amenities ?? [];
        $bookedServices = $booking->additional_services ?? [];

        return response()->json([
            'data' =>
            [
                //booth
                'id' => $booth->id,
                'number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name,
                'image_url' => $booth->boothImages->first()?->image
                    ? asset('storage/' . $booth->boothImages->first()->image)
                    : null,
                'area' => $booth->area,
                'status' => $this->investorBoothStatus($booking),
                'price' => $booth->price,
                'pricing_type' => $booth->pricing_type,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'location' => $booth->location,
                'amenities' => $amenities,
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->exists(),
                'services' => $services,

                //investor
                'company_name' => $booking->investor->company_name,
                'company_email' => $booking->investor->user->email,
                'company_initials' => mb_substr($booking->investor->company_name, 0, 2),

                //booking
                'booking_id' => $booking->id,
                'booking_number' => 'BK-' . $booking->id,
                'booked_at' => Carbon::parse($booking->booked_at)->format('Y-m-d'),
                'duration_days' => $booking->days,
                'services_price' => array_sum($this->servicePrices($booking)),
                'total_price' => $booking->total_price,
                'paid_amount' => $booking->paid_amount ?? 0,
                'remaining_amount' => $booking->total_price - ($booking->paid_amount ?? 0),
                'booked_services' => $bookedServices ?? [],
                'notes' => $booking->notes,
            ]
        ], 200);
    }
    //==============================================================
    //==============================================================


    //==============================================================
    //*************************----o----****************************
    //==============================================================
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        $booth = Booth::findOrFail($data['booth_id']);

        $days = Carbon::parse($data['start_date'])
            ->diffInDays(Carbon::parse($data['end_date'])) + 1;

        $total = $booth->pricing_type === 'daily'
            ? $booth->price * $days
            : $booth->price;

        if (!empty($data['service_prices']))
        {
            $total += array_sum($data['service_prices']);
        }

        $booking = BoothBooking::create([
            'investor_id' => $data['investor_id'],
            'booth_id' => $data['booth_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $days,
            'additional_services' => $data['additional_services'] ?? [],
            'services_products' => json_encode($data['service_prices'] ?? []),
            'total_price' => $total,
            'booked_at' => now()->format('Y-m-d'),
            'status' => 'pending',
            'copy_id' => Copy::where('exhibition_id', $booth->exhibition_id)
                ->where('copy_status', 'active')
                ->first()?->id,
        ]);

        return new BookingResource($booking);
    }
    //==============================================================
    public function index(Request $request)
    {
        $query = BoothBooking::with(['booth', 'investor.user', 'copy']);

        if ($request->status)
            $query->where('status', $request->status);

        if ($request->exhibition_id)
            $query->whereHas('booth', fn($q) => $q->where('exhibition_id', $request->exhibition_id));

        if ($request->edition_id)
            $query->where('copy_id', $request->edition_id);

        if ($request->investor_id)
            $query->where('investor_id', $request->investor_id);

        if (!$request->include_past)
            $query->where('status', '!=', 'finished');

        return BookingResource::collection($query->get());
    }
    //==============================================================
    public function pastEditionBookings($exhibition_id)//حجوزات الإصدارات السابقة
    {
        $bookings = BoothBooking::with(['booth', 'investor.user', 'copy'])
            ->whereHas('booth', fn($q) => $q->where('exhibition_id', $exhibition_id))
            ->where('status', 'finished')
            ->get();

        return BookingResource::collection($bookings);
    }
    //==============================================================
    public function show($booking_id)
    {
        $booking = BoothBooking::with(['booth', 'investor.user', 'copy'])
            ->findOrFail($booking_id);

        return new BookingResource($booking);
    }
    //==============================================================
    public function approve($booking_id)
    {
        $booking = BoothBooking::findOrFail($booking_id);

        $booking->update([
            'status' => 'approved',
            'approved_at' => now()->format('Y-m-d'),
            'paid_amount' => $booking->total_price
        ]);

        // تحديث حالة البوث
        $booking->booth->update(['status_inv' => 'booked']);

        $this->notifyBooking($booking->booth->exhibition_id, 'تمت الموافقة على حجز', 'تمت الموافقة على طلب حجز جناح.', 'booking.approved');
        $this->notifyInvestorBooking(
            $booking,
            'تمت الموافقة على الحجز',
            'تمت الموافقة على طلب حجز الجناح الخاص بك.',
            'booking.approved'
        );

        return new BookingResource($booking);
    }
    //==============================================================
    public function reject(RejectBookingRequest $request, $booking_id)
    {
        $booking = BoothBooking::findOrFail($booking_id);

        $booking->update([
            'status' => 'rejected',
            'notes' => $request->reason
        ]);

        $this->notifyBooking($booking->booth->exhibition_id, 'تم رفض حجز', 'تم رفض طلب حجز جناح.', 'booking.rejected');
        $this->notifyInvestorBooking(
            $booking,
            'تم رفض الحجز',
            'تم رفض طلب حجز الجناح الخاص بك. السبب: ' . ($request->reason ?: 'لم يتم تحديد سبب.'),
            'booking.rejected'
        );

        return new BookingResource($booking);
    }

    private function notifyBooking(int $exhibitionId, string $title, string $body, string $event): void
    {
        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, $title, $body, 'booking', 'org.bookings', ['event' => $event], '/bookings', ['admin.reports']
            );
        }
    }

    private function notifyInvestorBooking(
        BoothBooking $booking,
        string $title,
        string $body,
        string $event,
    ): void {
        $userId = $booking->investor?->user_id;
        if (!$userId) {
            return;
        }

        app(NotificationService::class)->forUserId(
            $userId,
            $title,
            $body,
            'booking',
            ['event' => $event, 'booking_id' => $booking->id],
            '/bookings',
            $booking->booth?->exhibition_id,
        );
    }
    //==============================================================
    public function contractPdf($booking_id)
    {
        $booking = BoothBooking::findOrFail($booking_id);

        $pdf = Pdf::loadView('contracts.booking', ['booking' => $booking]);

        return $pdf->stream("contract-{$booking->id}.pdf");
    }

    //==============================================================
    //==============================================================








    // public function getAllBooking($exhibition_id)//عرض كل الحجوزات الخاصة بمعرض ما//o
    // {
    //     $exhibition = Exhibition::with(
    //         'booths'
    //     )->findOrFail($exhibition_id);

    //     $bookings = BoothBooking::whereIn('booth_id', $exhibition->booths->pluck('id'))
    //         ->with(['booth', 'investor.user','investor'])
    //         ->orderBy('start_date', 'asc')
    //         ->get();

    //     return response()->json([
    //         // 'exhibition_id' => $exhibition->id,
    //         // 'exhibition_name' => $exhibition->name,
    //         'total_bookings' => $bookings->count(),
    //         'bookings' => $bookings
    //     ], 200);
    // }
    // //==============================================================
    // public function approveBooking($booking_id)//o
    // {
    //     $booking = BoothBooking::findOrFail($booking_id);
    //     $booth = $booking->booth;

    //     if ($booking->status === 'approved')
    //     {
    //         return response()->json([
    //             'message' => 'Booking already approved'
    //         ], 400);
    //     }


    //     $booking->update(['status' => 'approved']);
    //     $booth->update(['status_inv' => 'booked']);
    //     $booking->update(['approved_at' => now()->format('Y-m-d')]);

    //     //رفض التضارب
    //     $approvedStart = $booking->start_date;
    //     $approvedEnd   = $booking->end_date;

    //     BoothBooking::where('booth_id', $booth->id)
    //         ->where('id', '!=', $booking->id)
    //         ->where('status', 'pending')
    //         ->where(function ($q) use ($approvedStart, $approvedEnd)
    //         {
    //             $q->where('start_date', '<=', $approvedEnd)
    //             ->where('end_date', '>=', $approvedStart);
    //         })
    //         ->update(['status' => 'rejected']);

    //     return response()->json([
    //         'message' => 'Booking approved successfully',
    //         'booth' => $booth,
    //         'booking' => $booking
    //     ], 200);
    // }
    // //==============================================================
    // public function rejectBooking($booking_id)//o
    // {
    //     $booking = BoothBooking::findOrFail($booking_id);
    //     $booth = $booking->booth;

    //     if ($booking->status === 'approved')
    //     {
    //         $booking->booth->update(['status_inv' => 'available']);
    //     }


    //     $booking->update(['status' => 'rejected']);

    //     return response()->json([
    //         'message' => 'Booking rejected successfully',
    //         'booth' => $booth,
    //         'booking' => $booking
    //     ], 200);
    // }
    // //==============================================================
    // //==============================================================
    // public function activeBookings()//الحجوزات النشطة
    // {
    //     $investor = Auth::user()->investor;

    //     $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
    //         ->where('investor_id', $investor->id)
    //         ->where('status', 'approved')
    //         ->get();

    //     return response()->json(['bookings' => $bookings], 200);
    // }
    // //==============================================================
    // public function pendingBookings()//الحجوزات قيد المراجعة
    // {
    //     $investor = Auth::user()->investor;

    //     $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
    //         ->where('investor_id', $investor->id)
    //         ->where('status', 'pending')
    //         ->get();

    //     return response()->json(['bookings' => $bookings], 200);
    // }
    // //==============================================================
    // public function rejectedBookings()//الحجوزات المرفوضة
    // {
    //     $investor = Auth::user()->investor;

    //     $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
    //         ->where('investor_id', $investor->id)
    //         ->where('status', 'rejected')
    //         ->get();

    //     return response()->json(['bookings' => $bookings], 200);
    // }
    // //==============================================================
    // public function finishedBookings()//الحجوزات المنتهية
    // {
    //     $investor = Auth::user()->investor;

    //     $bookings = BoothBooking::with(['booth', 'booth.exhibition'])
    //         ->where('investor_id', $investor->id)
    //         ->where('status', 'Finished')
    //         ->get();

    //     return response()->json(['bookings' => $bookings], 200);
    // }
    //==============================================================
    //==============================================================
    //==============================================================
    // public function getBoothProfile($boothId)//عرض تفاصيل الجناح المحجوز
    // {
    //     $investor = Auth::user()->investor;

    //     $booking = BoothBooking::with([
    //         'boothBookingImages',
    //         'productBookingImages',
    //         'booth',
    //         'booth.exhibition',
    //         'investor.user',
    //         'investor.socialLinks',
    //     ])
    //         ->where('investor_id', $investor->id)
    //         ->where('booth_id', $boothId)
    //         ->first();

    //     if (!$booking)
    //     {
    //         return response()->json(['message' => 'Booking not found'], 404);
    //     }

    //     $booking_data =
    //     [
    //         'id' => $booking->id,
    //         'booth_id' => $booking->booth_id,
    //         'status' => $booking->status,
    //         'booth_number' => $booking->booth->number,
    //         'exhibition_name'=>$booking->booth->exhibition->name,
    //         'booth_area' => $booking->booth->area,
    //         'booth_location' => $booking->booth->location,
    //         'price' => $booking->total_price,
    //         'end_date' => $booking->end_date,
    //         'booth_image' => $booking->booth->image,

    //         'company_name' => $booking->investor->company_name,
    //         'company_email' => $booking->investor->user->email,
    //         'activity_type' => $booking->investor->activity_type,
    //         'services_products' => $booking->services_products,
    //         'company_location' => $booking->investor->location,
    //         'socialLinks' => $booking->investor->socialLinks,

    //         'product_BookingImages'=>$booking->productBookingImages,
    //         'booth_BookingImages'=>$booking->boothBookingImages,

    //         'is_favorite' => Auth::user()->favorites()
    //                 ->where('favoritable_id', $booking->booth_id)
    //                 ->where('favoritable_type', Booth::class)
    //                 ->exists()
    //     ];

    //     return response()->json([
    //         'boothProfile' => $booking_data,
    //     ], 200);

    // }
    // //==============================================================
    // public function updateBoothProfile(Request $request, $boothId)
    // {
    //     $investor = Auth::user()->investor;

    //     $booking = BoothBooking::with([
    //         'boothBookingImages',
    //         'productBookingImages',
    //         'investor.socialLinks'
    //     ])
    //         ->where('investor_id', $investor->id)
    //         ->where('booth_id', $boothId)
    //         ->first();

    //     if (!$booking)
    //     {
    //         return response()->json(['message' => 'Booking not found'], 404);
    //     }

    //     //تعديل بيانات الشركة
    //     if ($request->filled('company_nature'))
    //     {
    //         $booking->investor->activity_type = $request->company_nature;
    //         $booking->investor->save();
    //     }

    //     if ($request->filled('headquarters'))
    //     {
    //         $booking->investor->location = $request->headquarters;
    //         $booking->investor->save();
    //     }


    //     if ($request->filled('services_products'))
    //     {
    //         $booking->services_products = $request->services_products;
    //     }

    //     //تعديل صور الجناح
    //     if ($request->filled('delete_booth_images'))
    //     {
    //         foreach ($request->delete_booth_images as $imgId)
    //         {
    //             $img = BoothBookingImage::where('booth_booking_id', $booking->id)->find($imgId);
    //             if ($img)
    //             {
    //                 Storage::disk('public')->delete($img->image_b);
    //                 $img->delete();
    //             }
    //         }
    //     }


    //     if ($request->hasFile('booth_images'))
    //     {
    //         foreach ($request->file('booth_images') as $img)
    //         {
    //             $path = $img->store('booth_booking_images', 'public');

    //             BoothBookingImage::create([
    //                 'booth_booking_id' => $booking->id,
    //                 'image_b' => $path,
    //             ]);
    //         }
    //     }

    //     //تعديل صور المنتجات
    //     if ($request->filled('delete_product_images'))
    //     {
    //         foreach ($request->delete_product_images as $imgId)
    //         {
    //             $img = ProductBookingImage::where('booth_booking_id', $booking->id)->find($imgId);
    //             if ($img)
    //             {
    //                 Storage::disk('public')->delete($img->image_p);
    //                 $img->delete();
    //             }
    //         }
    //     }


    //     if ($request->hasFile('product_images'))
    //     {
    //         foreach ($request->file('product_images') as $img)
    //         {
    //             $path = $img->store('product_booking_images', 'public');

    //             ProductBookingImage::create([
    //                 'booth_booking_id' => $booking->id,
    //                 'image_p' => $path,
    //             ]);
    //         }
    //     }

    //     //تعديل الروابط
    //     if ($request->filled('delete_links'))
    //     {
    //         foreach ($request->delete_links as $linkId)
    //         {
    //             $link = SocialLink::where('investor_id', $investor->id)->find($linkId);
    //             if ($link)
    //             {
    //                 $link->delete();
    //             }
    //         }
    //     }


    //     if ($request->filled('social_links'))
    //     {
    //         foreach ($request->social_links as $link)
    //         {
    //             if ($link)
    //             {
    //                 SocialLink::create([
    //                     'investor_id' => $investor->id,
    //                     'link' => $link,
    //                 ]);
    //             }
    //         }
    //     }

    //     $booking->save();

    //     return response()->json([
    //         'message' => 'Booth profile updated successfully.',
    //         'booking' => $booking->load([
    //             'boothBookingImages',
    //             'productBookingImages',
    //             'investor.socialLinks'
    //         ])
    //     ], 200);
    // }

}

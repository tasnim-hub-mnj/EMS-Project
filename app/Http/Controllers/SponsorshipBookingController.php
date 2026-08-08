<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingshipSponsorEventRequest;
use App\Models\SponsorshipBooking;
use App\Models\SponsorEvent;
use App\Models\SponsorshipBookingImage;
use App\Models\SponsorshipBookingProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SponsorshipBookingController extends Controller
{
    //===============================================================
    //SponsorshipBooking//i
    //===============================================================
    public function getMySponsorships()//رعاياتي//✅
    {
        $investor = Auth::user()->investor;

        $bookings = SponsorshipBooking::with([
            'sponsorEvent.exhibition',
            'sponsorEvent.sponsorEventImages'
        ])
        ->where('investor_id', $investor->id)
        ->orderBy('booked_at', 'desc')
        ->get();

        $data = $bookings->map(function ($bk)
        {
            $event = $bk->sponsorEvent;
            $exhibition = $event->exhibition;
            return
            [
                //bookings
                'id' => $bk->id,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_type' => $event->type,

                'exhibition_name' => $exhibition->name,

                'date' => Carbon::parse($event->start_time)->format('Y-m-d'),
                'place' => $event->place,
                'time' => Carbon::parse($event->start_time)->format('H:i') . ' - ' .
                        Carbon::parse($event->end_time)->format('H:i'),

                // المدة
                'selected_duration_label' => $bk->selected_duration_label ?? null,
                'selected_days' => $bk->days,
                'price' => $bk->total_price,

                'status' => $bk->status,
                'booked_at' => Carbon::parse($bk->booked_at)->format('Y-m-d'),

                // الإحصائيات
                'total_visitors' => $bk->total_visitors ?? 0,
                'total_attendees' => $bk->total_attendees ?? 0,
                'daily_visitors' => json_decode($bk->daily_visitors, true) ?? [],
                'current_day' => $bk->current_day ?? 1,
                'total_days' => $bk->total_days ?? $bk->days,

                // صور الفعالية الإعلانية
                'company_images' => $bk->sponsorshipBookingImages->pluck('image')->toArray(),

                'logo' => $bk->investor->logo,
            ];
        });

        return response()->json([
            'data' => $data
        ], 200);
    }
    //===============================================================
    public function createSponsorship(BookingshipSponsorEventRequest $request)//حجز فعالية اعلانية//✅
    {

        $investor = Auth::user()->investor;
        $data = $request->validated();

        $event = SponsorEvent::findOrFail($data['event_id']);

        $total_price = $data['price'];

        $logoPath = null;
        if ($request->hasFile('logo'))
        {
            $logoPath = $request->file('logo')->store('sponsorship_company_logos', 'public');
        }

        $booking = SponsorshipBooking::create([
            'investor_id' => $investor->id,
            'sponsor_event_id' => $event->id,

            'selected_duration_label' => $data['selected_duration_label'] ?? null,
            'days' => $data['selected_days'],
            'total_price' => $total_price,

            'description' => $data['product_names'] ?? null,
            'logo' => $logoPath,

            'booked_at' => now()->format('Y-m-d'),
            'status' => 'pending',
        ]);

        //ad_images
        if ($request->hasFile('ad_images'))
        {
            foreach ($request->file('ad_images') as $img)
            {
                $path = $img->store('sponsorship_ad_images', 'public');

                SponsorshipBookingImage::create([
                    'sponsorship_booking_id' => $booking->id,
                    'type' => 'ad',
                    'image' => $path,
                ]);
            }
        }

        //poster_images
        if ($request->hasFile('poster_images'))
        {
            foreach ($request->file('poster_images') as $img)
            {
                $path = $img->store('sponsorship_poster_images', 'public');

                SponsorshipBookingImage::create([
                    'sponsorship_booking_id' => $booking->id,
                    'type' => 'poster',
                    'image' => $path,
                ]);
            }
        }

        //product_images
        if ($request->filled('product_images'))
        {
            foreach ($request->product_images as $item)
            {
                $path = $item['image']->store('sponsorship_product_images', 'public');

                SponsorshipBookingProductImage::create([
                    'sp_b_id' => $booking->id,
                    'product_name' => $item['name'],
                    'image' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Sponsorship created successfully.',
            'data' => $booking->load([
                'sponsorshipBookingImages',
                'sponsorshipBookingProductImages',
                'sponsorEvent.exhibition',
            ])
        ], 201);
    }
    //===============================================================
    public function cancelSponsorship($sponsorship_id)//✅
    {
        $investor = Auth::user()->investor;

        $booking = SponsorshipBooking::findOrFail($sponsorship_id);

        if ($booking->investor_id !== $investor->id)
        {
            return response()->json([
                'message' => 'You are not allowed to cancel this sponsorships.'
            ], 403);
        }

        if ($booking->status === 'canceled')
        {
            return response()->json([
                'message' => 'This sponsorships is already canceled.'
            ], 400);
        }

        if ($booking->status === 'rejected')
        {
            return response()->json([
                'message' => 'Rejected sponsorships cannot be canceled.'
            ], 400);
        }

        if ($booking->status === 'ended')
        {
            return response()->json([
                'message' => 'Ended sponsorships cannot be canceled.'
            ], 400);
        }

        $booking->status = 'canceled';
        $booking->save();

        return response()->json([
            'message' => 'Sponsorship canceled successfully',
            'booking' => $booking
        ], 200);
    }
    //===============================================================
    //O
    //===============================================================
    public function getAllSponsorshipBookings($sponsor_event_id)//o
    {
        $sponsorship_bookings = SponsorshipBooking::where('sponsor_event_id', $sponsor_event_id)->get();

        $revenue = $sponsorship_bookings->clone()->whereIn('status', ['approved','ended'])->sum('total_price');

        $sponsorship_bookings_data = $sponsorship_bookings->map(function ($sp_bo)
        {
            return
            [
                'company_name' => $sp_bo->investor->company_name,
                'status' => $sp_bo->status,
                'company_email' => $sp_bo->investor->email,
                'company_phone' => $sp_bo->investor->phone,
                'total_price' => $sp_bo->total_price,
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
    // public function showSponsorEvent($sponsor_event_id)//عرض تفاصيل الفعالية الاعلانية للحجز
    // {
    //     $investor = Auth::user()->investor;
    //     $sponsor_event = SponsorEvent::with('exhibition')->find($sponsor_event_id);

    //     if (!$sponsor_event)
    //     {
    //         return response()->json(['message' => 'Sponsor event not found'], 404);
    //     }


    //     $options = [];
    //     for ($day = 1; $day <= $sponsor_event->duration_days; $day++)
    //     {

    //         $label = $day == $sponsor_event->duration_days
    //             ? "$day days (Full Event)"
    //             : "$day day" . ($day > 1 ? "s" : "");

    //         $options[] =
    //         [
    //             'days' => $day,
    //             'label' => $label,
    //             'total' => $sponsor_event->daily_price * $day,
    //         ];
    //     }


    //     $sponsor_event_data =
    //     [
    //         'id' => $sponsor_event->id,
    //         'name' => $sponsor_event->name,
    //         'type' => $sponsor_event->type,
    //         'exhibition_name' => $sponsor_event->exhibition->name,
    //         'start_time' => Carbon::parse($sponsor_event->start_time)->format('Y-m-d'),
    //         'place' => $sponsor_event->place,
    //         'time' => Carbon::parse($sponsor_event->start_time)->format('h:i').' _ '.Carbon::parse($sponsor_event->end_time)->format('h:i'),
    //         'duration_days' => $sponsor_event->duration_days,
    //         'description' => $sponsor_event->description,

    //         'participation_options' => $options,

    //         'company_name' => $investor->company_name,
    //         'company_website' => $investor->website,
    //         'company_phone' => $investor->user->phone,

    //     ];

    //     return response()->json([
    //         'sponsor_event' => $sponsor_event_data
    //     ], 200);
    // }

    //===============================================================
    // public function storeBooking(BookingshipSponsorEventRequest $request, $sponsor_event_id)//حجز رعاية/i
    // {
    //     $investor = Auth::user()->investor;
    //     $data = $request->validated();
    //     $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

    //     $total_price = $sponsor_event->daily_price * $request->days;

    //     $logoPath = null;
    //     if ($request->hasFile('logo'))
    //     {
    //         $logoPath = $request->file('logo')->store('sponsorship_company_logos', 'public');
    //     }


    //     $booking = SponsorshipBooking::create([
    //         'investor_id' => $investor->id,
    //         'sponsor_event_id' => $sponsor_event->id,
    //         'days' => $request->days,
    //         'total_price' => $total_price,
    //         'description' => $request->description,
    //         'logo' => $logoPath,
    //         'booked_at' => now()->format('Y-m-d'),
    //         'status' => 'pending',
    //     ]);

    //     // ============================
    //     if ($request->hasFile('materials'))
    //     {
    //         foreach ($request->file('materials') as $img)
    //         {
    //             $path = $img->store('sponsorship_materials', 'public');

    //             SponsorshipBookingImage::create([
    //                 'sponsorship_booking_id' => $booking->id,
    //                 'image' => $path,
    //             ]);
    //         }
    //     }
    //     // ============================
    //     if ($request->filled('product_images'))
    //     {
    //         foreach ($request->product_images as $item)
    //         {
    //             $path = $item['image']->store('sponsorship_product_images', 'public');

    //             SponsorshipBookingProductImage::create([
    //                 'sponsorship_booking_id' => $booking->id,
    //                 'product_name' => $item['name'],
    //                 'image' => $path,
    //             ]);
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Sponsorship booking created successfully.',
    //         'booking' => $booking->load([
    //             'sponsorshipBookingImages',
    //             'sponsorshipBookingProductImages'
    //         ])
    //     ], 201);
    // }
    //===============================================================
    // public function mySponsorshipBookings()//رعاياتي/i
    // {
    //     $investor = Auth::user()->investor;

    //     $bookings = SponsorshipBooking::where('investor_id', $investor->id)->get();

    //     $bookings_data = $bookings->map(function($bo)
    //     {
    //         return
    //         [
    //             'id' => $bo->id,
    //             'name' => $bo->sponsorEvent->name,
    //             'type' => $bo->sponsorEvent->type,
    //             'exhibition_name' => $bo->sponsorEvent->exhibition->name,
    //             'start_date' => Carbon::parse($bo->sponsorEvent->start_time)->format('Y-m-d'),
    //             'place' => $bo->sponsorEvent->place,
    //             'status' => $bo->status,
    //             'days' => $bo->days,

    //             'registered_count' => $bo->sponsorEvent->registered_count,
    //             'scanned_count' => $bo->sponsorEvent->scanned_count,
    //             'total_price' => $bo->total_price,
    //         ];
    //     });

    //     return response()->json([
    //         'bookings' => $bookings_data
    //     ], 200);
    // }
    //===============================================================
    // public function showSponsorshipBookings($booking_id)//i
    // {
    //     $investor = Auth::user()->investor;

    //     $booking = SponsorshipBooking::where('investor_id', $investor->id)->findOrFail($booking_id);

    //     $booking_data =
    //     [
    //         'id' => $booking->id,
    //         'name' => $booking->sponsorEvent->name,
    //         'status' => $booking->status,
    //         // 'day_rate' =>,

    //         'type' => $booking->sponsorEvent->type,
    //         'exhibition_name' => $booking->sponsorEvent->exhibition->name,
    //         'start_date' => Carbon::parse($booking->sponsorEvent->start_time)->format('Y-m-d'),
    //         'time' => Carbon::parse($booking->sponsorEvent->start_time)->format('h:i'),
    //         'place' => $booking->sponsorEvent->place,

    //         'days' => $booking->days,
    //         'total_price' => $booking->total_price,
    //         'booked_at' => $booking->booked_at,

    //         'registered_count' => $booking->sponsorEvent->registered_count,
    //         'scanned_count' => $booking->sponsorEvent->scanned_count,
    //     ];

    //     return response()->json([
    //         'booking' => $booking_data
    //     ], 200);
    // }
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

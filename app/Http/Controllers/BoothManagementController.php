<?php

namespace App\Http\Controllers;

use App\Models\BoothBooking;
use App\Models\BoothBookingImage;
use App\Models\Event;
use App\Models\ProductBookingImage;
use App\Models\SocialLink;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BoothManagementController extends Controller
{
    public function getBoothProfile($boothId)//✅
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'productBookingImages',
            'boothBookingImages',
            'investor.socialLinks'
        ])
        ->where('investor_id', $investor->id)
        ->where('booth_id', $boothId)
        ->first();

        if (!$booking)
        {
            return response()->json(['message' => 'Booth profile not found'], 404);
        }

        return response()->json([
            'data' => [
                'company_nature'    => $investor->activity_type,
                'services_products' => $booking->services_products,
                'headquarters'      => $investor->location,

                'social_links'      => $investor->socialLinks->pluck('link'),

                'product_images'    => $booking->productBookingImages->pluck('image_p'),

                'booth_images'      => $booking->boothBookingImages->pluck('image_b'),
            ]
        ], 200);
    }
    //==============================================================
    public function updateBoothProfile(Request $request, $booth_id)//✅
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::with([
            'productBookingImages',
            'boothBookingImages',
            'investor.socialLinks'
        ])
        ->where('investor_id', $investor->id)
        ->where('booth_id', $booth_id)
        ->first();

        if (!$booking)
        {
            return response()->json(['message' => 'Booth profile not found'], 404);
        }

        if ($request->filled('company_nature'))
        {
            $investor->activity_type = $request->company_nature;
            $investor->save();
        }

        if ($request->filled('headquarters'))
        {
            $investor->location = $request->headquarters;
            $investor->save();
        }

        if ($request->filled('services_products'))
        {
            $booking->services_products = $request->services_products;
        }

        //link
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

        //booth_images
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
        if ($request->hasFile('booth_image_files'))
        {
            foreach ($request->file('booth_image_files') as $img)
            {
                $path = $img->store('booth_booking_images', 'public');

                BoothBookingImage::create([
                    'booth_booking_id' => $booking->id,
                    'image_b' => $path,
                ]);
            }
        }

        //product_images
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
        if ($request->hasFile('product_image_files'))
        {
            foreach ($request->file('product_image_files') as $img)
            {
                $path = $img->store('product_booking_images', 'public');

                ProductBookingImage::create([
                    'booth_booking_id' => $booking->id,
                    'image_p' => $path,
                ]);
            }
        }

        //cover_image
        if ($request->hasFile('cover_image'))
        {
            $path = $request->file('cover_image')->store('booth_cover_images', 'public');
            $booking->cover_image = $path;
        }

        $booking->save();

        return response()->json([
            'message' => 'Booth profile updated successfully.',
            'data' => [
                'company_nature'    => $investor->activity_type,
                'services_products' => $booking->services_products,
                'headquarters'      => $investor->location,
                'social_links'      => $investor->socialLinks->pluck('link'),
                'product_images'    => $booking->productBookingImages->pluck('image_p'),
                'booth_images'      => $booking->boothBookingImages->pluck('image_b'),
                'cover_image'       => $booking->cover_image,
            ]
        ], 200);
    }
    //==============================================================
    public function uploadBoothCover(Request $request, $booth_id)//✅
    {
        $investor = Auth::user()->investor;

        $booking = BoothBooking::where('investor_id', $investor->id)
            ->where('booth_id', $booth_id)
            ->first();

        if (!$booking)
        {
            return response()->json(['message' => 'Booth profile not found'], 404);
        }

        $request->validate([
            'cover_image' => 'required|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        // حذف الغلاف القديم
        if ($booking->cover_image)
        {
            Storage::disk('public')->delete($booking->cover_image);
        }

        // رفع الغلاف الجديد
        $path = $request->file('cover_image')->store('booth_cover_images', 'public');

        $booking->cover_image = $path;
        $booking->save();

        return response()->json([
            'message' => 'Cover image uploaded successfully.',
            'cover_image' => $path
        ], 200);
    }
    //==============================================================
    public function getBoothEvents(Request $request)//✅
    {
        $investor = Auth::user()->investor;

        $request->validate([
            'booth_id' => 'required|integer|exists:booths,id'
        ]);

        $booth_id = $request->booth_id;

        $booking = BoothBooking::where('investor_id', $investor->id)
            ->where('booth_id', $booth_id)
            ->first();

        if (!$booking)
        {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $events = Event::with(['boothBooking.booth.exhibition', 'eventImages'])
            ->where('booth_booking_id', $booking->id)
            ->orderBy('date', 'asc')
            ->get();

        $data = $events->map(function ($ev)
        {
            $booth = $ev->boothBooking->booth;
            $exhibition = $booth->exhibition;

            return
            [
                'id' => $ev->id,
                'name' => $ev->name,
                'type' => $ev->type,

                'booth_number' => $booth->number,
                'exhibition_name' => $exhibition->name,

                'date' => $ev->date,
                'start_date' => $ev->date,
                'end_date' => Carbon::parse($ev->date)
                    ->copy()
                    ->addDays($ev->duration_days - 1)
                    ->format('Y-m-d'),

                'time' => $ev->time,

                'max_participants' => $ev->max_participants,
                'registered_count' => $ev->registered_count ?? 0,

                'status' => $ev->status,
                'description' => $ev->description,

                'requires_booking' => $ev->requires_booking,
                'has_bookable_seats' => $ev->has_bookable_seats,
                'total_seats' => $ev->total_seats,
                'booked_seats' => $ev->registered_count ?? 0,
                'sold_tickets' => $ev->sold_tickets ?? 0,

                'ticket_price' => $ev->ticket_price,
                'is_general_invitation' => $ev->is_general_invitation,

                'place' => $ev->place,
                'duration_days' => $ev->duration_days,

                'company_images' => $ev->eventImages->pluck('image')->toArray(),

                // الإحصائيات اليومية
                'current_day' => $ev->current_day ?? 1,
                'total_event_days' => $ev->duration_days,
                'daily_attendees' => json_decode($ev->daily_attendees, true) ?? [],
                'scanned_count' => $ev->scanned_count ?? 0,

                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $ev->id)
                    ->where('favoritable_type', Event::class)
                    ->exists(),
            ];
        });

        return response()->json([
            'data' => $data
        ], 200);
    }
}

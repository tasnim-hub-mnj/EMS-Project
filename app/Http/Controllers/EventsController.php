<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingshipSponsorEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Booth;
use App\Models\Event;
use App\Models\BoothBooking;
use App\Models\EventImage;
use App\Models\EventTicket;
use App\Models\Exhibition;
use App\Models\SponsorEvent;
use App\Models\SponsorshipBooking;
use App\Models\SponsorshipBookingImage;
use App\Models\SponsorshipBookingProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventsController extends Controller
{
    //===========================================================
    //Event//i
    //===========================================================
    public function createEvent(StoreEventRequest $request)//✅
    {
        $investor = Auth::user()->investor;

        $data = $request->validated();

        $booking = BoothBooking::where('investor_id', $investor->id)
            ->where('booth_id', $data['booth_id'])
            ->where('status', 'approved')
            ->first();

        if (!$booking)
        {
            return response()->json([
                'message' => 'You do not have an approved booking for this booth.'
            ], 400);
        }

        //التحقق أن الفعالية ضمن فترة الحجز
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        if ($start < $booking->start_date || $end > $booking->end_date)
        {
            return response()->json([
                'message' => 'Event dates must be within your booth booking period.'
            ], 400);
        }

        $duration_days = $start->diffInDays($end) + 1;

        $status = $start->isToday() ? 'ongoing' : 'upcoming';//هل تبدأ اليوم ام بعدين

        $event = Event::create([
            'booth_booking_id' => $booking->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'date' => $data['start_date'],
            'time' => $data['time'],
            'place' => $booking->booth->number . ' - ' . $booking->booth->location,
            'duration_days' => $duration_days,
            'description' => $data['description'],
            'is_general_invitation' => $data['is_general_invitation'] ?? true,
            'has_bookable_seats' => $data['has_bookable_seats'] ?? false,
            'requires_booking' => $data['requires_booking'] ?? false,
            'max_participants' => $data['max_participants'],
            'ticket_price' => $data['ticket_price'] ?? 0,
            'total_seats' => $data['total_seats'] ?? $data['max_participants'],
            'video_promo_url' => $data['video_promo_url'],
            'status' => $status,
        ]);

        $images = [];
        if ($request->hasFile('images'))
        {
            foreach ($request->file('images') as $img)
            {
                $path = $img->store('event_images', 'public');

                $images[] = EventImage::create([
                    'event_id' => $event->id,
                    'image' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $event,
            'images' => $images
        ], 201);
    }
    //===========================================================
    public function getInvestorEvents()//فعالياتي//✅
    {
        $investor = Auth::user()->investor;

        $events = Event::with([
            'boothBooking.booth.exhibition',
            'eventImages'
        ])
        ->whereHas('boothBooking', function ($q) use ($investor)
        {
            $q->where('investor_id', $investor->id);
        })
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
    //===========================================================
    public function getTicketRequests($event_id)//✅
    {
        $investor = Auth::user()->investor;

        $event = Event::where('id', $event_id)
            ->where('investor_id', $investor->id)
            ->firstOrFail();

        $tickets = EventTicket::with('visitor.user')
            ->where('event_id', $event_id)
            ->orderBy('booked_at', 'desc')
            ->get();

        $data = $tickets->map(function ($ticket)
        {
            return
            [
                'id' => $ticket->id,
                'visitor_name' => $ticket->visitor->first_name . ' ' . $ticket->visitor->last_name,
                'visitor_phone' => $ticket->visitor->user->phone,
                'visitor_email' => $ticket->visitor->user->email,
                'status' => $ticket->status,
                'amount' => $ticket->amount,
                'booked_at' => $ticket->booked_at,
                'qr_code' => $ticket->qr_code,
            ];
        });

        return response()->json([
            'data' => $data
        ], 200);
    }
    //===========================================================
    public function ticketRequestAction(Request $request,$event_id,$ticket_id)//✅
    {
        $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        $investor = Auth::user()->investor;

        $event = Event::where('id', $event_id)
            ->where('investor_id', $investor->id)
            ->firstOrFail();

        $ticket = EventTicket::where('id', $ticket_id)
            ->where('event_id', $event_id)
            ->firstOrFail();

        //approve
        if ($request->action === 'approve')
        {
            if ($ticket->status === 'approved')
            {
                return response()->json(['message' => 'Ticket already approved'], 400);
            }

            if ($event->total_seats == 0)
            {
                return response()->json(['message' => 'No seats available'], 400);
            }

            // توليد QR
            $qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=ticket_id:" . $ticket->id;

            $ticket->update([
                'status' => 'approved',
                'qr_code' => $qr,
            ]);

            $event->increment('registered_count');//+1
            $event->decrement('total_seats');//-1

            return response()->json([
                'message' => 'Ticket approved successfully',
                'data' => $ticket
            ], 200);
        }

        //reject
        if ($request->action === 'reject')
        {
            if ($ticket->status === 'rejected')
            {
                return response()->json(['message' => 'Ticket already rejected'], 400);
            }

            //التراجع
            if ($ticket->status === 'approved')
            {
                $event->decrement('registered_count');
                $event->increment('total_seats');
            }

            $ticket->update([
                'status' => 'rejected',
                'qr_code' => null,
            ]);

            return response()->json([
                'message' => 'Ticket rejected successfully',
                'data' => $ticket
            ], 200);
        }
    }
    //===========================================================
    //SponsorEvent//i
    //===========================================================
    public function getSponsorEvents(Request $request)//✅
    {
        $page       = $request->query('page', 1);
        $perPage    = $request->query('per_page', 20);
        $type       = $request->query('type');
        $dateStart  = $request->query('date_start');
        $dateEnd    = $request->query('date_end');
        $search     = $request->query('search');

        $query = SponsorEvent::with(['exhibition', 'sponsorEventImages'])
            ->where('copy_status', 'active');

        if ($type)
        {
            $query->where('type', $type);
        }

        if ($dateStart)
        {
            $query->whereDate('start_time', '>=', $dateStart);
        }

        if ($dateEnd)
        {
            $query->whereDate('start_time', '<=', $dateEnd);
        }

        if ($search)
        {
            $query->where(function ($q) use ($search)
            {
                $q->where('name', 'LIKE', "%$search%")
                ->orWhereHas('exhibition', function ($ex) use ($search)
                {
                    $ex->where('name', 'LIKE', "%$search%");
                });
            });
        }

        $events = $query->orderBy('start_time', 'asc')
                    ->paginate($perPage, ['*'], 'page', $page);

        $eventsData = $events->map(function ($ev)
        {
            return
            [
                'id' => $ev->id,
                'name' => $ev->name,
                'type' => $ev->type,

                'exhibition_id' => $ev->exhibition_id,
                'exhibition_name' => $ev->exhibition->name,
                'exhibition_image_url' => optional($ev->exhibition->exhibitionImages->first())->image,

                'date' => Carbon::parse($ev->start_time)->format('Y-m-d'),
                'start_time' => Carbon::parse($ev->start_time)->format('H:i'),
                'end_time' => Carbon::parse($ev->end_time)->format('H:i'),

                'place' => $ev->place,
                'listing_days' => $ev->duration_days ?? 1,
                'description' => $ev->description,

                //if exsist
                'duration_options' => $ev->duration_options
                    ? json_decode($ev->duration_options, true)
                    : [],

                'images' => $ev->sponsorEventImages->pluck('image')->toArray(),

                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $ev->id)
                    ->where('favoritable_type', SponsorEvent::class)
                    ->exists(),
            ];
        });

        return response()->json([
            'data' => $eventsData,
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ], 200);
    }
    //------------------SponsorshipBooking-----------------------------------------
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
                'booked_at' => $bk->booked_at,

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
                    'sp_b_id' => $booking->id,
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
                    'sp_b_id' => $booking->id,
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
    //===========================================================
    // public function show($event_id)//عرض فعالية واحدة
    // {
    //     $event = Event::findOrFail($event_id);

    //     $event_data =
    //         [
    //             'name' => $event->name,
    //             'type' => $event->type,
    //             'status' => $event->status,
    //             'current_day' => $event->current_day,
    //             'place' => $event->place,
    //             'exhibition_name' => $event->boothBooking->booth->exhibition->name,
    //             'date' => $event->date,
    //             'time' => $event->time,
    //             'duration_days' => $event->time,
    //             'description' => $event->description,
    //             'event_image' => $event->eventImages,
    //         ];

    //     return response()->json([
    //         'event' => $event_data
    //     ], 200);
    // }
    // //===========================================================
    // public function getStatisticsEvent($event_id)//احصائيات فعالية
    // {
    //     $event = Event::findOrFail($event_id);
    //     $statistics =
    //         [
    //             'max_participants' => $event->max_participants,
    //             'registered_count' => $event->registered_count,
    //             'total_seats' => $event->total_seats,
    //             'scanned_count' => $event->scanned_count,
    //             'occupancy_rate' => $event->max_participants > 0
    //                 ? round(($event->registered_count / $event->max_participants) * 100, 2)
    //                 : 0,
    //             'ticket_price' => $event->ticket_price,
    //         ];

    //     return response()->json([
    //         'statistics' => $statistics
    //     ], 200);
    // }
    //===========================================================
    // public function getTicketsEvent($event_id)
    // {
    //     $tickets = EventTicket::where('event_id', $event_id)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     $tickets_data = $tickets->map(function ($ticket)
    //     {
    //         return
    //             [
    //                 'visitor_name' => $ticket->visitor->first_name . ' ' . $ticket->visitor->last_name,
    //                 'status' => $ticket->status,
    //                 'booked_at' => $ticket->booked_at,
    //                 'phone' => $ticket->visitor->user->phone,
    //                 'email' => $ticket->visitor->user->email,
    //             ];

    //     });

    //     return response()->json([
    //         'tickets' => $tickets_data
    //     ], 200);
    // }
    // //===========================================================
    // public function approveTicket($ticket_id)
    // {
    //     $ticket = EventTicket::findOrFail($ticket_id);
    //     $event = $ticket->event;

    //     // منع قبول تذكرة مقبولة مسبقًا
    //     if ($ticket->status === 'approved') {
    //         return response()->json([
    //             'message' => 'Ticket already approved'
    //         ], 400);
    //     }

    //     // اذا مافي مقاعد متاحة
    //     if ($event->total_seats == 0) {
    //         return response()->json([
    //             'message' => 'No seats available'
    //         ], 400);
    //     }

    //     // توليد QR Code
    //     $qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=ticket_id:" . $ticket->id;

    //     // قبول+توليد qr
    //     $ticket->update([
    //         'status' => 'approved',
    //         'qr_code' => $qr,
    //     ]);

    //     $event->increment('registered_count');//+1
    //     $event->decrement('total_seats');//-1

    //     return response()->json([
    //         'message' => 'Ticket approved successfully',
    //         'ticket' => $ticket
    //     ], 200);
    // }
    // //===========================================================
    // public function rejectTicket($ticket_id)
    // {
    //     $ticket = EventTicket::findOrFail($ticket_id);
    //     $event = $ticket->event;

    //     // منع رفض تذكرة مرفوضة مسبقًا
    //     if ($ticket->status === 'rejected') {
    //         return response()->json([
    //             'message' => 'Ticket already rejected'
    //         ], 400);
    //     }

    //     // إذا كانت التذكرة مقبولة مسبقًا → يجب إعادة المقاعد
    //     if ($ticket->status === 'approved') {
    //         $event->decrement('registered_count');
    //         $event->increment('total_seats');
    //     }

    //     $ticket->update(['status' => 'rejected']);

    //     return response()->json([
    //         'message' => 'Ticket rejected successfully',
    //         'ticket' => $ticket
    //     ], 200);
    // }
    //===========================================================
    //===========================================================
    // public function update(Request $request, $id)//تعديل فعالية
    // {
    //     $investor = Auth::user()->investor;

    //     $event = Event::where('investor_id', $investor->id)->findOrFail($id);

    //     $event->update($request->all());

    //     return response()->json([
    //         'message' => 'Event Updated',
    //         'event'   => $event
    //     ], 200);
    // }
    //===========================================================
    // public function destroy($id)//حذف فعالية
    // {
    //     $investor = Auth::user()->investor;

    //     $event = Event::where('investor_id', $investor->id)->findOrFail($id);

    //     $event->delete();

    //     return response()->json([
    //         'message' => 'Event Deleted'
    //     ], 200);
    // }
    //===========================================================
    // public function getInvestorEvents()//فعالياتي
    // {
    //     $investor = Auth::user()->investor;

    //     $events = Event::whereHas('boothBooking', function ($q) use ($investor)
    //     {
    //         $q->where('investor_id', $investor->id);
    //     })->orderBy('date', 'asc')
    //         ->get();

    //     $events_data = $events->map(function ($event)
    //     {
    //         return
    //             [
    //                 'name' => $event->name,
    //                 'type' => $event->type,
    //                 'date_time' => $event->date . '_' . $event->time,
    //                 'exhibition_name' => $event->boothBooking->booth->exhibition->name,
    //                 'booking_rate' => $event->max_participants > 0
    //                     ? round(($event->registered_count / $event->max_participants) * 100, 2)
    //                     : 0,
    //                 'event_image' => $event->eventImages,
    //             ];

    //     });

    //     return response()->json([
    //         'events' => $events_data
    //     ], 200);
    // }
    //===========================================================
    // public function getBoothEvents($boothId)//عرض فعاليات حجز معين(الجناح)
    // {
    //     $booking = BoothBooking::where('booth_id', $boothId)->first();

    //     if (!$booking)
    //     {
    //         return response()->json([
    //             'message' => 'Booth booking not found'
    //         ], 404);
    //     }

    //     $events = Event::where('booth_booking_id', $booking->id)
    //         ->orderBy('date', 'asc')
    //         ->get();

    //     $events_data = $events->map(function ($event)
    //     {
    //         return
    //             [
    //                 'name' => $event->name,
    //                 'type' => $event->type,
    //                 'date' => $event->date,
    //                 'time' => $event->time,
    //                 'status' => $event->status,
    //                 'registered_count' => $event->registered_count,
    //             ];

    //     });

    //     return response()->json([
    //         'events' => $events_data
    //     ], 200);
    // }//-------------------تم بالحجز
    //===========================================================
    // public function store(StoreEventRequest $request, $exhibition_id, $booth_id)
    // {
    //     $investor = Auth::user()->investor;

    //     //التحقق أن المستثمر لديه حجز موافَق عليه في هذا الجناح داخل هذا المعرض
    //     $booking = BoothBooking::where('investor_id', $investor->id)
    //         ->where('booth_id', $booth_id)
    //         ->where('status', 'approved')
    //         ->first();

    //     if (!$booking) {
    //         return response()->json([
    //             'message' => 'You do not have an approved booking for this booth.'
    //         ], 400);
    //     }

    //     //التحقق أن الجناح ينتمي للمعرض الذي اختاره المستثمر
    //     $booth = Booth::where('id', $booth_id)
    //         ->where('exhibition_id', $exhibition_id)
    //         ->first();

    //     if (!$booth) {
    //         return response()->json([
    //             'message' => 'This booth does not belong to the selected exhibition.'
    //         ], 400);
    //     }

    //     // التحقق أن تاريخ الفعالية ضمن فترة الحجز
    //     if ($request->date < $booking->start_date || $request->date > $booking->end_date) {
    //         return response()->json([
    //             'message' => 'Event date must be within your booth booking period.'
    //         ], 400);
    //     }

    //     // التحقق أن مدة الفعالية لا تتجاوز نهاية الحجز
    //     $eventStart = Carbon::parse($request->date);
    //     $eventEnd = $eventStart->copy()->addDays($request->duration_days - 1);
    //     if ($eventEnd > $booking->end_date) {
    //         return response()->json([
    //             'message' => 'Event duration exceeds your booth booking period.'
    //         ], 400);
    //     }


    //     //تحديد حالة الفعالية بناءً على تاريخ اليوم
    //     $status = $request->date == now()->toDateString() ? 'ongoing' : 'upcoming';

    //     $event = Event::create([
    //         'booth_booking_id' => $booking->id,
    //         'name' => $request->name,
    //         'type' => $request->type,
    //         'date' => $request->date,
    //         'time' => $request->time,
    //         'place' => $booth->number . ' - ' . $booth->location,
    //         'duration_days' => $request->duration_days,
    //         'description' => $request->description,
    //         'is_general_invitation' => $request->is_general_invitation ?? false,
    //         'has_bookable_seats' => $request->has_bookable_seats ?? false,
    //         'requires_booking' => $request->requires_booking ?? false,
    //         'max_participants' => $request->max_participants,
    //         'ticket_price' => $request->ticket_price ?? 0,
    //         'total_seats' => $request->max_participants,
    //         'status' => $status,
    //     ]);

    //     $images = [];
    //     if ($request->hasFile('image')) {
    //         foreach ($request->file('image') as $img) {
    //             $path = $img->store('event_images', 'public');
    //             $images[] = EventImage::create([
    //                 'event_id' => $event->id,
    //                 'image' => $path,
    //             ]);
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Event created successfully',
    //         'event' => $event,
    //         'images' => $images
    //     ], 201);
    // }
    //===========================================================
    //  public function investorExhibitions()//قائمة المعارض يلي حاجز فيها
    // {
    //     $investor = Auth::user()->investor;

    //     $exhibitions = Exhibition::whereHas('booths.bookings', function ($q) use ($investor) {
    //         $q->where('investor_id', $investor->id)
    //             ->where('status', 'approved');
    //     })->get();

    //     return response()->json(['exhibitions' => $exhibitions]);
    // }
    // //===========================================================
    // public function investorBooths($exhibition_id)//قائمة الاجنحة المحجوزة في هذا المعرض
    // {
    //     $investor = Auth::user()->investor;

    //     $booths = Booth::where('exhibition_id', $exhibition_id)
    //         ->whereHas('bookings', function ($q) use ($investor) {
    //             $q->where('investor_id', $investor->id)
    //                 ->where('status', 'approved');
    //         })
    //         ->get();

    //     return response()->json(['booths' => $booths]);
    // }
    //===========================================================
    //=====================الزائر===============================
    public function getLatestEvents(Request $request)
    {
        $visitor = $request->user()->visitor;

        $isLatest = $request->query('latest', 0);
        $perPage = $request->query('per_page', 6);

        $query = Event::with([
            'boothBooking.booth.exhibition',
            'images'
        ]);

        if ($isLatest == 1) {
            $query->latest();
        }

        $events = $query->take($perPage)->get();

        $formattedEvents = $events->map(function ($event) use ($visitor) {
            $booking = $event->boothBooking;
            $booth = $booking?->booth;
            $exhibition = $booth?->exhibition;

            $isRegistered = false;
            if ($visitor) {
                $isRegistered = \DB::table('event_tickets')
                    ->where('visitor_id', $visitor->id)
                    ->where('event_id', $event->id)
                    ->exists();
            }

            $totalSeats = $event->total_seats ?? 0;
            $registeredCount = $event->registered_count ?? 0;
            $availableSeats = max(0, $totalSeats - $registeredCount);

            return [
                'id' => $event->id,
                'exhibition_id' => $exhibition?->id,
                'name' => $event->name,
                'type' => $event->type,
                'hall' => $booth?->hall_name ?? 'الرئيسية',
                'booth' => $booth?->booth_number ?? 'غير محدد',
                'company_name' => $booking?->company_name ?? $event->by ?? 'الجهة المنظمة',
                'start_time' => $event->date && $event->start_time
                    ? \Carbon\Carbon::parse($event->date . ' ' . $event->start_time)->toIso8601String()
                    : null,
                'end_time' => $event->date && $event->end_time
                    ? \Carbon\Carbon::parse($event->date . ' ' . $event->end_time)->toIso8601String()
                    : null,
                'description' => $event->description,
                'image_url' => $event->images?->first()?->image_url ?? $event->video_promo_url ?? null,
                'speaker_name' => $event->by ?? 'متحدث رسمي',
                'available_seats' => $availableSeats,
                'total_seats' => $totalSeats,
                'is_registered' => $isRegistered,
                'exhibition_name' => $exhibition?->name ?? 'معرض غير محدد',
            ];
        });

        return response()->json($formattedEvents, 200);
    }
    //=====================================================
    public function getEventById(Request $request, $id)
    {
        $visitor = $request->user()->visitor;

        $event = Event::with([
            'boothBooking.booth.exhibition',
            'images'
        ])->findOrFail($id);

        $booking = $event->boothBooking;
        $booth = $booking?->booth;
        $exhibition = $booth?->exhibition;

        $isRegistered = false;
        if ($visitor) {
            $isRegistered = \DB::table('event_tickets')
                ->where('visitor_id', $visitor->id)
                ->where('event_id', $event->id)
                ->exists();
        }

        $totalSeats = $event->total_seats ?? 0;
        $registeredCount = $event->registered_count ?? 0;
        $availableSeats = max(0, $totalSeats - $registeredCount);

        $formattedEvent = [
            'id' => $event->id,
            'exhibition_id' => $exhibition?->id,
            'name' => $event->name,
            'type' => $event->type,
            'hall' => $booth?->hall_name ?? 'الرئيسية',
            'booth' => $booth?->booth_number ?? 'غير محدد',
            'company_name' => $booking?->company_name ?? $event->by ?? 'الجهة المنظمة',
            'start_time' => $event->date && $event->start_time
                ? \Carbon\Carbon::parse($event->date . ' ' . $event->start_time)->toIso8601String()
                : null,
            'end_time' => $event->date && $event->end_time
                ? \Carbon\Carbon::parse($event->date . ' ' . $event->end_time)->toIso8601String()
                : null,
            'description' => $event->description,
            'image_url' => $event->images?->first()?->image_url ?? $event->video_promo_url ?? null,
            'speaker_name' => $event->by ?? 'متحدث رسمي',
            'available_seats' => $availableSeats,
            'total_seats' => $totalSeats,
            'is_registered' => $isRegistered,
            'exhibition_name' => $exhibition?->name ?? 'معرض غير محدد',
        ];

        return response()->json($formattedEvent, 200);
    }
    //=====================================================

}

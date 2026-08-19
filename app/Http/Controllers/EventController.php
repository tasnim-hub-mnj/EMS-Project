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

class EventController extends Controller
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

        // منع إنشاء فعالية بنفس التاريخ والوقت لنفس الحجز
        $conflict = Event::where('booth_booking_id', $booking->id)
            ->where('start_date', $data['start_date'])
            ->where('time', $data['time'])
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'You already have an event at the same date and time for this booth.'
            ], 409);
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

        //الحالة -_-
        $eventDate = Carbon::parse($data['start_date']);
        $eventDateTime = Carbon::parse($data['start_date'] . ' ' . $data['time']);
        $now = now();

        // إذا كانت الفعالية اليوم
        if ($eventDate->isToday()) {

            // إذا وقت الفعالية إجا أو مرّ → جارية
            if ($eventDateTime->lessThanOrEqualTo($now)) {
                $status = 'ongoing';
            }
            // إذا وقت الفعالية لسا ما إجا → قادمة
            else {
                $status = 'upcoming';
            }
        }
        // إذا كانت الفعالية بعد اليوم → قادمة
        else {
            $status = 'upcoming';
        }


        // شرط: إذا كانت الفعالية ليوم واحد وفي نفس تاريخ اليوم، يجب أن يكون وقت الفعالية بعد 6 ساعات من الآن
        if ($start->isToday() && $start->equalTo($end))
        {

            $eventTime = Carbon::parse($data['time']); // وقت الفعالية
            $minAllowedTime = now()->addHours(6);      // الوقت الحالي + 6 ساعات

            if ($eventTime->lessThan($minAllowedTime))
            {
                return response()->json([
                    'message' => 'Event time must be at least 6 hours from now for same‑day events.'
                ], 422);
            }
        }

        $event = Event::create([
            'booth_booking_id' => $booking->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
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
            'video_promo_url' => $data['video_promo_url'] ?? null,
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

        $data =
        [
            'booth_booking_id' => $booking->id,
            'name' => $event->name,
            'type' =>  $event->type,
            'booth_number' => $booking->booth->number,
            'exhibition_name' => $booking->booth->exhibition->name,
            'start_date' =>  $event->start_date,
            'end_date' =>  $event->end_date,
            'time' =>  $event->time,
            'place' => $booking->booth->number . ' - ' . $booking->booth->location,
            'duration_days' => $event->duration_days,
            'description' => $event->description,
            'is_general_invitation' => $event->is_general_invitation,
            'has_bookable_seats' => $event->has_bookable_seats,
            'requires_booking' => $event->requires_booking,
            'max_participants' => $event->max_participants,
            'ticket_price' => $event->ticket_price,
            'total_seats' => $event->total_seats,
            'video_promo_url' => $event->video_promo_url,
            'status' => $event->status,
        ];



        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $data,
            'image' => $images
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
            ->whereHas('boothBooking', function ($q) use ($investor) {
                $q->where('investor_id', $investor->id);
            })
            ->orderBy('start_date', 'asc')
            ->get();

        $data = $events->map(function ($ev) {
            $booth = $ev->boothBooking->booth;
            $exhibition = $booth->exhibition;

            return
                [
                    'id' => $ev->id,
                    'name' => $ev->name,
                    'type' => $ev->type,

                    'booth_number' => $booth->number,
                    'exhibition_name' => $exhibition->name,

                    'start_date' => Carbon::parse($ev->start_date)->format('Y-m-d'),
                    'end_date' => Carbon::parse($ev->end_date)->format('Y-m-d'),

                    'time' => Carbon::parse($ev->start_date)->format('H:i') . ' - ' . Carbon::parse($ev->end_date)->format('H:i'),

                    'max_participants' => $ev->max_participants,
                    'registered_count' => $ev->registered_count ?? 0,

                    'status' => $ev->status,
                    'description' => $ev->description,

                    'requires_booking' => $ev->requires_booking,
                    'has_bookable_seats' => $ev->has_bookable_seats,
                    'total_seats' => $ev->total_seats,
                    'booked_seats' => $ev->registered_count ?? 0,
                    // 'sold_tickets' => $ev->sold_tickets ?? 0,

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
            ->firstOrFail();

        if (!$event->boothBooking || $event->boothBooking->investor_id !== $investor->id) {
            return response()->json([
                'message' => 'You do not can access this event ticket.'
            ], 400);
        }

        $tickets = EventTicket::with('visitor.user')
            ->where('event_id', $event_id)
            ->orderBy('booked_at', 'desc')
            ->get();

        $data = $tickets->map(function ($ticket) {
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
    public function ticketRequestAction(Request $request, $event_id, $ticket_id)//✅
    {
        $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        $investor = Auth::user()->investor;

        $event = Event::where('id', $event_id)
            ->firstOrFail();

        if (!$event->boothBooking || $event->boothBooking->investor_id !== $investor->id) {
            return response()->json([
                'message' => 'You do not can access this event ticket.'
            ], 400);
        }

        $ticket = EventTicket::where('id', $ticket_id)
            ->where('event_id', $event_id)
            ->firstOrFail();

        //approve
        if ($request->action === 'approve') {
            if ($ticket->status === 'approved') {
                return response()->json(['message' => 'Ticket already approved'], 400);
            }

            if ($event->total_seats == 0) {
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
        if ($request->action === 'reject') {
            if ($ticket->status === 'rejected') {
                return response()->json(['message' => 'Ticket already rejected'], 400);
            }

            //التراجع
            if ($ticket->status === 'approved') {
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
        $user = auth('sanctum')->user();
        $visitor = $user?->visitor;

        $perPage = (int) $request->query('per_page', 6);
        $isLatest = $request->query('latest', 0);

        $query = Event::with([
            'boothBooking.booth.exhibition',
            'boothBooking.investor',
            'eventImages' // تم تعديلها من images إلى eventImages
        ]);

        if ($isLatest == 1) {
            $query->latest();
        }

        $events = $query->paginate($perPage);

        $registeredEventIds = [];
        if ($visitor) {
            $registeredEventIds = \DB::table('event_tickets')
                ->where('visitor_id', $visitor->id)
                ->whereIn('event_id', $events->pluck('id'))
                ->pluck('event_id')
                ->toArray();
        }

        $formattedEvents = $events->getCollection()->map(function ($event) use ($registeredEventIds) {
            $booking = $event->boothBooking;
            $booth = $booking?->booth;
            $exhibition = $booth?->exhibition;

            $totalSeats = (int) ($event->max_participants ?? $event->total_seats ?? 0);
            $registeredCount = (int) ($event->registered_count ?? 0);

            $startTime = null;
            if ($event->start_date) {
                $dateTimeString = $event->time
                    ? $event->start_date . ' ' . $event->time
                    : $event->start_date;
                $startTime = \Carbon\Carbon::parse($dateTimeString)->toIso8601String();
            }

            $endTime = null;
            if ($event->end_date) {
                $endTime = \Carbon\Carbon::parse($event->end_date)->toIso8601String();
            }

            return [
                'id' => (int) $event->id,
                'exhibition_id' => $exhibition?->id ? (int) $exhibition->id : null,
                'name' => $event->name ?? '',
                'type' => $event->type ?? '',
                'hall' => $booth?->location ?? $event->place ?? '',
                'booth' => $booth?->number ?? $booth?->name ?? '',
                'company_name' => $booking?->investor?->company_name ?? '',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'description' => $event->description ?? '',
                'image_url' => $event->eventImages?->first()?->image_url ?? $event->video_promo_url ?? null,
                'speaker_name' => $event->speaker_name ?? '',
                'available_seats' => max(0, $totalSeats - $registeredCount),
                'total_seats' => $totalSeats,
                'is_registered' => in_array($event->id, $registeredEventIds),
                'exhibition_name' => $exhibition?->name ?? '',
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formattedEvents,
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ]
        ], 200);
    }
    //=====================================================
    public function getEventById(Request $request, $id)
    {
        $visitor = $request->user()?->visitor;

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

        $totalSeats = (int) ($event->total_seats ?? 0);
        $registeredCount = (int) ($event->registered_count ?? 0);
        $availableSeats = max(0, $totalSeats - $registeredCount);

        $formattedEvent = [
            'id' => (int) $event->id,
            'exhibition_id' => $exhibition?->id ? (int) $exhibition->id : null,
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

        return response()->json([
            'status' => true,
            'data' => $formattedEvent
        ], 200);
    }
    //=====================================================

}

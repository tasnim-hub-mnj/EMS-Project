<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Models\Booth;
use App\Models\Event;
use App\Models\BoothBooking;
use App\Models\EventImage;
use App\Models\EventTicket;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function store(StoreEventRequest $request, $exhibition_id, $booth_id)
    {
        $investor = Auth::user()->investor;

        //التحقق أن المستثمر لديه حجز موافَق عليه في هذا الجناح داخل هذا المعرض
        $booking = BoothBooking::where('investor_id', $investor->id)
            ->where('booth_id', $booth_id)
            ->where('status', 'approved')
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'You do not have an approved booking for this booth.'
            ], 400);
        }

        //التحقق أن الجناح ينتمي للمعرض الذي اختاره المستثمر
        $booth = Booth::where('id', $booth_id)
            ->where('exhibition_id', $exhibition_id)
            ->first();

        if (!$booth) {
            return response()->json([
                'message' => 'This booth does not belong to the selected exhibition.'
            ], 400);
        }

        // التحقق أن تاريخ الفعالية ضمن فترة الحجز
        if ($request->date < $booking->start_date || $request->date > $booking->end_date) {
            return response()->json([
                'message' => 'Event date must be within your booth booking period.'
            ], 400);
        }

        // التحقق أن مدة الفعالية لا تتجاوز نهاية الحجز
        $eventStart = Carbon::parse($request->date);
        $eventEnd = $eventStart->copy()->addDays($request->duration_days - 1);
        if ($eventEnd > $booking->end_date) {
            return response()->json([
                'message' => 'Event duration exceeds your booth booking period.'
            ], 400);
        }


        //تحديد حالة الفعالية بناءً على تاريخ اليوم
        $status = $request->date == now()->toDateString() ? 'ongoing' : 'upcoming';

        $event = Event::create([
            'booth_booking_id' => $booking->id,
            'name' => $request->name,
            'type' => $request->type,
            'date' => $request->date,
            'time' => $request->time,
            'place' => $booth->number . ' - ' . $booth->location,
            'duration_days' => $request->duration_days,
            'description' => $request->description,
            'is_general_invitation' => $request->is_general_invitation ?? false,
            'has_bookable_seats' => $request->has_bookable_seats ?? false,
            'requires_booking' => $request->requires_booking ?? false,
            'max_participants' => $request->max_participants,
            'ticket_price' => $request->ticket_price ?? 0,
            'total_seats' => $request->max_participants,
            'status' => $status,
        ]);

        $images = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $img) {
                $path = $img->store('event_images', 'public');
                $images[] = EventImage::create([
                    'event_id' => $event->id,
                    'image' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event,
            'images' => $images
        ], 201);
    }
    //===========================================================
    public function investorExhibitions()//قائمة المعارض يلي حاجز فيها
    {
        $investor = Auth::user()->investor;

        $exhibitions = Exhibition::whereHas('booths.bookings', function ($q) use ($investor) {
            $q->where('investor_id', $investor->id)
                ->where('status', 'approved');
        })->get();

        return response()->json(['exhibitions' => $exhibitions]);
    }
    //===========================================================
    public function investorBooths($exhibition_id)//قائمة الاجنحة المحجوزة في هذا المعرض
    {
        $investor = Auth::user()->investor;

        $booths = Booth::where('exhibition_id', $exhibition_id)
            ->whereHas('bookings', function ($q) use ($investor) {
                $q->where('investor_id', $investor->id)
                    ->where('status', 'approved');
            })
            ->get();

        return response()->json(['booths' => $booths]);
    }
    //===========================================================
    public function getInvestorEvents()//فعالياتي//i
    {
        $investor = Auth::user()->investor;

        $events = Event::whereHas('boothBooking', function ($q) use ($investor) {
            $q->where('investor_id', $investor->id);
        })->orderBy('date', 'asc')
            ->get();

        $events_data = $events->map(function ($event) {
            return
                [
                    'name' => $event->name,
                    'type' => $event->type,
                    'date_time' => $event->date . '_' . $event->time,
                    'exhibition_name' => $event->boothBooking->booth->exhibition->name,
                    'booking_rate' => $event->max_participants > 0
                        ? round(($event->registered_count / $event->max_participants) * 100, 2)
                        : 0,
                    'event_image' => $event->eventImages,
                ];

        });

        return response()->json([
            'events' => $events_data
        ], 200);
    }
    //===========================================================
    public function show($event_id)//عرض فعالية واحدة
    {
        $event = Event::findOrFail($event_id);

        $event_data =
            [
                'name' => $event->name,
                'type' => $event->type,
                'status' => $event->status,
                'current_day' => $event->current_day,
                'place' => $event->place,
                'exhibition_name' => $event->boothBooking->booth->exhibition->name,
                'date' => $event->date,
                'time' => $event->time,
                'duration_days' => $event->time,
                'description' => $event->description,
                'event_image' => $event->eventImages,
            ];

        return response()->json([
            'event' => $event_data
        ], 200);
    }
    //===========================================================
    public function getBoothEvents($booth_booking_id)//عرض فعاليات حجز معين(الجناح)
    {
        $booking = BoothBooking::with(['booth', 'booth.exhibition'])
            ->find($booth_booking_id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booth booking not found'
            ], 404);
        }

        $events = Event::where('booth_booking_id', $booth_booking_id)
            ->orderBy('date', 'asc')
            ->get();

        $events_data = $events->map(function ($event) {
            return
                [
                    'name' => $event->name,
                    'type' => $event->type,
                    'date' => $event->date,
                    'time' => $event->time,
                    'status' => $event->status,
                    'registered_count' => $event->registered_count,
                ];

        });

        return response()->json([
            'events' => $events_data
        ], 200);
    }
    //===========================================================
    public function getStatisticsEvent($event_id)//احصائيات فعالية
    {
        $event = Event::findOrFail($event_id);
        $statistics =
            [
                'max_participants' => $event->max_participants,
                'registered_count' => $event->registered_count,
                'total_seats' => $event->total_seats,
                'scanned_count' => $event->scanned_count,
                'occupancy_rate' => $event->max_participants > 0
                    ? round(($event->registered_count / $event->max_participants) * 100, 2)
                    : 0,
                'ticket_price' => $event->ticket_price,
            ];

        return response()->json([
            'statistics' => $statistics
        ], 200);
    }
    //===========================================================
    public function getTicketsEvent($event_id)
    {
        $tickets = EventTicket::where('event_id', $event_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $tickets_data = $tickets->map(function ($ticket) {
            return
                [
                    'visitor_name' => $ticket->visitor->first_name . ' ' . $ticket->visitor->last_name,
                    'status' => $ticket->status,
                    'booked_at' => $ticket->booked_at,
                    'phone' => $ticket->visitor->user->phone,
                    'email' => $ticket->visitor->user->email,
                ];

        });

        return response()->json([
            'tickets' => $tickets_data
        ], 200);
    }
    //===========================================================
    public function approveTicket($ticket_id)//i
    {
        $ticket = EventTicket::findOrFail($ticket_id);
        $event = $ticket->event;

        // منع قبول تذكرة مقبولة مسبقًا
        if ($ticket->status === 'approved') {
            return response()->json([
                'message' => 'Ticket already approved'
            ], 400);
        }

        // اذا مافي مقاعد متاحة
        if ($event->total_seats == 0) {
            return response()->json([
                'message' => 'No seats available'
            ], 400);
        }

        // توليد QR Code
        $qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=ticket_id:" . $ticket->id;

        // قبول+توليد qr
        $ticket->update([
            'status' => 'approved',
            'qr_code' => $qr,
        ]);

        $event->increment('registered_count');//+1
        $event->decrement('total_seats');//-1

        return response()->json([
            'message' => 'Ticket approved successfully',
            'ticket' => $ticket
        ], 200);
    }
    //===========================================================
    public function rejectTicket($ticket_id)//i
    {
        $ticket = EventTicket::findOrFail($ticket_id);
        $event = $ticket->event;

        // منع رفض تذكرة مرفوضة مسبقًا
        if ($ticket->status === 'rejected') {
            return response()->json([
                'message' => 'Ticket already rejected'
            ], 400);
        }

        // إذا كانت التذكرة مقبولة مسبقًا → يجب إعادة المقاعد
        if ($ticket->status === 'approved') {
            $event->decrement('registered_count');
            $event->increment('total_seats');
        }

        $ticket->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Ticket rejected successfully',
            'ticket' => $ticket
        ], 200);
    }
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

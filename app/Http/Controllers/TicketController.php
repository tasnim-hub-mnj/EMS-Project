<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\Exhibition;
use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    //طلب حجز تذكرة معرض 

    public function bookExhibition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exhibition_id' => 'required|exists:exhibitions,id',
            ' ticket_type' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1',
            'paid_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $exhibition = Exhibition::find($request->exhibition_id);
        $quantity = (int) $request->input('quantity', 1);

        $unitPrice = $request->has('paid_amount')
            ? ((float) $request->paid_amount / $quantity)
            : ($exhibition->ticket_price ?? 0.00);

        $createdTickets = [];

        DB::transaction(function () use ($quantity, $visitor, $request, $unitPrice, $exhibition, &$createdTickets) {
            for ($i = 0; $i < $quantity; $i++) {
                $qrCode = 'EXH-' . strtoupper(Str::random(10));
                $now = now();

                $ticket = Ticket::create([
                    'visitor_id' => $visitor->id,
                    'exhibition_id' => $request->exhibition_id,
                    'qr_code' => $qrCode,
                    'status' => 'approved',
                    'amount' => $unitPrice,
                    'booked_at' => $now,
                ]);

                $createdTickets[] = [
                    'id' => (int) $ticket->id,
                    'exhibition_id' => (int) $ticket->exhibition_id,
                    'exhibition_name' => $exhibition?->name ?? 'معرض غير محدد',
                    'qr_data' => (string) $ticket->qr_code,
                    'booked_at' => Carbon::parse($ticket->booked_at)->toIso8601String(),
                    'type' => 'exhibition',
                    'status' => (string) $ticket->status,
                    'event_id' => null,
                    'event_name' => null,
                    'paid_amount' => (float) $ticket->amount,
                    'seat_number' => null,
                ];
            }
        });

        return response()->json([
            'status' => true,
            'data' => $quantity === 1 ? $createdTickets[0] : $createdTickets
        ], 201);
    }
    //===========================================================
    public function bookEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'quantity' => 'nullable|integer|min:1',
            'paid_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $event = Event::with('boothBooking.booth.exhibition')->find($request->event_id);
        $quantity = (int) $request->input('quantity', 1);
        $exhibition = $event->boothBooking?->booth?->exhibition;

        $totalSeats = $event->total_seats ?? 0;
        $registeredCount = $event->registered_count ?? 0;

        if ($totalSeats > 0 && ($registeredCount + $quantity > $totalSeats)) {
            return response()->json([
                'status' => false,
                'message' => 'المقاعد المتاحة لا تكفي للعدد المطلوب'
            ], 400);
        }

        $unitPrice = $request->has('paid_amount')
            ? ((float) $request->paid_amount / $quantity)
            : ($event->ticket_price ?? 0.00);

        $createdTickets = [];

        DB::transaction(function () use ($quantity, $visitor, $event, $exhibition, $unitPrice, &$createdTickets) {
            for ($i = 0; $i < $quantity; $i++) {
                $qrCode = 'EVT-' . strtoupper(Str::random(10));
                $now = now();

                $ticket = EventTicket::create([
                    'visitor_id' => $visitor->id,
                    'event_id' => $event->id,
                    'qr_code' => $qrCode,
                    'status' => 'approved',
                    'amount' => $unitPrice,
                    'booked_at' => $now,
                ]);

                $createdTickets[] = [
                    'id' => (int) $ticket->id,
                    'exhibition_id' => $exhibition ? (int) $exhibition->id : null,
                    'exhibition_name' => $exhibition?->name ?? null,
                    'qr_data' => (string) $ticket->qr_code,
                    'booked_at' => Carbon::parse($ticket->booked_at)->toIso8601String(),
                    'type' => 'event',
                    'status' => (string) $ticket->status,
                    'event_id' => (int) $ticket->event_id,
                    'event_name' => $event->title ?? $event->name ?? 'فعالية غير محددة',
                    'paid_amount' => (float) $ticket->amount,
                    'seat_number' => null,
                ];
            }

            $event->increment('registered_count', $quantity);
        });

        return response()->json([
            'status' => true,
            'data' => $quantity === 1 ? $createdTickets[0] : $createdTickets
        ], 201);
    }
    //===========================================================
    //طلب حجز تذكرة فعالية راعي
    public function bookSponsorEventTicket(Request $request)
    {
        // جلب الـ ID من الـ Request Body مباشرة
        $sponsor_event_id = $request->input('sponsor_event_id');

        $validator = Validator::make($request->all(), [
            'sponsor_event_id' => 'required|exists:sponsor_events,id',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $sponsorEvent = SponsorEvent::find($sponsor_event_id);
        $qrCode = 'SPN-' . strtoupper(Str::random(10));
        $now = now();
        $amount = $request->input('amount', $sponsorEvent?->ticket_price ?? 0.00);

        // إنشاء التذكرة
        $ticket = SponserEventTicket::create([
            'visitor_id' => $visitor->id,
            'sponsor_event_id' => $sponsor_event_id,
            'status' => 'approved',
            'qr_code' => $qrCode,
            'amount' => $amount,
            'booked_at' => $now,
        ]);

        $formattedTicket = [
            'id' => (int) $ticket->id,
            'exhibition_id' => null,
            'exhibition_name' => null,
            'qr_data' => (string) $ticket->qr_code,
            'booked_at' => Carbon::parse($ticket->booked_at)->toIso8601String(),
            'type' => 'event',
            'status' => (string) $ticket->status,
            'event_id' => (int) $ticket->sponsor_event_id,
            'event_name' => $sponsorEvent?->title ?? $sponsorEvent?->name ?? 'فعالية إعلانية',
            'paid_amount' => (float) $ticket->amount,
            'seat_number' => null,
        ];

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب التذكرة الإعلانية بنجاح',
            'data' => $formattedTicket
        ], 201);
    }
    //=============================================================

    //==========================================================
    public function getExhibitionTicket(Request $request, $id)
    {
        $visitor = $request->user()->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $ticket = Ticket::with('exhibition')
            ->where('visitor_id', $visitor->id)
            ->find($id);

        if (!$ticket) {
            return response()->json([
                'message' => 'تذكرة المعرض غير موجودة'
            ], 404);
        }

        return response()->json([
            'id' => $ticket->id,
            'exhibition_id' => $ticket->exhibition_id,
            'exhibition_name' => $ticket->exhibition?->name ?? 'معرض غير محدد',
            'qr_data' => $ticket->qr_code,
            'booked_at' => $ticket->booked_at ? Carbon::parse($ticket->booked_at)->toIso8601String() : Carbon::parse($ticket->created_at)->toIso8601String(),
            'type' => 'exhibition',
            'status' => $ticket->status,
            'event_id' => null,
            'event_name' => null,
            'paid_amount' => (float) $ticket->amount,
            'seat_number' => null,
        ], 200);
    }

    public function getEventTicket(Request $request, $id)
    {
        $visitor = $request->user()->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $ticket = EventTicket::with('event')
            ->where('visitor_id', $visitor->id)
            ->find($id);

        if (!$ticket) {
            return response()->json([
                'message' => 'تذكرة الفعالية غير موجودة'
            ], 404);
        }

        return response()->json([
            'id' => $ticket->id,
            'exhibition_id' => null,
            'exhibition_name' => null,
            'qr_data' => $ticket->qr_code,
            'booked_at' => $ticket->booked_at ? Carbon::parse($ticket->booked_at)->toIso8601String() : Carbon::parse($ticket->created_at)->toIso8601String(),
            'type' => 'event',
            'status' => $ticket->status,
            'event_id' => $ticket->event_id,
            'event_name' => $ticket->event?->name ?? 'فعالية غير محددة',
            'paid_amount' => (float) $ticket->amount,
            'seat_number' => null,
        ], 200);
    }
    //==========================================================
    public function showSponsorEventTicket($id)
    {
        $ticket = SponserEventTicket::with('sponsorEvent')->findOrFail($id);

        return response()->json([
            'id' => $ticket->id,
            'sponsor_event_id' => $ticket->sponsor_event_id,
            'name' => $ticket->sponsorEvent->title,   // اسم الفعالية الإعلانية
            'status' => $ticket->status,
            'qr_code' => $ticket->qr_code,
            'booked_at' => $ticket->booked_at?->format('Y-m-d'),
            'amount' => $ticket->amount,
        ]);
    }
    //==========================================================
    //عرض حجوزات الزائر ككل 
    public function myTickets()
    {
        $visitor = auth()->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        // 1. تذاكر المعارض
        $exhibitionTickets = Ticket::with('exhibition')
            ->where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $bookedAt = $t->booked_at ?? $t->created_at;

                return [
                    'id' => (int) $t->id,
                    'exhibition_id' => (int) $t->exhibition_id,
                    'exhibition_name' => $t->exhibition?->name ?? '',
                    'qr_data' => (string) ($t->qr_code ?? $t->qr_data ?? ''),
                    'booked_at' => $bookedAt ? Carbon::parse($bookedAt)->toIso8601String() : null,
                    'type' => 'exhibition',
                    'status' => (string) $t->status,
                    'event_id' => null,
                    'event_name' => null,
                    'paid_amount' => (float) ($t->amount ?? $t->paid_amount ?? 0),
                    'seat_number' => $t->seat_number ?? null,
                ];
            });

        // 2. تذاكر الفعاليات
        $eventTickets = EventTicket::with('event')
            ->where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $bookedAt = $t->booked_at ?? $t->created_at;

                return [
                    'id' => (int) $t->id,
                    'exhibition_id' => $t->event?->exhibition_id ? (int) $t->event->exhibition_id : null,
                    'exhibition_name' => $t->event?->exhibition?->name ?? null,
                    'qr_data' => (string) ($t->qr_code ?? $t->qr_data ?? ''),
                    'booked_at' => $bookedAt ? Carbon::parse($bookedAt)->toIso8601String() : null,
                    'type' => 'event',
                    'status' => (string) $t->status,
                    'event_id' => (int) $t->event_id,
                    'event_name' => $t->event?->title ?? $t->event?->name ?? '',
                    'paid_amount' => (float) ($t->amount ?? $t->paid_amount ?? 0),
                    'seat_number' => $t->seat_number ?? null,
                ];
            });

        // 3. تذاكر الفعاليات الإعلانية (Sponsor Events)
        $sponsorTickets = SponserEventTicket::with('sponsorEvent')
            ->where('visitor_id', $visitor->id) // تم تصحيح سid إلى id
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $bookedAt = $t->booked_at ?? $t->created_at;

                return [
                    'id' => (int) $t->id,
                    'exhibition_id' => null,
                    'exhibition_name' => null,
                    'qr_data' => (string) ($t->qr_code ?? $t->qr_data ?? ''),
                    'booked_at' => $bookedAt ? Carbon::parse($bookedAt)->toIso8601String() : null,
                    'type' => 'event',
                    'status' => (string) $t->status,
                    'event_id' => (int) ($t->sponsor_event_id ?? $t->sponser_event_id),
                    'event_name' => $t->sponsorEvent?->title ?? $t->sponsorEvent?->name ?? '',
                    'paid_amount' => (float) ($t->amount ?? $t->paid_amount ?? 0),
                    'seat_number' => $t->seat_number ?? null,
                ];
            });

        // 4. دمج كل التذاكر في مصفوفة واحدة
        $allTickets = $exhibitionTickets
            ->concat($eventTickets)
            ->concat($sponsorTickets)
            ->values();

        return response()->json([
            'status' => true,
            'data' => $allTickets
        ], 200);
    }
    //=========================================================

    public function cancelTicket($id)
    {
        $visitor = auth()->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $ticket = Ticket::where('id', $id)
            ->where('visitor_id', $visitor->id)
            ->first();

        if ($ticket) {
            $ticket->update(['status' => 'rejected']);

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء تذكرة المعرض بنجاح'
            ], 200);
        }

        $eventTicket = EventTicket::with('event')
            ->where('id', $id)
            ->where('visitor_id', $visitor->id)
            ->first();

        if ($eventTicket) {
            DB::transaction(function () use ($eventTicket) {
                $eventTicket->update(['status' => 'rejected']);

                if ($eventTicket->event && $eventTicket->event->registered_count > 0) {
                    $eventTicket->event->decrement('registered_count');
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء تذكرة الفعالية بنجاح'
            ], 200);
        }

        $sponsorTicket = SponserEventTicket::where('id', $id)
            ->where('visitor_id', $visitor->id)
            ->first();

        if ($sponsorTicket) {
            $sponsorTicket->update(['status' => 'rejected']);

            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء تذكرة الفعالية الإعلانية بنجاح'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'التذكرة غير موجودة أو لا تملك صلاحية إلغائها'
        ], 404);
    }
}








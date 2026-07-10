<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    //طلب حجز تذكرة معرض 
    public function bookExhibitionTicket(Request $request, $exhibition_id)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:1',
        ]);

        $visitor = auth()->user()->visitor;

        $ticket = Ticket::create([
            'visitor_id' => $visitor->id,
            'exhibition_id' => $exhibition_id,
            'status' => 'pending',
            'qr_code' => null,
            'amount' => $request->amount ?? 1,
            'booked_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب تذكرة المعرض إلى المدير بنجاح',
            'ticket' => $ticket
        ]);
    }
    //===========================================================
    //طلب حجز تذكرة فعالية
    public function bookEventTicket(Request $request, $event_id)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:1',
        ]);

        $visitor = auth()->user()->visitor;

        $ticket = EventTicket::create([
            'visitor_id' => $visitor->id,
            'event_id' => $event_id,
            'status' => 'pending',
            'qr_code' => null,
            'amount' => $request->amount ?? 1,
            'booked_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب تذكرة الفعالية إلى المستثمر بنجاح',
            'ticket' => $ticket
        ]);
    }
    //===========================================================
    //طلب حجز تذكرة فعالية راعي
    public function bookSponsorEventTicket(Request $request, $sponsor_event_id)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:1',
        ]);

        $visitor = auth()->user()->visitor;

        $ticket = SponsorEventTicket::create([
            'visitor_id' => $visitor->id,
            'sponser_event_id' => $sponsor_event_id,
            'status' => 'pending',
            'qr_code' => null,
            'amount' => $request->amount ?? 1,
            'booked_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب التذكرة الإعلانية إلى المستثمر بنجاح',
            'ticket' => $ticket
        ]);
    }
    //=============================================================
    public function showExhibitionTicket($id)
    {
        $ticket = Ticket::with('exhibition')->findOrFail($id);

        return response()->json([
            'id' => $ticket->id,
            'exhibition_id' => $ticket->exhibition_id,
            'name' => $ticket->exhibition->name,   // اسم المعرض من العلاقة
            'status' => $ticket->status,
            'qr_code' => $ticket->qr_code,
            'booked_at' => $ticket->booked_at?->format('Y-m-d'),
            'amount' => $ticket->amount,
        ]);
    }
    //==========================================================
    public function showEventTicket($id)
    {
        $ticket = EventTicket::with('event')->findOrFail($id);

        return response()->json([
            'id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'name' => $ticket->event->title,   // اسم الفعالية من العلاقة
            'status' => $ticket->status,
            'qr_code' => $ticket->qr_code,
            'booked_at' => $ticket->booked_at?->format('Y-m-d'),
            'amount' => $ticket->amount,
        ]);
    }
    //==========================================================
    public function showSponsorEventTicket($id)
    {
        $ticket = SponserEventTicket::with('sponsorEvent')->findOrFail($id);

        return response()->json([
            'id' => $ticket->id,
            'sponsor_event_id' => $ticket->sponser_event_id,
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
        $visitor = auth()->user()->visitor;

        if (!$visitor) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // تذاكر المعرض
        $exhibitionTickets = Ticket::with('exhibition')
            ->where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'exhibition_id' => $t->exhibition_id,
                    'name' => $t->exhibition->name,   // اسم المعرض من العلاقة
                    'status' => $t->status,
                    'qr_code' => $t->qr_code,
                    'booked_at' => $t->booked_at?->format('Y-m-d'),
                    'amount' => $t->amount,
                ];
            });

        // تذاكر الفعاليات
        $eventTickets = EventTicket::with('event')
            ->where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'event_id' => $t->event_id,
                    'name' => $t->event->title,   // اسم الفعالية من العلاقة
                    'status' => $t->status,
                    'qr_code' => $t->qr_code,
                    'booked_at' => $t->booked_at?->format('Y-m-d'),
                    'amount' => $t->amount,
                ];
            });

        // تذاكر الفعاليات الإعلانية
        $sponsorTickets = SponserEventTicket::with('sponsorEvent')
            ->where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'sponsor_event_id' => $t->sponser_event_id,
                    'name' => $t->sponsorEvent->title,   // اسم الفعالية الإعلانية
                    'status' => $t->status,
                    'qr_code' => $t->qr_code,
                    'booked_at' => $t->booked_at?->format('Y-m-d'),
                    'amount' => $t->amount,
                ];
            });

        return response()->json([
            'exhibition_tickets' => $exhibitionTickets,
            'event_tickets' => $eventTickets,
            'sponsor_event_tickets' => $sponsorTickets,
        ]);
    }

}








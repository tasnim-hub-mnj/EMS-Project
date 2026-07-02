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
    public function bookExhibitionTicket(Request $request)
    {
        $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $visitor = auth()->user()->visitor;

        $ticket = Ticket::create([
            'visitor_id' => $visitor->id,
            'exhibition_id' => $request->exhibition_id,
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
    public function bookEventTicket(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $visitor = auth()->user()->visitor;

        $event = Event::find($request->event_id);

        $ticket = EventTicket::create([
            'visitor_id' => $visitor->id,
            'event_id' => $event->id,
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
    public function bookSponsorEventTicket(Request $request)
    {
        $request->validate([
            'sponsor_event_id' => 'required|exists:sponsor_events,id',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $visitor = auth()->user()->visitor;

        $event = SponsorEvent::find($request->sponsor_event_id);

        $ticket = SponserEventTicket::create([
            'visitor_id' => $visitor->id,
            'sponser_event_id' => $event->id,
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

    //عرض حجوزات الزائر ككل 
    public function myBookings()
    {
        $visitor = auth()->user()->visitor;
        if (!$visitor) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $exhibitionTickets = Ticket::where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $eventTickets = EventTicket::where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $sponsorEventTickets = SponserEventTicket::where('visitor_id', $visitor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'تم جلب جميع حجوزاتك بنجاح',

            'pending' => [
                'exhibition' => $exhibitionTickets->where('status', 'pending')->values(),
                'event' => $eventTickets->where('status', 'pending')->values(),
                'sponsor_event' => $sponsorEventTickets->where('status', 'pending')->values(),
            ],

            'confirmed' => [
                'exhibition' => $exhibitionTickets->where('status', 'confirmed')->values(),
                'event' => $eventTickets->where('status', 'confirmed')->values(),
                'sponsor_event' => $sponsorEventTickets->where('status', 'confirmed')->values(),
            ],

            'cancelled' => [
                'exhibition' => $exhibitionTickets->where('status', 'cancelled')->values(),
                'event' => $eventTickets->where('status', 'cancelled')->values(),
                'sponsor_event' => $sponsorEventTickets->where('status', 'cancelled')->values(),
            ],
        ]);
    }





}

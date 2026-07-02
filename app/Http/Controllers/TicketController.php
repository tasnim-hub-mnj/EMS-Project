<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function approvTicket($ticketId)//قبول طلب التذكرة
    {
        $ticket = Ticket::findOrFail($ticketId);
        if (Auth::id() !== $ticket->event->investor_id) {
            return response()->json([
                'message' => 'Unauthorized to accept this ticket request'
            ], 403);
        }

        // توليد QR
        $qr = 'ECT-' . str_pad($ticket->id, 3, '0', STR_PAD_LEFT);

        $ticket->update([
            'status' => 'approved',
            'qr_code' => $qr,
        ]);

        return response()->json([
            'message' => 'Accepted the ticket request',
            'ticket' => $ticket
        ], 200);
    }
    //_____________________________________________________________
    public function rejectTicket($ticketId)//رفض طلب تذكرة
    {
        $ticket = Ticket::findOrFail($ticketId);
        if (Auth::id() !== $ticket->event->investor_id) {
            return response()->json([
                'message' => 'Unauthorized to reject this ticket request'
            ], 403);
        }

        $ticket->update([
            'status' => 'rejected',
            'qr_code' => null,
        ]);

        return response()->json([
            'message' => 'Rejected the ticket request',
            'ticket' => $ticket
        ], 200);
    }
    //_____________________________________________________________
    public function pendingTickets($eventId)
    {
        $event = Event::where('investor_id', Auth::id())
            ->findOrFail($eventId);

        $tickets = $event->tickets()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'event_id' => $t->event_id,
                    'requester_name' => $t->requester_name,
                    'requester_phone' => $t->requester_phone,
                    'requester_email' => $t->requester_email,
                    'ticket_number' => $t->ticket_number,
                    'status' => $t->status,
                    'qr_code' => $t->qr_code,
                    'requested_at' => $t->requested_at,
                ];
            });

        return response()->json([
            'tickets' => $tickets
        ], 200);
    }

    //_____________________________________________________________
    public function acceptedTickets($eventId)
    {
        $event = Event::where('investor_id', Auth::id())
            ->findOrFail($eventId);

        $tickets = $event->tickets()
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'event_id' => $t->event_id,
                    'requester_name' => $t->requester_name,
                    'requester_phone' => $t->requester_phone,
                    'requester_email' => $t->requester_email,
                    'ticket_number' => $t->ticket_number,
                    'status' => $t->status,
                    'qr_code' => $t->qr_code,
                    'requested_at' => $t->requested_at,
                ];
            });

        return response()->json([
            'tickets' => $tickets
        ], 200);
    }

    //_____________________________________________________________
    public function rejectedTickets($eventId)
    {
        $event = Event::where('investor_id', Auth::id())
            ->findOrFail($eventId);

        $tickets = $event->tickets()
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'event_id' => $t->event_id,
                    'requester_name' => $t->requester_name,
                    'requester_phone' => $t->requester_phone,
                    'requester_email' => $t->requester_email,
                    'ticket_number' => $t->ticket_number,
                    'status' => $t->status,
                    'qr_code' => $t->qr_code,
                    'requested_at' => $t->requested_at,
                ];
            });

        return response()->json([
            'tickets' => $tickets
        ], 200);
    }

    //_____________________________________________________________
    //_____________________________________________________________
    //_____________________________________________________________


    //=========================تذاكر الزائر===========================




}

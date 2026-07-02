<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VisitorTicketController extends Controller
{
    public function myTickets()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول لعرض التذاكر'
            ], 401);
        }
    }

    // 1) تذاكر فعاليات داخل جناح (تذهب للمستثمر)
    //     $boothEvents = EventTicket::with('sponsorEvent')
    //         ->where('user_id', $user->id)
    //         ->get()
    //         ->map(function ($t) {
    //             return [
    //                 'id' => $t->id,
    //                 'type' => 'booth_event',
    //                 'event_name' => $t->sponsorEvent->name,
    //                 'place' => 'داخل جناح',
    //                 'sent_to' => 'المستثمر',
    //                 'status' => $t->status,
    //                 'qr_code' => $t->qr_code,
    //                 'amount' => $t->amount,
    //                 'booked_at' => $t->booked_at,
    //             ];
    //         });

    //     // 2) تذاكر فعاليات داخل معرض (تذهب للمدير)
    //     $exhibitionEvents = SponsorEventTicket::with('sponsorEvent')
    //         ->where('user_id', $user->id)
    //         ->get()
    //         ->map(function ($t) {
    //             return [
    //                 'id' => $t->id,
    //                 'type' => 'exhibition_event',
    //                 'event_name' => $t->sponsorEvent->name,
    //                 'place' => 'داخل معرض',
    //                 'sent_to' => 'المدير',
    //                 'status' => $t->status,
    //                 'qr_code' => $t->qr_code,
    //                 'amount' => $t->amount,
    //                 'booked_at' => $t->booked_at,
    //             ];
    //         });

    //     // 3) تذاكر المعرض (جدول tickets الجديد)
    //     $exhibitionTickets = $user->tickets->map(function ($t) {
    //         return [
    //             'id' => $t->id,
    //             'type' => 'exhibition_ticket',
    //             'event_name' => $t->exhibition->name,
    //             'place' => 'معرض',
    //             'sent_to' => 'المدير',
    //             'status' => $t->status,
    //             'qr_code' => $t->qr_code,
    //             'amount' => $t->amount,
    //             'booked_at' => $t->booked_at,
    //         ];
    //     });

    //     // دمج كل التذاكر
    //     $allTickets = $boothEvents
    //         ->merge($exhibitionEvents)
    //         ->merge($exhibitionTickets)
    //         ->values();

    //     return response()->json([
    //         'message' => 'تم جلب جميع تذاكر الزائر بنجاح',
    //         'tickets' => $allTickets
    //     ]);
    // }

}
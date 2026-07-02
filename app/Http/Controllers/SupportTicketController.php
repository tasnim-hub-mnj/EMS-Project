<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function allTickets()
    {
        $tickets = SupportTicket::with('user')
            ->orderByDesc('created_at')
            ->get();

        if ($tickets->isEmpty()) {
            return response()->json([
                'message' => 'لا يوجد أي رسائل دعم حالياً'
            ]);
        }

        return response()->json([
            'message' => 'تم جلب جميع رسائل الدعم بنجاح',
            'tickets' => $tickets
        ]);
    }
    //==================================================
    // عرض تذاكر الدعم الخاصة بالمستخدم
    public function index(Request $request)
    {
        return $request->user()
            ->supportTickets()
            ->orderByDesc('created_at')
            ->get();
    }
    //===========================================

    // عرض تذكرة واحدة
    public function show(Request $request, $id)
    {
        $ticket = $request->user()
            ->supportTickets()
            ->findOrFail($id);

        return response()->json($ticket);
    }
    //============================================

    // إنشاء تذكرة دعم من نوع رسالة
    public function storeMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        $data = $validator->validate();

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'type' => 'message',
            'body' => $data['message'],
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'تم إرسال رسالة الدعم بنجاح',
            'ticket' => $ticket
        ], 201);
    }
    //=========================================

    // إنشاء تذكرة دعم من نوع تقرير
    public function storeReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issue_type' => 'required|string',
            'description' => 'required|string|max:2000',
        ]);

        $data = $validator->validate();

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'type' => 'report',
            'body' => $data['description'],
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'تم إرسال التقرير بنجاح',
            'ticket' => $ticket
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function allTickets()
    {
        $tickets = SupportTicket::with('visitor.user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'تم جلب جميع رسائل الدعم بنجاح',
            'tickets' => $tickets
        ], 200);
    }
    //==================================================
    // عرض تذاكر الدعم الخاصة بالمستخدم
    public function index(Request $request)
    {
        $visitor = $request->user()->visitor;

        $tickets = SupportTicket::where('visitor_id', $visitor->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tickets, 200);
    }

    public function show(Request $request, $id)
    {
        $visitor = $request->user()->visitor;

        $ticket = SupportTicket::where('visitor_id', $visitor->id)
            ->findOrFail($id);

        return response()->json($ticket, 200);
    }
    //============================================

    // إنشاء تذكرة دعم من نوع رسالة
    public function storeMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        $data = $validator->validate();
        $visitor = $request->user()->visitor;
        $ticket = SupportTicket::create([
            'visitor_id' => $visitor->id,
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

        $visitor = $request->user()->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $formattedBody = "نوع المشكلة: " . $data['issue_type'] . "\nالوصف: " . $data['description'];

        $ticket = SupportTicket::create([
            'visitor_id' => $visitor->id,
            'type' => 'report',
            'body' => $formattedBody,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'تم إرسال التقرير بنجاح',
            'ticket' => $ticket
        ], 201);
    }
}


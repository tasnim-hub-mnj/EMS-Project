<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function allTickets()
    {
        $tickets = SupportTicket::with('visitor')
            ->orderByDesc('created_at')
            ->get();

        $formattedTickets = $tickets->map(function ($ticket) {
            return [
                'id' => (int) $ticket->id,
                'user_id' => (int) $ticket->visitor?->user_id,
                'user_name' => trim(($ticket->visitor?->first_name ?? '') . ' ' . ($ticket->visitor?->last_name ?? '')),
                'user_avatar' => $ticket->visitor?->avatar_url ? asset($ticket->visitor?->avatar_url) : null,
                'type' => $ticket->type,
                'body' => $ticket->body,
                'latitude' => $ticket->latitude ? (float) $ticket->latitude : null,
                'longitude' => $ticket->longitude ? (float) $ticket->longitude : null,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at?->toIso8601String(),
            ];
        });
        return response()->json([
            'status' => true,
            'message' => 'تم جلب جميع رسائل وتذاكر الدعم بنجاح',
            'data' => $formattedTickets
        ], 200);
    }
    //==================================================
    // // عرض تذاكر الدعم الخاصة بالمستخدم
    // public function index(Request $request)
    // {
    //     $visitor = $request->user()->visitor;

    //     $tickets = SupportTicket::where('visitor_id', $visitor->id)
    //         ->orderByDesc('created_at')
    //         ->get();

    //     return response()->json($tickets, 200);
    // }
    //=====================================================
    public function show(Request $request, $id)
    {
        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        // جلب التذكرة الخاصة بالزائر أو إرجاع 404 إذا لم تُوجد
        $ticket = SupportTicket::where('visitor_id', $visitor->id)
            ->findOrFail($id);

        // إرجاع الاستجابة المنسقة والمغلفة
        return response()->json([
            'status' => true,
            'data' => [
                'id' => (int) $ticket->id,
                'user_id' => (int) $visitor->user_id,
                'user_name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? '')),
                'user_avatar' => $visitor->avatar_url ? asset($visitor->avatar_url) : null,
                'type' => $ticket->type,
                'body' => $ticket->body,
                'latitude' => $ticket->latitude ? (float) $ticket->latitude : null,
                'longitude' => $ticket->longitude ? (float) $ticket->longitude : null,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at?->toIso8601String(),
            ]
        ], 200);
    }
    //============================================
    // إنشاء تذكرة دعم من نوع رسالة
    public function storeMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
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

        $ticket = SupportTicket::create([
            'visitor_id' => $visitor->id,
            'type' => 'message',
            'body' => $request->message,
            'status' => 'open',
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'reply' => 'تم إرسال رسالتك بنجاح، وسيتم الرد عليك قريباً.'
            ]
        ], 201);
    }
    //=========================================

    public function storeReport(Request $request)
    {
        // 1. التحقق من المدخلات القادمة من الواجهة
        $validator = Validator::make($request->all(), [
            'issue_type' => 'required|string',
            'description' => 'required|string|max:2000',
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

        $formattedBody = "نوع المشكلة: " . $request->issue_type . "\nالوصف: " . $request->description;

        SupportTicket::create([
            'visitor_id' => $visitor->id,
            'type' => 'report',
            'body' => $formattedBody,
            'status' => 'open',
        ]);
        return response()->json([
            'status' => true,
            'data' => [
                'reply' => 'تم إرسال البلاغ بنجاح، وسيتم مراجعته من قبل الفريق.'
            ]
        ], 201);
    }
    //========================================================
    public function sendLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
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

        $locationJson = json_encode([
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
        ]);

        SupportTicket::create([
            'visitor_id' => $visitor->id,
            'type' => 'location',
            'body' => $locationJson,
            'status' => 'open',
        ]);
        return response()->json([
            'status' => true,
            'data' => [
                'reply' => 'تم إرسال موقعك الجغرافي للدعم بنجاح.'
            ]
        ], 201);
    }
}


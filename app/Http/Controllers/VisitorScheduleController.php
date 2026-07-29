<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\CollectedBooths;
use App\Models\VisitorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VisitorScheduleController extends Controller
{
    public function mySchedule(Request $request)
    {
        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        // today | week | all
        $filter = $request->query('filter', 'all');

        $query = VisitorSchedule::with([
            'event.exhibition',
            'event.boothBooking.booth.exhibition'
        ])
            ->where('visitor_id', $visitor->id);

        // فلترة حسب اليوم
        if ($filter === 'today') {
            $query->whereHas('event', function ($q) {
                $q->where('date', now()->toDateString());
            });
        }

        // فلترة حسب هذا الأسبوع
        if ($filter === 'week') {
            $start = now()->startOfWeek()->toDateString();
            $end = now()->endOfWeek()->toDateString();

            $query->whereHas('event', function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start, $end]);
            });
        }

        $events = $query->orderBy('added_at', 'asc')
            ->get()
            ->map(function ($item) {
                $event = $item->event;
                $booking = $event?->boothBooking;
                $booth = $booking?->booth;
                $exhibition = $event?->exhibition ?? $booth?->exhibition;

                return [
                    'id' => (int) $event->id,
                    'title' => $event->title ?? $event->name ?? '',
                    'description' => $event->description ?? null,
                    'organizer' => $event->by ?? $event->organizer ?? null,
                    'date' => $event->date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'type' => $event->type ?? 'event',
                    'exhibition_id' => $exhibition?->id ? (int) $exhibition->id : null,
                    'exhibition_name' => $exhibition?->name ?? null,
                    'location' => $exhibition?->location ?? null,
                    'city' => $exhibition?->city ?? null,
                    'hall' => $booth?->hall_name ?? null,
                    'is_in_schedule' => true,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $events
        ], 200);
    }
    //====================================================
    //==============================================
    // إضافة فعالية إلى مواعيدي
    public function storeSchedule(Request $request, $eventId)
    {
        $request->merge(['event_id' => $request->input('event_id', $eventId)]);

        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
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

        $schedule = VisitorSchedule::firstOrCreate([
            'visitor_id' => $visitor->id,
            'event_id' => $request->event_id,
        ], [
            'added_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تمت إضافة الفعالية إلى مواعيدك بنجاح',
            'data' => $schedule
        ], 200);
    }
    //==============================================
    // حذف موعد
    public function removeFromSchedule(Request $request, $eventId)
    {
        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }
        $schedule = VisitorSchedule::where('visitor_id', $visitor->id)
            ->where('event_id', $eventId)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => false,
                'message' => 'الفعالية غير موجودة في جدولك'
            ], 404);
        }

        $schedule->delete();
        return response()->json([
            'status' => true,
            'message' => 'تمت إزالة الفعالية من مواعيدك بنجاح'
        ], 200);
    }
}

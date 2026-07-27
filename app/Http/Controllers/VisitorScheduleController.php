<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\CollectedBooths;
use App\Models\VisitorSchedule;
use Illuminate\Http\Request;

class VisitorScheduleController extends Controller
{
    //جلب مواعيد الزائر
    public function mySchedule(Request $request)
    {

        $visitor = $request->user()->visitor;
        // today | week | all
        $filter = $request->query('filter', 'all');

        $query = VisitorSchedule::with([
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

        // جلب النتائج
        $schedule = $query->orderBy('added_at', 'asc')
            ->get()
            ->map(function ($item) {

                $event = $item->event;
                $booking = $event->boothBooking;
                $booth = $booking?->booth;
                $exhibition = $booth?->exhibition;

                return [
                    'id' => $item->id,
                    'event_name' => $event->name,
                    'organizer' => $event->by,
                    'date' => $event->date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'type' => $event->type,
                    'exhibition_name' => $exhibition?->name,
                    'location' => $exhibition?->location,
                    'city' => $exhibition?->city,
                    'hall' => $booth?->hall_name ?? null,
                    'added_at' => $item->added_at,
                ];
            });

        return response()->json([
            'message' => 'تم جلب المواعيد بنجاح',
            'schedule' => $schedule
        ]);
    }
    //====================================================
    //==============================================
    // إضافة فعالية إلى مواعيدي
    public function storeSchedule(Request $request, $eventId)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        if ($request->input('event_id') != $eventId) {
            return response()->json([
                'message' => 'الفعالية المرسلة في الرابط لا تطابق الفعالية المرسلة في الـ Body'
            ], 400);
        }

        $visitor = $request->user()->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $exists = VisitorSchedule::where('visitor_id', $visitor->id)
            ->where('event_id', $eventId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'هذه الفعالية مضافة بالفعل إلى مواعيدك'
            ], 409);
        }
        $schedule = VisitorSchedule::create([
            'visitor_id' => $visitor->id,
            'event_id' => $eventId,
            'added_at' => now(),
        ]);

        return response()->json([
            'message' => 'تمت إضافة الفعالية إلى مواعيدك بنجاح',
            'schedule' => $schedule
        ], 201);
    }
    //==============================================
    // حذف موعد
    public function removeFromSchedule(Request $request, $eventId)
    {
        $visitor = $request->user()->visitor;
        $schedule = VisitorSchedule::where('visitor_id', $visitor->id)
            ->where('event_id', $eventId)
            ->firstOrFail();

        $schedule->delete();

        return response()->json([
            'message' => 'تم حذف الفعالية من مواعيدك بنجاح'
        ], 200);
    }
}

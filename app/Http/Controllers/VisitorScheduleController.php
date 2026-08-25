<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\CollectedBooths;
use Illuminate\Support\Str;
use App\Models\Event;
use App\Models\SponsorEvent;
use App\Models\Notification;
use App\Models\VisitorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

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
            'event.exhibition.organizer',
            'event.boothBooking.booth.exhibition',
            'sponsorEvent.exhibition'
        ])
            ->where('visitor_id', $visitor->id);

        // فلترة حسب اليوم
        if ($filter === 'today') {
            $query->whereHas('event', function ($q) {
                $q->where('start_date', now()->toDateString());
            });
        }

        // فلترة حسب هذا الأسبوع
        if ($filter === 'week') {
            $start = now()->startOfWeek()->toDateString();
            $end = now()->endOfWeek()->toDateString();

            $query->whereHas('event', function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end]);
            });
        }

        $events = $query->orderBy('added_at', 'asc')
            ->get()
            ->map(function ($item) {
                $event = $item->event;
                $sponsorEvent = $item->sponsorEvent;
                if ($item->event_source === 'sponsor_event') {
                    $event = $sponsorEvent;
                }
                if (!$event) {
                    return null;
                }
                $booking = $event instanceof Event ? $event->boothBooking : null;
                $booth = $booking?->booth;
                $exhibition = $event instanceof SponsorEvent
                    ? $event->exhibition
                    : ($event?->exhibition ?? $booth?->exhibition);

                // تحديد المنظم
                $organizerName = $exhibition?->organizer?->name ?? 'منظم المعرض';

                return [
                    'id' => (int) $event->id,
                    'event_source' => $item->event_source ?? 'event',
                    'name' => $event->name ?? '',
                    'description' => $event->description ?? null,
                    'company_name' => $organizerName,
                    'date' => $event instanceof SponsorEvent
                        ? \Carbon\Carbon::parse($event->start_time)->toDateString()
                        : ($event->start_date ?? null),
                    'start_time' => $event instanceof SponsorEvent
                        ? \Carbon\Carbon::parse($event->start_time)->toIso8601String()
                        : ($event->start_date && $event->time
                            ? \Carbon\Carbon::parse($event->start_date . ' ' . $event->time)->toIso8601String()
                            : null),
                    'end_time' => $event instanceof SponsorEvent
                        ? \Carbon\Carbon::parse($event->end_time)->toIso8601String()
                        : ($event->end_date
                            ? \Carbon\Carbon::parse($event->end_date)->toIso8601String()
                            : null),
                    'type' => $event->type ?? 'Seminar',
                    'exhibition_id' => $exhibition?->id ? (int) $exhibition->id : null,
                    'exhibition_name' => $exhibition?->name ?? null,
                    'location' => $exhibition?->location ?? null,
                    'city' => $exhibition?->city ?? null,
                    'hall' => $event instanceof SponsorEvent ? $event->place : ($booth?->section ?? null),
                    'booth' => $booth?->number ?? $booth?->name ?? null,
                    'available_seats' => max(0, (int) ($event->max_participants ?? $event->total_seats ?? 0) - (int) ($event->registered_count ?? 0)),
                    'total_seats' => (int) ($event->max_participants ?? $event->total_seats ?? 0),
                    'is_in_schedule' => true,
                ];
            })
            ->filter()
            ->values();

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
        $source = $request->input('event_source', 'event');

        $validator = Validator::make($request->all(), [
            'event_id' => 'required|integer',
            'event_source' => 'nullable|in:event,sponsor_event',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $visitor = $user?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $event = $source === 'sponsor_event'
            ? SponsorEvent::find($request->event_id)
            : Event::find($request->event_id);
        if (!$event) {
            return response()->json(['status' => false, 'message' => 'الفعالية غير موجودة'], 404);
        }
        $eventName = $event->title ?? $event->name ?? 'فعالية غير محددة';

        $schedule = VisitorSchedule::firstOrCreate([
            'visitor_id' => $visitor->id,
            'event_id' => $source === 'event' ? $request->event_id : null,
            'sponsor_event_id' => $source === 'sponsor_event' ? $request->event_id : null,
            'event_source' => $source,
        ], [
            'added_at' => now(),
        ]);

        if ($schedule->wasRecentlyCreated) {
            app(NotificationService::class)->forUserId(
                (int) $user->id,
                'تمت إضافة الفعالية إلى مواعيدك',
                "تمت إضافة {$eventName} إلى جدول مواعيدك.",
                'schedule.added',
                [
                    'event_id' => (int) $request->event_id,
                    'event_source' => $source,
                ],
                '/schedule',
            );
        }


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
        $user = $request->user();
        $visitor = $user?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $source = $request->query('event_source', 'event');
        $schedule = VisitorSchedule::where('visitor_id', $visitor->id)
            ->where($source === 'sponsor_event' ? 'sponsor_event_id' : 'event_id', $eventId)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => false,
                'message' => 'الفعالية غير موجودة في جدولك'
            ], 404);
        }

        $event = Event::find($eventId);
        $eventName = $event->title ?? $event->name ?? 'فعالية غير محددة';

        // حذف الموعد
        $schedule->delete();

        return response()->json([
            'status' => true,
            'message' => 'تمت إزالة الفعالية من مواعيدك بنجاح'
        ], 200);
    }
}

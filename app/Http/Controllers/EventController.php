<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\BoothBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function store(Request $request, $boothId = null)//إضافة فعالية داخل الجناح أو عبر body
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'type'              => 'nullable|string|max:255',
            'date'              => 'required|date',
            'time'              => 'required',
            'duration_days'     => 'integer|min:1',
            'max_participants'  => 'nullable|integer|min:1',
            'description'       => 'nullable|string',
            'place'             => 'nullable|string|max:255',
            'requires_booking'  => 'boolean',
            'has_bookable_seats'=> 'boolean',
            'total_seats'       => 'nullable|integer|min:1',
            'ticket_price'      => 'nullable|numeric|min:0',
        ]);

        $investor = Auth::user()->investor;

        // إذا لم يمر boothId في المسار، نأخذ القيمة من الطلب نفسه (لأن الواجهة قد ترسلها في body)
        $boothId = $boothId ?? $request->input('booth_id');

        if (!$boothId) {
            return response()->json([
                'message' => 'booth_id is required'
            ], 422);
        }

        // تأكيد أن الجناح يخص هذا المستثمر
        BoothBooking::where('booth_id', $boothId)
               ->where('investor_id', $investor->id)
               ->where('status', 'approved')
               ->firstOrFail();

        $event = Event::create([
            'investor_id'       => $investor->id,
            'booth_id'          => $boothId,
            'name'              => $request->name,
            'type'              => $request->type,
            'date'              => $request->date,
            'time'              => $request->time,
            'duration_days'     => $request->duration_days ?? 1,
            'max_participants'  => $request->max_participants,
            'description'       => $request->description,
            'place'             => $request->place,
            'requires_booking'  => $request->requires_booking ?? false,
            'has_bookable_seats'=> $request->has_bookable_seats ?? false,
            'total_seats'       => $request->total_seats,
            'ticket_price'      => $request->ticket_price ?? 0,
            'company_images'    => [],
            'daily_attendees'   => [],
            'total_event_days'  => $request->duration_days ?? 1,
        ]);

        return response()->json([
            'message' => 'Event Added',
            'event'   => $event
        ], 201);
    }
    //===========================================================
    public function update(Request $request, $id)//تعديل فعالية
    {
        $investor = Auth::user()->investor;

        $event = Event::where('investor_id', $investor->id)->findOrFail($id);

        $event->update($request->all());

        return response()->json([
            'message' => 'Event Updated',
            'event'   => $event
        ], 200);
    }
    //===========================================================
    public function destroy($id)//حذف فعالية
    {
        $investor = Auth::user()->investor;

        $event = Event::where('investor_id', $investor->id)->findOrFail($id);

        $event->delete();

        return response()->json([
            'message' => 'Event Deleted'
        ], 200);
    }
    //===========================================================
    public function show($id)//عرض فعالية واحدة
    {
        $event = Event::with(['booth.exhibition', 'investor'])
                    ->findOrFail($id);

        return response()->json([
            'event' => $event
        ], 200);
    }
    //===========================================================
    public function boothEvents($boothId)//عرض فعاليات الجناح
    {
        $events = Event::where('booth_id', $boothId)
                       ->with(['booth.exhibition'])
                       ->orderBy('date', 'asc')
                       ->get();

        return response()->json([
            'events' => $events
        ], 200);
    }
    //===========================================================
    public function exhibitionEvents($exhibitionId)//عرض فعاليات المعرض
    {
        $events = Event::whereHas('booth', function ($q) use ($exhibitionId) {
                        $q->where('exhibition_id', $exhibitionId);
                    })
                    ->with(['booth.exhibition'])
                    ->orderBy('date', 'asc')
                    ->get();

        return response()->json([
            'events' => $events
        ], 200);
    }
    //===========================================================
    public function myEvents()//عرض فعالياتي فقط
    {
        $investor = Auth::user()->investor;

        $events = Event::where('investor_id', $investor->id)
                       ->with(['booth.exhibition'])
                       ->orderBy('created_at', 'desc')
                       ->get();

        return response()->json([
            'events' => $events
        ], 200);
    }
    //===========================================================
    // عرض كل الفعاليات الخاصة بالأجنحة التي حجزها المستثمر
    public function myBoothEvents()
    {
        $investor = Auth::user()->investor;

        $boothIds = BoothBooking::where('investor_id', $investor->id)
            ->where('status', 'approved')
            ->pluck('booth_id');

        $events = Event::whereIn('booth_id', $boothIds)
            ->with(['booth.exhibition'])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'events' => $events
        ], 200);
    }
    //===========================================================
    // عرض تفاصيل فعالية معينة ضمن أجنحة المستثمر
    public function showEventDetails($eventId)
    {
        $investor = Auth::user()->investor;

        $event = Event::where('id', $eventId)
            ->whereHas('booth', function ($query) use ($investor)
            {
                $query->whereHas('bookings', function ($q) use ($investor)
                {
                    $q->where('investor_id', $investor->id)
                      ->where('status', 'approved');
                });
            })
            ->with(['booth.exhibition', 'tickets'])
            ->firstOrFail();

        return response()->json([
            'event' => $event
        ], 200);
    }
    //===========================================================
    public function addImage(Request $request, $eventId)//إضافة صورة للفعالية
    {
        $request->validate(['image' => 'required|image|max:2048']);

        $investor = Auth::user()->investor;

        $event = Event::where('investor_id', $investor->id)->findOrFail($eventId);

        $path = $request->file('image')->store('events/company', 'public');

        $images = $event->company_images ?? [];
        $images[] = $path;

        $event->update(['company_images' => $images]);

        return response()->json([
            'message' => 'تمت إضافة الصورة بنجاح',
            'images'  => $images
        ], 200);
    }

    //===========================================================
    // 9) حذف صورة من الفعالية
    //===========================================================
    public function deleteImage(Request $request, $eventId)
    {
        $request->validate(['image_path' => 'required|string']);

        $investor = Auth::user()->investor;

        $event = Event::where('investor_id', $investor->id)->findOrFail($eventId);

        $images = $event->company_images ?? [];

        if (!in_array($request->image_path, $images)) {
            return response()->json(['message' => 'الصورة غير موجودة'], 404);
        }

        if (Storage::disk('public')->exists($request->image_path)) {
            Storage::disk('public')->delete($request->image_path);
        }

        $images = array_values(array_filter($images, fn($img) => $img !== $request->image_path));

        $event->update(['company_images' => $images]);

        return response()->json([
            'message' => 'تم حذف الصورة بنجاح',
            'images'  => $images
        ], 200);
    }
}

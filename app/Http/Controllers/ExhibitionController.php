<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExhibitionController extends Controller
{
    public function getAllExhibitions()//عرض كل المعارض
    {
        $exhibitions = Exhibition::orderBy('start_date', 'asc')->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    public function filter(Request $request)//فلترة+بحث
    {
        $query = Exhibition::query();

        // بحث بالاسم
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // فلترة حسب الحالة
        if ($request->has('status') && in_array($request->status, ['upcoming','ongoing','finished'])) {
            $query->where('status', $request->status);
        }

        // فلترة حسب المدينة
        if ($request->has('city') && $request->city != '') {
            $query->where('city', $request->city);
        }

        // فلترة حسب القطاع
        if ($request->has('sector') && $request->sector != '') {
            $query->whereJsonContains('sectors', $request->sector);
        }

        $exhibitions = $query->orderBy('start_date', 'asc')->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    public function show($Exhibition_id)//عرض معرض معين
    {
        $exhibition = Exhibition::with([
            'booths',
            'sponsorEvents',
            'favorites'
        ])->find($Exhibition_id);

        if (!$exhibition)
        {
            return response()->json(['message' => 'Exhibition not found'], 404);
        }

        return response()->json([
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    // هذا المسار مخصص لمنظم المعرض لعرض معارضه فقط
    public function organizerIndex()
    {
        $user = Auth::user();

        $exhibitions = Exhibition::where('organizer_id', $user->id)
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    // إنشاء معرض جديد من قبل المنظم
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'required|string',
            'city' => 'nullable|string',
            'status' => 'nullable|string|in:far,upcoming,ongoing,finished',
            'sectors' => 'nullable|array',
            'extra_services' => 'nullable|array',
        ]);

        $exhibition = Exhibition::create([
            'organizer_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'city' => $request->city,
            'status' => $request->status ?? 'upcoming',
            'sectors' => $request->sectors,
            'extra_services' => $request->extra_services,
            'working_hours' => $request->working_hours ?? 0,
            'is_paid' => $request->is_paid ?? false,
            'ticket_price' => $request->ticket_price,
            'map' => $request->map ?? [],
        ]);

        return response()->json([
            'message' => 'Exhibition created successfully',
            'exhibition' => $exhibition
        ], 201);
    }
    //===============================================================
    // تحديث معرض يخص نفس المنظم فقط
    public function update(Request $request, $id)
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())->findOrFail($id);
        $exhibition->update($request->only([
            'name',
            'type',
            'description',
            'start_date',
            'end_date',
            'location',
            'city',
            'status',
            'copy_status',
            'available_booths',
            'total_booths',
            'total_events',
            'visitors_count',
            'sectors',
            'extra_services',
            'working_hours',
            'is_paid',
            'ticket_price',
            'map'
        ]));

        return response()->json([
            'message' => 'Exhibition updated successfully',
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    public function destroy($id)
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())->findOrFail($id);
        $exhibition->delete();

        return response()->json([
            'message' => 'Exhibition deleted successfully'
        ], 200);
    }
    //===============================================================
    // أرشفة المعرض بدل الحذف لو كان المطلوب من الواجهة
    public function archive($id)
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())->findOrFail($id);
        $exhibition->update([
            'copy_status' => 'archived'
        ]);

        return response()->json([
            'message' => 'Exhibition archived successfully',
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    public function ongoing()//الجارية
    {
        $exhibitions = Exhibition::where('status', 'ongoing')
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    public function finished()//المنتهية
    {
        $exhibitions = Exhibition::where('status', 'finished')
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    public function upcoming()//القادمة
    {
        $exhibitions = Exhibition::where('status', 'upcoming')
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
}

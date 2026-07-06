<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExhibitionRequest;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExhibitionController extends Controller
{
    public function featurrdExhibitionsI()//عرض المعارض المميزة للمستثمر
    {
        $invsetor_user = Auth::user()->investor;
        $exhibitions = Exhibition::orderBy('start_date', 'asc')->get();
        if (
            $invsetor_user->location == $exhibitions->location ||
            $invsetor_user->activity_type == $exhibitions->type &&
            $exhibitions->status == 'upcoming' || $exhibitions->status == 'ongoing' &&
            $exhibitions->available_booths > 0
        ) {
            return response()->json(
                [
                    'exhibitions' => $exhibitions
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'message' => 'No featured exhibitions found',
                    'exhibitions' => $exhibitions
                ],
                200
            );

        }
    }
    //===============================================================
    public function latestExhibitions()//عرض احدث المعارض
    {
        $exhibitions = Exhibition::weher('status', ['upcoming', 'ongoing'])
            ->orderBy('start_date', 'asc')
            ->get();

        $exhibitions_data = $exhibitions->map(function ($exhibition) {
            return [
                'id' => $exhibition->id,
                'name' => $exhibition->name,
                'type' => $exhibition->type,
                'start_date' => $exhibition->start_date,
                'end_date' => $exhibition->end_date,
                'location' => $exhibition->location,
                'city' => $exhibition->city,
                'status' => $exhibition->status,
                'available_booths' => $exhibition->available_booths,
                'total_booths' => $exhibition->total_booths,
                'visitors_count' => $exhibition->visitors_count,
                'is_favorite' => Auth::user()->favorites->where('favoritable_id', $exhibition->id)
                    ->where('favoritable_type', 'App\Models\Exhibition')
                    ->exists()
            ];

        });

        return response()->json(
            [
                'exhibitions' => $exhibitions_data
            ],
            200
        );
    }
    //===============================================================
    public function getAllExhibitions()//عرض كل المعارض
    {
        $exhibitions = Exhibition::orderBy('start_date', 'asc')->get();
        $exhibitions_data = $exhibitions->map(function ($exhibition) {
            return [
                'id' => $exhibition->id,
                'name' => $exhibition->name,
                'type' => $exhibition->type,
                'start_date' => $exhibition->start_date,
                'end_date' => $exhibition->end_date,
                'location' => $exhibition->location,
                'city' => $exhibition->city,
                'status' => $exhibition->status,
                'available_booths' => $exhibition->available_booths,
                'total_booths' => $exhibition->total_booths,
                'visitors_count' => $exhibition->visitors_count,
                'is_favorite' => Auth::user()->favorites->where('favoritable_id', $exhibition->id)
                    ->where('favoritable_type', 'App\Models\Exhibition')
                    ->exists()
            ];

        });

        return response()->json(
            [
                'exhibitions' => $exhibitions_data
            ],
            200
        );
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
        if ($request->has('status') && in_array($request->status, ['far', 'upcoming', 'ongoing', 'finished'])) {
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

        return response()->json(
            [
                'exhibitions' => $exhibitions
            ],
            200
        );
    }
    //===============================================================
    public function show($exhibition_id)//عرض معرض معين
    {
        $user = Auth::user();
        if (
            $user->favorites->where('favoritable_id', $exhibition_id)
                ->where('favoritable_type', 'App\Models\Exhibition')
                ->exists()
        ) {
            $is_favorite = true;
        } else {
            $is_favorite = false;
        }

        $exhibition = Exhibition::with([
            'booths',
            'sponsorEvents',
        ])->find($exhibition_id);

        if (!$exhibition) {
            return response()->json(['message' => 'Exhibition not found'], 404);
        }

        return response()->json([
            'exhibition' => $exhibition,
            'is_favorite' => $is_favorite
        ], 200);
    }
    //===============================================================
    // public function myExhibitions()//o
    // {
    //     $user = Auth::user();

    //     $exhibitions = Exhibition::where('organizer_id', $user->id)
    //         ->orderBy('start_date', 'asc')
    //         ->get();

    //     return response()->json([
    //         'exhibitions' => $exhibitions
    //     ], 200);
    // }
    //===============================================================
    public function store(StoreExhibitionRequest $request)// إنشاء معرض جديد من قبل المنظم
    {
        $organizer = Auth::user()->organizer;
        $validate_data = $request->validated();
        $validate_data['organizer_id'] = Auth::id();

        $exhibition = Exhibition::create($validate_data);

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

    //===============================================================
    //===============================================================
    //=========================الزائر===============================


    public function featuredExhibitionsForVisitor()
    {
        $visitor = auth()->user()->visitor;
        $interests = $visitor->interests ?? [];
        $city = $visitor->city;

        // المعارض المميزة حسب عدة عوامل
        $exhibitions = Exhibition::where('copy_status', 'active') // المعرض منشور
            ->whereIn('status', ['upcoming', 'ongoing']) // قريب أو جاري
            ->when($interests, function ($query) use ($interests) {
                // مطابقة الاهتمامات مع قطاعات المعرض
                return $query->where(function ($q) use ($interests) {
                    foreach ($interests as $interest) {
                        $q->orWhereJsonContains('sectors', $interest);
                    }
                });
            })
            ->when($city, function ($query) use ($city) {
                // إعطاء أولوية للمعارض في نفس مدينة الزائر
                return $query->orderByRaw("city = ? DESC", [$city]);
            })
            ->orderBy('visitors_count', 'desc') // الأكثر شعبية أولاً
            ->take(10)
            ->get();

        return response()->json([
            'message' => 'تم جلب المعارض المميزة للزائر بنجاح',
            'exhibitions' => $exhibitions
        ]);
    }




}

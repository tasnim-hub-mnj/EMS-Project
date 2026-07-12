<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExhibitionRequest;
use App\Http\Requests\UpdateExhibitionRequest;
use App\Models\Exhibition;
use App\Models\ExhibitionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExhibitionController extends Controller
{
    public function store(StoreExhibitionRequest $request)// اضافة معرض
    {
        $organizer= Auth::user()->organizer;
        $validate_data = $request->validated();
        $validate_data['organizer_id']= $organizer->id;
        $validate_data['type']= $organizer->category;
        $validate_data['location']= $organizer->location;
        $validate_data['map'] = json_encode($validate_data['map']);

        // if ($request->hasFile('map'))
        // {
        //     $map = $request->file('map');
        //     $map_path = $map->store('maps', 'public');
        //     $validate_data['map'] = $map_path;
        // }

        $exhibition = Exhibition::create($validate_data);

        return response()->json([
            'message' => 'Exhibition created successfully',
            'exhibition' => $exhibition
        ], 201);
    }
    //===============================================================
    public function StoreImages($request,$exhibition_id)//اضافة صورة للمعرض
    {
        $request->validate([
            'image' => 'required',
            'image.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);
        $images = [];
        if ($request->hasFile('image'))
        {
            foreach ($request->file('image') as $img)
            {
                $path = $img->store('exhibition_images', 'public');

                $images[] = ExhibitionImage::create([
                    'exhibition_id' => $exhibition_id,
                    'image' => $path
                ]);
            }
        }

        return response()->json([
        'message' => 'Images Exhibition stored successfully',
        'images' => $images
        ], 201);
    }
    //===============================================================
    public function update(UpdateExhibitionRequest $request,$exhibition_id)//تعديل معرض
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
        ->findOrFail($exhibition_id);

        $exhibition->update($request->validated());

        return response()->json([
            'message' => 'Exhibition updated successfully',
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    public function destroy($exhibition_id)//حذف معرض
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
        ->findOrFail($exhibition_id);

        $exhibition->delete();

        return response()->json([
            'message' => 'Exhibition deleted successfully'
        ], 200);
    }
    //===============================================================
    public function featurrdExhibitionsI()//عرض المعارض المميزة للمستثمر
    {
        $invsetor_user=Auth::user()->investor;
        $exhibitions = Exhibition::where('copy_status', 'active')
        ->where('location', $invsetor_user->location)
        ->where('type', $invsetor_user->activity_type)
        ->whereIn('status', ['upcoming', 'ongoing'])
        ->where('available_booths', '>', 0)
        ->orderBy('start_date', 'asc')
        ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);

    }
    //===============================================================
    public function latestExhibitions()//عرض احدث المعارض
    {
        $exhibitions = Exhibition::whereIn('status',['upcoming','ongoing'])
        ->where('copy_status', 'active')
        ->orderBy('start_date', 'asc')
        ->get();

        $exhibitions_data = $exhibitions->map(function ($exhibition)
        {
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
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $exhibition->id)
                    ->where('favoritable_type', Exhibition::class)
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
    public function getAllExhibitions()//عرض كل المعارض+الاجنحة
    {
        $exhibitions = Exhibition::orderBy('start_date', 'asc')
        ->where('copy_status', 'active')
        ->get();

        $exhibitions_data = $exhibitions->map(function ($exhibition)
        {
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
                    ->exists(),
                'booths'=> $exhibition->booths,
            ];

        });

        return response()->json(
            [
                'exhibitions' => $exhibitions_data,
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
        $user=Auth::user();
        $is_favorite = $user->favorites()
        ->where('favoritable_id', $exhibition_id)
        ->where('favoritable_type', Exhibition::class)
        ->exists();

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
    public function archive($exhibition_id)//ارشفة معرض
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
        ->findOrFail($exhibition_id);

        $exhibition->update([
            'copy_status' => 'archived'
        ]);

        return response()->json([
            'message' => 'Exhibition archived successfully',
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    public function getMyExhibition($exhibition_id)//عرض المعرض الخاص بي
    {
        $organizer = Auth::user()->organizer;

        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->with('booths')
            ->findOrFail($exhibition_id);

        return response()->json([
            'exhibition' => $exhibition
        ], 200);
    }

    //===============================================================
    public function getMap($exhibition_id)//عرض خريطة معرض
    {
        $organizer = Auth::user()->organizer;

        $exhibition = Exhibition::where('organizer_id', $organizer->id)
        ->findOrFail($exhibition_id);
        $map = json_decode($exhibition->map, true);

        return response()->json([
            'map' => $map,
        ], 200);

    }
    //===============================================================
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
    //=========================الزائر===============================

    public function featuredExhibitionsForVisitor()
    {
        $visitor = Auth::user()->visitor;
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

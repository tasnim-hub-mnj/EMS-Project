<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoothRequest;
use App\Http\Requests\UpdateBoothRequest;
use App\Models\BoothBooking;
use App\Models\Booth;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Storage;

class BoothController extends Controller
{
    public function store(StoreBoothRequest $request, $exhibition_id)
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
            ->findOrFail($exhibition_id);

        $data = $request->validated();

        $booth = Booth::create([
            'exhibition_id' => $exhibition->id,
            'number'        => $data['number'],
            'area'          => $data['area'],
            'status'        => $data['status'] ?? 'available',
            'price'         => $data['price'],
            'location'      => $data['location'],
            'services'      => isset($data['services'])
                                ? json_encode($data['services'])
                                : json_encode([]),
            'map_x'         => $data['map_x'] ?? null,
            'map_y'         => $data['map_y'] ?? null,
            'map_z'         => $data['map_z'] ?? null,
        ]);

        if ($request->hasFile('image'))
        {
            $path = $request->file('image')->store('booth_images', 'public');
            $booth->update(['image' => $path]);
        }

        $exhibition->increment('total_booths');//+1

        return response()->json([
            'message' => 'Booth created successfully',
            'booth'   => $booth
        ], 200);
    }
    //=============================================================================
    public function update(UpdateBoothRequest $request, $exhibition_id, $booth_id)
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
            ->findOrFail($exhibition_id);

        $booth = Booth::where('exhibition_id', $exhibition->id)
            ->findOrFail($booth_id);

        $data = $request->validated();

        // تحويل الخدمات إلى JSON إذا كانت مصفوفة
        if (isset($data['services']) && is_array($data['services']))
        {
            $data['services'] = json_encode($data['services']);
        }

        if ($request->hasFile('image'))
        {
            if ($booth->image)
            {
                Storage::disk('public')->delete($booth->image);
            }

            $path = $request->file('image')->store('booth_images', 'public');
            $data['image'] = $path;
        }

        $booth->update($data);

        return response()->json([
            'message' => 'Booth updated successfully',
            'booth' => $booth
        ], 200);
    }

    //=============================================================================
    public function index($exhibition_id)//عرض كل الاجنحة الخاصة بمعرض معين
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
        ->findOrFail($exhibition_id);

        $booths = Booth::where('exhibition_id', $exhibition->id)->get();

        return response()->json([
            'booths' => $booths
        ], 200);
    }
    //=============================================================================
    public function show($exhibition_id, $booth_id)//عرض جناح معين
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
        ->findOrFail($exhibition_id);

        $booth = Booth::where('exhibition_id', $exhibition->id)
        ->findOrFail($booth_id);

        return response()->json([
            'booth' => $booth
        ], 200);
    }
    //=============================================================================
    public function delete($exhibition_id, $booth_id)
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
            ->findOrFail($exhibition_id);

        $booth = Booth::where('exhibition_id', $exhibition->id)
            ->findOrFail($booth_id);

        $booth->delete();
        $exhibition->decrement('total_booths');

        return response()->json([
            'message' => 'Booth deleted successfully'
        ], 200);
    }
    //=============================================================================
    //=============================================================================



//*****************************************************************************
//**********************************HANAN😁***********************************
//*****************************************************************************


    //===============الزائر======================//
    // عرض الاجنحة كاملة مع امكانية البحث وبجيب الاجنحة مع المعارض المرتبطة فيهن
    public function AllBooths(Request $request)
    {
        $query = Booth::with('exhibition')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('number', 'LIKE', "%$search%")
                    ->orWhere('location', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('exhibition_id')) {
            $query->where('exhibition_id', $request->input('exhibition_id'));
        }

        return response()->json($query->get());
    }
    //===================================================
    // عرض كشك معين
    public function showBooth($id)
    {
        $booth = Booth::with([
            'exhibition',
            'profile',// 00
            'images',
            'bookings',
            'reviews.user'
        ])->find($id);

        if (!$booth) {
            return response()->json(['message' => 'الكشك غير موجود'], 404);
        }

        return response()->json($booth);
    }


}

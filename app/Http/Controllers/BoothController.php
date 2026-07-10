<?php

namespace App\Http\Controllers;

use App\Models\BoothBooking;
use App\Models\Booth;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use App\Models\User;
use App\Notifications\OrderStatusNotification;


class BoothController extends Controller
{
    public function getBothsExhibition($exhibition_id)//عرض كل الاجنحة الخاصة بمعرض معين
    {
        $exhibition = Exhibition::findOrfail($exhibition_id);
        $booths = $exhibition->booths;

        return response()->json([
            'booths' => $booths
        ], 200);
    }
    //=================================================================================
    // public function boothBasicDetails($booth_id)//عرض جناح على لخريطة
    // {
    //     $booth = Booth::findOrfail($booth_id);

    //     if (!$booth)
    //     {
    //         return response()->json(['message' => 'Booth not found'], 404);
    //     }

    //     $booth_map=$booth->map(function($b)
    //     {
    //         return
    //         [
    //             'id' => $b->id,
    //             'number'=>$b->number,
    //             'area'=>$b->area,
    //             'price'=>$b->price,
    //             'location'=>$b->location,
    //             'high'=>$b->map_y,
    //             'X # Y'=>[$b->map_x.'#'.$b->map_z],
    //             'services'=>$b->services
    //         ];
    //     });
    //     return response()->json([
    //         'booth' => $booth_map
    //     ], 200);
    // }

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
            'profile',
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

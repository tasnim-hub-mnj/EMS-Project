<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatedBoothProfileRequest;
use App\Models\BoothBooking;
use App\Models\BoothProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BoothProfileController extends Controller
{
    public function getBoothProfile($booth_profile_id)
    {
        $booth_profile=BoothProfile::findOrfail($booth_profile_id);
        return response()->json([
            'booth_profile'=>$booth_profile
        ], 200);

    }
    // public function getBoothProfile($booking_id)
    // {
    //     $booking=BoothBooking::findOrfail($booking_id);
    //     $booth=$booking->booth;

    //     $booth_profile= $booking->map(function($bookin)
    //     {
    //         $investor=$bookin->investor;
    //         return
    //         [
    //             'company_nature'=>$investor->company_nature,
    //             ''
    //         ];
    //     });

    //     return response()->json([
    //         'booth_profile'=>$booth_profile
    //     ], 200);

    // }
    //=====================================================================
    public function UpdateBoothPorfile(UpdatedBoothProfileRequest $request,$booth_profile_id)
    {
        $user=Auth::user();
        $booth_profile=BoothProfile::findOrfail($booth_profile_id);
        $validate=$request->validated();


        $booth_profile->update($validate);

        return response()->json([
            'message'=>'Updated booth profile',
            'booth_profile' =>$booth_profile,
        ], 200);

    }
    //=====================================================================
    //=====================================================================

















































































































    //=====================================================================
    // public function addBoothImage(Request $request, $boothId)
    // {
    //     $request->validate([
    //         'image' => 'required|image|max:2048'
    //     ]);

    //     $investor = Auth::user()->investor;

    //     $profile = BoothProfile::where('booth_id', $boothId)
    //                         ->where('investor_id', $investor->id)
    //                         ->firstOrFail();

    //     $path = $request->file('image')->store('booth_images', 'public');

    //     $images = $profile->booth_images ?? [];
    //     $images[] = $path;

    //     $profile->update(['booth_images' => $images]);

    //     return response()->json([
    //         'message' => 'Booth image added successfully',
    //         'images' => $images
    //     ]);
    // }

    // //=====================================================================
    // public function deleteProductImage(Request $request, $boothId)
    // {
    //     $request->validate([
    //         'image_path' => 'required|string'
    //     ]);

    //     $investor = Auth::user()->investor;

    //     $profile = BoothProfile::where('booth_id', $boothId)
    //                         ->where('investor_id', $investor->id)
    //                         ->firstOrFail();

    //     $images = $profile->product_images ?? [];

    //     // إذا الصورة غير موجودة
    //     if (!in_array($request->image_path, $images)) {
    //         return response()->json(['message' => 'Image not found'], 404);
    //     }

    //     // حذف الصورة من التخزين
    //     if (Storage::disk('public')->exists($request->image_path)) {
    //         Storage::disk('public')->delete($request->image_path);
    //     }

    //     // حذف الصورة من الـ array
    //     $images = array_values(array_filter($images, function ($img) use ($request) {
    //         return $img !== $request->image_path;
    //     }));

    //     // تحديث JSON
    //     $profile->update(['product_images' => $images]);

    //     return response()->json([
    //         'message' => 'Product image deleted successfully',
    //         'images' => $images
    //     ], 200);
    // }

    // //=====================================================================
    // public function deleteBoothImage(Request $request, $boothId)
    // {
    //     $request->validate([
    //         'image_path' => 'required|string'
    //     ]);

    //     $investor = Auth::user()->investor;

    //     $profile = BoothProfile::where('booth_id', $boothId)
    //                         ->where('investor_id', $investor->id)
    //                         ->firstOrFail();

    //     $images = $profile->booth_images ?? [];

    //     // إذا الصورة غير موجودة
    //     if (!in_array($request->image_path, $images)) {
    //         return response()->json(['message' => 'Image not found'], 404);
    //     }

    //     // حذف الصورة من التخزين
    //     if (Storage::disk('public')->exists($request->image_path)) {
    //         Storage::disk('public')->delete($request->image_path);
    //     }

    //     // حذف الصورة من الـ array
    //     $images = array_values(array_filter($images, function ($img) use ($request) {
    //         return $img !== $request->image_path;
    //     }));

    //     // تحديث JSON
    //     $profile->update(['booth_images' => $images]);

    //     return response()->json([
    //         'message' => 'Booth image deleted successfully',
    //         'images' => $images
    //     ], 200);
    // }


}

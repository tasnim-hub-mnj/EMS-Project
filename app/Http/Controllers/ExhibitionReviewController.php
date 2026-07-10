<?php

namespace App\Http\Controllers;

use App\Models\ExhibitionReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExhibitionReviewController extends Controller
{

    //عرض كل تقييمات المعارض
    public function allExhibitionReviews()
    {
        $reviews = ExhibitionReview::with(['visitor', 'exhibition'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($reviews->isEmpty()) {
            return response()->json([
                'message' => 'لا يوجد أي تقييمات للمعارض حالياً'
            ]);
        }

        return response()->json([
            'message' => 'تم جلب جميع تقييمات المعارض بنجاح',
            'reviews' => $reviews
        ]);
    }
    //===============================================
    // إضافة تقييم معرض
    public function addReviewExhibition(Request $request)
    {
        $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $visitor = Auth::user()->visitor;

        if (!$visitor) {
            return response()->json(['message' => 'يجب أن يكون لديك ملف زائر لإضافة تقييم'], 403);
        }

        ExhibitionReview::create([
            'visitor_id' => $visitor->id,
            'exhibition_id' => $request->exhibition_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'تم إضافة تقييم المعرض بنجاح']);
    }
    //===============================================

    // عرض كل تقييمات معرض معيّن
    public function showReviewsExhibition($exhibition_id)
    {
        $reviews = ExhibitionReview::with('visitor')
            ->where('exhibition_id', $exhibition_id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'لا يوجد تقييمات لهذا المعرض']);
        }

        return response()->json([
            'message' => 'تم جلب تقييمات المعرض بنجاح',
            'reviews' => $reviews
        ]);
    }
}

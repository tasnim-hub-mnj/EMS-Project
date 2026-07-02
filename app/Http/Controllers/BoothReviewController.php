<?php

namespace App\Http\Controllers;

use App\Models\BoothReview;
use Illuminate\Http\Request;

class BoothReviewController extends Controller
{
    //عرض كل تقييمات الاجنحة
    public function allBoothReviews()
    {
        $reviews = BoothReview::with(['visitor', 'booth'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($reviews->isEmpty()) {
            return response()->json([
                'message' => 'لا يوجد أي تقييمات للأجنحة حالياً'
            ]);
        }

        return response()->json([
            'message' => 'تم جلب جميع تقييمات الأجنحة بنجاح',
            'reviews' => $reviews
        ]);
    }

    //================================================
    // إضافة تقييم جناح
    public function AddReviewBooth(Request $request)
    {
        $request->validate([
            'booth_id' => 'required|exists:booths,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $visitor = auth()->user()->visitor;

        if (!$visitor) {
            return response()->json(['message' => 'يجب أن يكون لديك ملف زائر لإضافة تقييم'], 403);
        }

        BoothReview::create([
            'visitor_id' => $visitor->id,
            'booth_id' => $request->booth_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'تم إضافة تقييم الجناح بنجاح']);
    }
    //================================================

    // عرض كل تقييمات جناح معيّن
    public function showReviewsBooth($booth_id)
    {
        $reviews = BoothReview::with('visitor')
            ->where('booth_id', $booth_id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'لا يوجد تقييمات لهذا الجناح']);
        }

        return response()->json([
            'message' => 'تم جلب تقييمات الجناح بنجاح',
            'reviews' => $reviews
        ]);
    }
}

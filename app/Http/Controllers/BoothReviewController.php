<?php

namespace App\Http\Controllers;

use App\Models\BoothReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoothReviewController extends Controller
{
    public function allBoothReviews()//عرض كل تقييمات الاجنحة
    {
        $reviews = BoothReview::with(['visitor', 'booth'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($reviews->isEmpty())
        {
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
    public function AddReviewBooth(Request $request)// إضافة تقييم جناح
    {
        $request->validate([
            'booth_id' => 'required|exists:booths,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $visitor = Auth::user()->visitor;

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

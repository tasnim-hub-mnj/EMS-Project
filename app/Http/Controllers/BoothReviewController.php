<?php

namespace App\Http\Controllers;

use App\Models\BoothReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoothReviewController extends Controller
{
    // public function allBoothReviews()//عرض كل تقييمات الاجنحة
    // {
    //     $reviews = BoothReview::with(['visitor', 'booth'])
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     if ($reviews->isEmpty()) {
    //         return response()->json([
    //             'message' => 'لا يوجد أي تقييمات للأجنحة حالياً'
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'تم جلب جميع تقييمات الأجنحة بنجاح',
    //         'reviews' => $reviews
    //     ]);
    // }
    public function getAllBoothsReviews()
    {
        $reviews = BoothReview::with('visitor')
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->visitor?->user_id,
                'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')),
                'user_avatar' => $r->visitor?->avatar_url,
                'target_type' => 'booth',
                'target_id' => $r->booth_id,
                'rating' => (float) $r->rating,

                'org_score' => null,
                'content_score' => null,
                'services_score' => null,

                'comment' => $r->comment,
                'created_at' => $r->created_at->toIso8601String(),
            ];
        });

        return response()->json($formattedReviews, 200);
    }
    //================================================
    // public function AddReviewBooth(Request $request)// إضافة تقييم جناح
    // {
    //     $request->validate([
    //         'booth_id' => 'required|exists:booths,id',
    //         'rating' => 'required|numeric|min:1|max:5',
    //         'comment' => 'nullable|string',
    //     ]);

    //     $visitor = Auth::user()->visitor;

    //     if (!$visitor) {
    //         return response()->json(['message' => 'يجب أن يكون لديك ملف زائر لإضافة تقييم'], 403);
    //     }

    //     BoothReview::create([
    //         'visitor_id' => $visitor->id,
    //         'booth_id' => $request->booth_id,
    //         'rating' => $request->rating,
    //         'comment' => $request->comment,
    //     ]);

    //     return response()->json(['message' => 'تم إضافة تقييم الجناح بنجاح']);
    // }
    //================================================

    // عرض كل تقييمات جناح معيّن
    // public function showReviewsBooth($booth_id)
    // {
    //     $reviews = BoothReview::with('visitor')
    //         ->where('booth_id', $booth_id)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     if ($reviews->isEmpty()) {
    //         return response()->json(['message' => 'لا يوجد تقييمات لهذا الجناح']);
    //     }

    //     return response()->json([
    //         'message' => 'تم جلب تقييمات الجناح بنجاح',
    //         'reviews' => $reviews
    //     ]);
    // }
    //=============================================================
    public function submitBoothReview(Request $request)
    {
        $data = $request->validate([
            'booth_id' => 'required|exists:booths,id',
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string',
        ]);

        $visitor = auth()->user()->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }


        $review = BoothReview::create([
            'visitor_id' => $visitor->id,
            'booth_id' => $data['booth_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $formattedReview = BoothReview::with('visitor')
            ->where('id', $review->id)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'user_id' => $r->visitor?->user_id,
                    'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')), // الاسم الكامل
                    'user_avatar' => $r->visitor?->avatar_url,
                    'target_type' => 'booth',
                    'target_id' => $r->booth_id,
                    'rating' => (float) $r->rating,

                    'org_score' => null,
                    'content_score' => null,
                    'services_score' => null,

                    'comment' => $r->comment,
                    'created_at' => $r->created_at->toIso8601String(),
                ];
            })
            ->first();
        return response()->json($formattedReview, 201);
    }
    //=======================================================
    public function getBoothReviews($boothId)
    {

        $reviews = BoothReview::with('visitor')
            ->where('booth_id', $boothId)
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->visitor?->user_id,
                'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')), // الاسم الكامل
                'user_avatar' => $r->visitor?->avatar_url,
                'target_type' => 'booth',
                'target_id' => $r->booth_id,
                'rating' => (float) $r->rating,

                'org_score' => null,
                'content_score' => null,
                'services_score' => null,

                'comment' => $r->comment,
                'created_at' => $r->created_at->toIso8601String(),
            ];
        });

        return response()->json($formattedReviews, 200);
    }
}

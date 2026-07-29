<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BoothReviewController extends Controller
{
    public function getAllBoothsReviews()
    {
        $reviews = BoothReview::with('visitor')
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'user_id' => (int) $r->visitor?->user_id,
                'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')),
                'user_avatar' => $r->visitor?->avatar_url ? asset($r->visitor?->avatar_url) : null,
                'target_type' => 'booth',
                'target_id' => (int) $r->booth_id,
                'rating' => (float) $r->rating,
                'org_score' => null,
                'content_score' => null,
                'services_score' => null,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'تم جلب جميع تقييمات الأجنحة بنجاح',
            'data' => $formattedReviews
        ], 200);
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

        $validator = Validator::make($request->all(), [
            'booth_id' => 'required|exists:booths,id',
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $review = BoothReview::create([
            'visitor_id' => $visitor->id,
            'booth_id' => $request->booth_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال تقييم الجناح بنجاح',
            'data' => [
                'id' => (int) $review->id,
                'user_id' => (int) $visitor->user_id,
                'user_name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? '')),
                'user_avatar' => $visitor->avatar_url ? asset($visitor->avatar_url) : null,
                'target_type' => 'booth',
                'target_id' => (int) $review->booth_id,
                'rating' => (float) $review->rating,
                'org_score' => null,
                'content_score' => null,
                'services_score' => null,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
            ]
        ], 201);
    }
    //=======================================================
    public function getBoothReviews($boothId)
    {

        $booth = Booth::find($boothId);
        if (!$booth) {
            return response()->json([
                'status' => false,
                'message' => 'الجناح غير موجود'
            ], 404);
        }
        $reviews = BoothReview::with('visitor')
            ->where('booth_id', $boothId)
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            $visitor = $r->visitor;
            $fullName = trim(($visitor?->first_name ?? '') . ' ' . ($visitor?->last_name ?? ''));

            return [
                'id' => $r->id,
                'user_id' => $visitor?->user_id ?? $r->visitor_id,
                'user_name' => $fullName !== '' ? $fullName : 'زائر',
                'user_avatar' => $visitor?->avatar_url ?? null,
                'target_type' => 'booth',
                'target_id' => (int) $r->booth_id,
                'rating' => (float) ($r->rating ?? 0),

                'org_score' => null,
                'content_score' => null,
                'services_score' => null,

                'comment' => $r->comment ?? '',
                'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formattedReviews
        ], 200);
    }
}

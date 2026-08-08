<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\ExhibitionReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExhibitionReviewController extends Controller
{
    public function getAllExhibitionsReviews()//عرض كل تقييمات المعارض 
    {
        $reviews = ExhibitionReview::with('visitor')
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            $mainRating = (float) $r->rating;

            return [
                'id' => (int) $r->id,
                'user_id' => (int) $r->visitor?->user_id,
                'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')),
                'user_avatar' => $r->visitor?->avatar_url ? asset($r->visitor->avatar_url) : null,
                'target_type' => 'exhibition',
                'target_id' => (int) $r->exhibition_id,
                'rating' => $mainRating,
                'org_score' => $mainRating,
                'content_score' => $mainRating,
                'services_score' => $mainRating,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
            ];
        });
        return response()->json([
            'status' => true,
            'message' => 'تم جلب جميع تقييمات المعارض بنجاح',
            'data' => $formattedReviews
        ], 200);
    }
    //===============================================
    // // إضافة تقييم معرض
    // public function addReviewExhibition(Request $request)
    // {
    //     $request->validate([
    //         'exhibition_id' => 'required|exists:exhibitions,id',
    //         'rating' => 'required|numeric|min:1|max:5',
    //         'comment' => 'nullable|string',
    //     ]);

    //     $visitor = Auth::user()->visitor;

    //     if (!$visitor) {
    //         return response()->json(['message' => 'يجب أن يكون لديك ملف زائر لإضافة تقييم'], 403);
    //     }

    //     ExhibitionReview::create([
    //         'visitor_id' => $visitor->id,
    //         'exhibition_id' => $request->exhibition_id,
    //         'rating' => $request->rating,
    //         'comment' => $request->comment,
    //     ]);

    //     return response()->json(['message' => 'تم إضافة تقييم المعرض بنجاح']);
    // }
    // //===============================================

    // // عرض كل تقييمات معرض معيّن
    // public function showReviewsExhibition($exhibition_id)
    // {
    //     $reviews = ExhibitionReview::with('visitor')
    //         ->where('exhibition_id', $exhibition_id)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     if ($reviews->isEmpty()) {
    //         return response()->json(['message' => 'لا يوجد تقييمات لهذا المعرض']);
    //     }

    //     return response()->json([
    //         'message' => 'تم جلب تقييمات المعرض بنجاح',
    //         'reviews' => $reviews
    //     ]);
    // }

    //=============================================================
    public function submitExhibitionReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exhibition_id' => 'required|exists:exhibitions,id',
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

        $mainRating = (float) $request->rating;

        $review = ExhibitionReview::updateOrCreate(
            [
                'visitor_id' => $visitor->id,
                'exhibition_id' => $request->exhibition_id,
            ],
            [
                'rating' => $mainRating,
                'comment' => $request->comment,
            ]
        );

        // 3. تحديث متوسط تقييم المعرض
        $exhibition = Exhibition::find($request->exhibition_id);
        if ($exhibition) {
            $avgRating = ExhibitionReview::where('exhibition_id', $exhibition->id)->avg('rating');
            $exhibition->update(['rating' => round($avgRating, 1)]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال التقييم بنجاح',
            'data' => [
                'id' => (int) $review->id,
                'user_id' => (int) $visitor->user_id,
                'user_name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? '')),
                'user_avatar' => $visitor->avatar_url ? asset($visitor->avatar_url) : null,
                'target_type' => 'exhibition',
                'target_id' => (int) $review->exhibition_id,
                'rating' => $mainRating,
                'org_score' => $mainRating,
                'content_score' => $mainRating,
                'services_score' => $mainRating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
            ]
        ], 201);
    }
    //=========================================================
    public function getExhibitionReviews($exhibitionId)
    {
        $reviews = ExhibitionReview::with('visitor')
            ->where('exhibition_id', $exhibitionId)
            ->latest()
            ->get();
        $formattedReviews = $reviews->map(function ($r) {
            $visitor = $r->visitor;
            $fullName = trim(($visitor?->first_name ?? '') . ' ' . ($visitor?->last_name ?? ''));
            $mainRating = (float) $r->rating;

            return [
                'id' => (int) $r->id,
                'user_id' => (int) ($visitor?->user_id ?? $r->visitor_id),
                'user_name' => $fullName !== '' ? $fullName : 'زائر',
                'user_avatar' => $visitor?->avatar_url ? asset($visitor->avatar_url) : null,
                'target_type' => 'exhibition',
                'target_id' => (int) $r->exhibition_id,
                'rating' => $mainRating,

                'org_score' => $mainRating,
                'content_score' => $mainRating,
                'services_score' => $mainRating,

                'comment' => $r->comment ?? '',
                'created_at' => $r->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'تم جلب تقييمات المعرض بنجاح',
            'data' => $formattedReviews
        ], 200);
    }
}



<?php

namespace App\Http\Controllers;

use App\Models\ExhibitionReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExhibitionReviewController extends Controller
{

    // //عرض كل تقييمات المعارض
    // public function allExhibitionReviews()
    // {
    //     $reviews = ExhibitionReview::with(['visitor', 'exhibition'])
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     if ($reviews->isEmpty()) {
    //         return response()->json([
    //             'message' => 'لا يوجد أي تقييمات للمعارض حالياً'
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'تم جلب جميع تقييمات المعارض بنجاح',
    //         'reviews' => $reviews
    //     ]);
    // }
    public function getAllExhibitionsReviews()
    {
        $reviews = ExhibitionReview::with('visitor')
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->visitor?->user_id,
                'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')),
                'user_avatar' => $r->visitor?->avatar_url,
                'target_type' => 'exhibition',
                'target_id' => $r->exhibition_id,
                'rating' => (float) $r->rating,

                'org_score' => (float) $r->rating,
                'content_score' => (float) $r->rating,
                'services_score' => (float) $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->toIso8601String(),
            ];
        });

        return response()->json($formattedReviews, 200);
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

        $data = $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'rating' => 'required|numeric|min:0|max:5',
            'org_score' => 'required|numeric|min:0|max:5',
            'content_score' => 'required|numeric|min:0|max:5',
            'services_score' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string',
        ]);

        // جلب الزائر المسجل حالياً
        $visitor = auth()->user()->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $review = ExhibitionReview::create([
            'visitor_id' => $visitor->id,
            'exhibition_id' => $data['exhibition_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $formattedReview = ExhibitionReview::with('visitor')
            ->where('id', $review->id)
            ->get()
            ->map(function ($r) use ($data) {
                return [
                    'id' => $r->id,
                    'user_id' => $r->visitor?->user_id,
                    'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')),
                    'user_avatar' => $r->visitor?->avatar_url,
                    'target_type' => 'exhibition',
                    'target_id' => $r->exhibition_id,
                    'rating' => (float) $r->rating,

                    'org_score' => (float) $data['org_score'],
                    'content_score' => (float) $data['content_score'],
                    'services_score' => (float) $data['services_score'],

                    'comment' => $r->comment,
                    'created_at' => $r->created_at->toIso8601String(),
                ];
            })
            ->first();
        return response()->json($formattedReview, 201);
    }
    //=========================================================
    public function getExhibitionReviews($exhibitionId)
    {

        $reviews = ExhibitionReview::with('visitor')
            ->where('exhibition_id', $exhibitionId)
            ->latest()
            ->get();

        $formattedReviews = $reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->visitor?->user_id,
                'user_name' => trim(($r->visitor?->first_name ?? '') . ' ' . ($r->visitor?->last_name ?? '')), // الاسم الكامل من جدول الزوار
                'user_avatar' => $r->visitor?->avatar_url,
                'target_type' => 'exhibition',
                'target_id' => $r->exhibition_id,
                'rating' => (float) $r->rating,

                'org_score' => (float) $r->rating,
                'content_score' => (float) $r->rating,
                'services_score' => (float) $r->rating,

                'comment' => $r->comment,
                'created_at' => $r->created_at->toIso8601String(),
            ];
        });

        return response()->json($formattedReviews, 200);
    }

}


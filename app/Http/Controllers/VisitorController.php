<?php

namespace App\Http\Controllers;

use App\Models\BoothReview;
use App\Models\ExhibitionReview;
use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    //عرض كل تقييمات زائر محدد
    public function visitorReviews($visitor_id)
    {
        // التأكد من وجود الزائر
        $visitor = Visitor::find($visitor_id);

        if (!$visitor) {
            return response()->json([
                'message' => 'الزائر غير موجود'
            ], 404);
        }

        // تقييمات المعارض
        $exhibitionReviews = ExhibitionReview::with('exhibition')
            ->where('visitor_id', $visitor_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // تقييمات الأجنحة
        $boothReviews = BoothReview::with('booth')
            ->where('visitor_id', $visitor_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // إذا ما في ولا تقييم
        if ($exhibitionReviews->isEmpty() && $boothReviews->isEmpty()) {
            return response()->json([
                'message' => 'لا يوجد أي تقييمات لهذا الزائر'
            ]);
        }

        return response()->json([
            'message' => 'تم جلب جميع تقييمات الزائر بنجاح',
            'visitor' => $visitor,
            'exhibition_reviews' => $exhibitionReviews,
            'booth_reviews' => $boothReviews
        ]);
    }

}

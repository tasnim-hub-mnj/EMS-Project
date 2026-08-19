<?php

namespace App\Http\Controllers;

use App\Models\BoothReview;
use App\Models\ExhibitionReview;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class VisitorController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'visitor',
                'status' => 'pending',
            ]);

            Visitor::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    //================================================================

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الدخول غير صحيحة، البريد الإلكتروني أو كلمة المرور خاطئة.',
            ], 401);
        }

        $user = Auth::user();
        $visitor = $user->visitor;

        $ticketsCount = 0;
        $scheduleCount = 0;
        $favoritesCount = 0;

        if ($visitor) {
            $ticketsCount = ($visitor->tickets()->count() ?? 0) +
                ($visitor->eventTickets()->count() ?? 0) +
                ($visitor->sponsorEventTickets()->count() ?? 0);

            $scheduleCount = method_exists($visitor, 'schedules') ? $visitor->schedules()->count() : 0;
            $favoritesCount = method_exists($visitor, 'favorites') ? $visitor->favorites()->count() : 0;
        }

        $token = $user->createToken('visitor_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'first_name' => $visitor ? $visitor->first_name : '',
                'last_name' => $visitor ? $visitor->last_name : '',
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'avatar' => $visitor ? $visitor->avatar_url : null,
                'interests' => $visitor ? $visitor->interests : [],
                'profession' => $visitor ? $visitor->profession : '',
                'city' => $visitor ? $visitor->city : '',
                'hobby' => $visitor ? $visitor->hobby : '',
                'schedule_count' => $scheduleCount,
                'tickets_count' => $ticketsCount,
                'favorites_count' => $favoritesCount,
            ]
        ], 200);
    }
    //=================================================================
    public function logout(Request $request)
    {
        // حذف التوكين المستخدَم حالياً للتسجيل
        $request->user()->currentAccessToken()->delete();

        // إرجاع الاستجابة بنفس الشكل المطلوب في الموثق
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }
    //=================================================================

    public function visitorReviews($visitor_id)
    {
        $visitor = Visitor::find($visitor_id);

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'الزائر غير موجود'
            ], 404);
        }

        $exhibitionReviews = ExhibitionReview::where('visitor_id', $visitor_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $boothReviews = BoothReview::where('visitor_id', $visitor_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $fullName = trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? ''));
        $userName = $fullName !== '' ? $fullName : 'زائر';
        $userAvatar = $visitor->avatar_url ? asset($visitor->avatar_url) : null;

        $formattedExhibitions = $exhibitionReviews->map(function ($r) use ($visitor, $userName, $userAvatar) {
            $mainRating = (float) ($r->rating ?? 0);
            return [
                'id' => (int) $r->id,
                'user_id' => (int) ($visitor->user_id ?? $r->visitor_id),
                'user_name' => $userName,
                'user_avatar' => $userAvatar,
                'target_type' => 'exhibition',
                'target_id' => (int) $r->exhibition_id,
                'rating' => $mainRating,

                'org_score' => $mainRating,
                'content_score' => $mainRating,
                'services_score' => $mainRating,

                'comment' => $r->comment ?? '',
                'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
            ];
        });

        $formattedBooths = $boothReviews->map(function ($r) use ($visitor, $userName, $userAvatar) {
            $mainRating = (float) ($r->rating ?? 0);
            return [
                'id' => (int) $r->id,
                'user_id' => (int) ($visitor->user_id ?? $r->visitor_id),
                'user_name' => $userName,
                'user_avatar' => $userAvatar,
                'target_type' => 'booth',
                'target_id' => (int) $r->booth_id,
                'rating' => $mainRating,

                'org_score' => null,
                'content_score' => null,
                'services_score' => null,

                'comment' => $r->comment ?? '',
                'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
            ];
        });

        $allReviews = $formattedExhibitions->concat($formattedBooths)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب جميع تقييمات الزائر بنجاح',
            'data' => $allReviews
        ], 200);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\BoothReview;
use App\Models\ExhibitionReview;
use App\Mail\VerificationCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

            $otp = OtpCode::create([
                'user_id' => $user->id,
                'code' => $this->newOtpCode(),
                'expires_at' => now()->addMinutes(10),
                'is_used' => false,
            ]);
            DB::commit();

            try {
                Mail::to($user->email)->sendNow(new VerificationCodeMail($otp));
            } catch (\Throwable $mailException) {
                report($mailException);
            }

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'email' => $user->email,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    //================================================================

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'visitor')
            ->firstOrFail();
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json(['status' => false, 'message' => 'OTP not found'], 404);
        }
        if ($otp->expires_at && now()->greaterThan($otp->expires_at)) {
            return response()->json(['status' => false, 'message' => 'OTP expired'], 400);
        }

        $otp->update(['is_used' => true]);
        $user->update(['is_verified' => true]);
        $visitor = $user->visitor;

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully',
            'token' => $user->createToken('visitor_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->visitorPayload($user, $visitor),
        ], 200);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)
            ->where('role', 'visitor')
            ->firstOrFail();

        OtpCode::where('user_id', $user->id)->delete();
        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $this->newOtpCode(),
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);
        Mail::to($user->email)->sendNow(new VerificationCodeMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP resent successfully',
        ], 200);
    }

    private function newOtpCode(): int
    {
        do {
            $code = random_int(100000, 999999);
        } while (OtpCode::where('code', $code)->exists());

        return $code;
    }

    private function visitorPayload(User $user, ?Visitor $visitor): array
    {
        return [
            'id' => $user->id,
            'first_name' => $visitor?->first_name ?? '',
            'last_name' => $visitor?->last_name ?? '',
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'avatar' => $visitor?->avatar_url,
            'interests' => $visitor?->interests ?? [],
            'profession' => $visitor?->profession ?? '',
            'city' => $visitor?->city ?? '',
            'hobby' => $visitor?->hobby ?? '',
            'schedule_count' => 0,
            'tickets_count' => 0,
            'favorites_count' => 0,
        ];
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'visitor',
        ])) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الدخول غير صحيحة، البريد الإلكتروني أو كلمة المرور خاطئة.',
            ], 401);
        }

        $user = Auth::user();
        if (!$user->is_verified) {
            Auth::logout();
            return response()->json([
                'status' => false,
                'message' => 'يرجى التحقق من البريد الإلكتروني قبل تسجيل الدخول.',
            ], 403);
        }
        $visitor = $user->visitor;

        $ticketsCount = 0;
        $scheduleCount = 0;
        $favoritesCount = 0;

        if ($visitor) {
            $ticketsCount = ($visitor->tickets()->count() ?? 0) +
                ($visitor->eventTickets()->count() ?? 0) +
                ($visitor->sponsorEventTickets()->count() ?? 0);

            $scheduleCount = $visitor->schedule()->count();
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

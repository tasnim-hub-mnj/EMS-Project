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

class VisitorController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
        ]);

        $otpCode = (string) rand(1000, 9999);


        DB::transaction(function () use ($validated, $otpCode) {
            $user = User::create([
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'visitor',
                'status' => 'pending',

            ]);

            Visitor::create([
                'user_id' => $user->id,
                'first_name' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'profession' => '',
                'city' => '',
                'hobby' => '',
                'interests' => [],
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Registration successful. Please verify your OTP.',
            'otp' => $otpCode
        ], 201);
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
            $ticketsCount = $visitor->tickets()->count() +
                $visitor->eventTickets()->count() +
                $visitor->sponsorEventTickets()->count();

            $scheduleCount = $visitor->schedules ? $visitor->schedules()->count() : 0;
            $favoritesCount = $visitor->favorites ? $visitor->favorites()->count() : 0;
        }
        $token = $user->createToken('visitor_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $visitor ? $visitor->first_name : '',
                'last_name' => $visitor ? $visitor->last_name : '',
                'email' => $user->email,
                'phone' => $visitor ? $visitor->phone : '',
                'avatar' => $visitor ? $visitor->avatar : null,
                'interests' => $visitor ? $visitor->interests : [],
                'profession' => $visitor ? $visitor->profession : '',
                'city' => $visitor ? $visitor->city : '',
                'hobby' => $visitor ? $visitor->hobby : '',
                'preferred_lang' => $visitor ? $visitor->preferred_lang : 'ar',
                'schedule_count' => $scheduleCount,
                'tickets_count' => $ticketsCount,
                'favorites_count' => $favoritesCount,
            ]
        ], 200);
    }
    //=================================================================

    public function visitorReviews($visitor_id)
    {
        $visitor = Visitor::find($visitor_id);

        if (!$visitor) {
            return response()->json([
                'message' => 'الزائر غير موجود'
            ], 404);
        }

        $exhibitionReviews = ExhibitionReview::with('exhibition')
            ->where('visitor_id', $visitor_id)
            ->orderBy('created_at', 'desc')
            ->get();

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

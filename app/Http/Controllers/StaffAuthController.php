<?php

namespace App\Http\Controllers;

use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\OtpCode;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StaffAuthController extends Controller
{
    public function setup(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $link = PortalLink::with('staff.user')
            ->where('token', $data['token'])
            ->where('active', true)
            ->first();

        if (!$link || !$link->staff || !$link->staff->user || !$link->staff_email
            || strtolower($link->staff_email) !== strtolower($data['email'])) {
            return response()->json(['message' => 'بيانات الموظف أو الرابط غير صحيحة.'], 422);
        }

        if ($link->staff->user->password && $link->staff->user->is_verified) {
            return response()->json([
                'message' => 'تم إنشاء حساب الموظف مسبقًا. استخدم تسجيل الدخول.',
            ], 409);
        }

        if ($link->staff->user->password && !$link->staff->user->is_verified) {
            $link->staff->user->update(['email' => strtolower($data['email'])]);
            $otp = $this->createOtp($link->staff->user);
            Mail::to($link->staff->user->email)->sendNow(new VerificationCodeMail($otp));
            return response()->json([
                'requiresOtp' => true,
                'email' => $link->staff->user->email,
            ], 201);
        }

        try {
            return DB::transaction(function () use ($link, $data) {
                $user = $link->staff->user;
                $user->update([
                    'email' => strtolower($data['email']),
                    'password' => Hash::make($data['password']),
                    'role' => 'staff',
                    'status' => 'pending',
                    'is_verified' => false,
                ]);

                $otp = $this->createOtp($user);
                Mail::to($user->email)->sendNow(new VerificationCodeMail($otp));

                return response()->json([
                    'requiresOtp' => true,
                    'email' => $user->email,
                ], 201);
            });
        } catch (Throwable $exception) {
            Log::error('Staff account setup failed', [
                'portal_link_id' => $link->id,
                'staff_id' => $link->staff_id,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'تعذر تفعيل حساب الموظف حاليًا. تأكد من إعداد Firebase ثم أعد المحاولة.',
            ], 503);
        }
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $link = PortalLink::with('staff.user')
            ->where('token', $data['token'])
            ->where('active', true)
            ->first();
        $user = $link?->staff?->user;

        if (!$link || !$user || !$link->staff_email || strtolower($link->staff_email) !== strtolower($data['email'])) {
            return response()->json(['message' => 'بيانات الموظف أو الرابط غير صحيحة.'], 422);
        }

        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $data['otp'])
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otp) return response()->json(['message' => 'رمز التحقق غير صحيح.'], 422);
        if ($otp->expires_at && now()->greaterThan($otp->expires_at)) {
            return response()->json(['message' => 'انتهت صلاحية رمز التحقق.'], 422);
        }

        $otp->update(['is_used' => true]);
        $user->update(['is_verified' => true, 'status' => 'approved']);

        return response()->json($this->authenticatedResponse($user));
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $link = PortalLink::with('staff.user')
            ->where('token', $data['token'])
            ->where('active', true)
            ->first();
        $user = $link?->staff?->user;

        if (!$link || !$user || !$link->staff_email || strtolower($link->staff_email) !== strtolower($data['email'])) {
            return response()->json(['message' => 'بيانات الموظف أو الرابط غير صحيحة.'], 422);
        }

        $otp = $this->createOtp($user);
        Mail::to($user->email)->sendNow(new VerificationCodeMail($otp));

        return response()->json(['message' => 'تمت إعادة إرسال رمز التحقق.']);
    }

    private function createOtp(User $user): OtpCode
    {
        OtpCode::where('user_id', $user->id)->delete();
        do {
            $code = (string) random_int(100000, 999999);
        } while (OtpCode::where('code', $code)->exists());

        return OtpCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($data['email'])])
            ->where('role', 'staff')
            ->first();

        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 401);
        }

        if (!$user->is_verified || $user->status !== 'approved') {
            return response()->json(['message' => 'تحقق من بريدك الإلكتروني أولًا.'], 403);
        }

        return $this->authenticatedResponse($user);
    }

    public function status(Request $request)
    {
        $token = $request->validate(['token' => ['required', 'string']])['token'];
        $link = PortalLink::with('staff.user')->where('token', $token)->where('active', true)->first();

        return response()->json([
            'exists' => (bool) ($link?->staff?->user?->password && $link?->staff?->user?->is_verified),
            'email' => $link?->staff_email,
        ]);
    }

    public function chatToken(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'staff' || $user->status !== 'approved') {
            return response()->json(['message' => 'لا يملك المستخدم صلاحية المحادثات.'], 403);
        }

        return response()->json($this->firebaseToken($user));
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);
        $user = $request->user();

        if (!$user || $user->role !== 'staff' || !Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح.']);
    }

    private function authenticatedResponse(User $user): array
    {
        $token = $user->createToken('staff_token')->plainTextToken;

        return [
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'email' => $user->email,
                'role' => 'staff',
                'name' => $user->staff?->name ?? $user->email,
            ],
            'firebase' => $this->firebaseToken($user),
        ];
    }

    private function firebaseToken(User $user): array
    {
        $uid = 'staff:' . $user->id;
        $staff = StaffMember::where('user_id', $user->id)->get(['id', 'exhibition_id', 'team']);
        $exhibitions = $staff->pluck('exhibition_id')->map(fn ($id) => (string) $id)->values();
        $portalTokens = PortalLink::whereIn('staff_id', $staff->pluck('id'))
            ->where('active', true)
            ->pluck('token')
            ->values()
            ->all();
        $portalChannels = PortalLink::whereIn('staff_id', $staff->pluck('id'))
            ->where('active', true)
            ->get(['messaging_channels'])
            ->flatMap(fn ($link) => $link->messaging_channels ?? [])
            ->unique()
            ->values()
            ->all();

        $channelTypes = collect($portalChannels)->flatMap(fn ($channel) => match ($channel) {
            'visitors-complaints' => ['portal-visitors', 'portal-complaints'],
            'investors-companies' => ['portal-investors'],
            'team-admin' => ['portal-admin-team'],
            'team-org' => ['portal-org-team'],
            default => [],
        })->unique()->values()->all();

        $customToken = app('firebase.auth')->createCustomToken($uid, [
            'principal_type' => 'staff',
            'principal_id' => (string) $user->id,
            'portal_tokens' => $portalTokens,
            'portal_conversation_types' => $channelTypes,
        ]);

        return [
            'token' => $customToken->toString(),
            'uid' => $uid,
            'exhibitions' => $exhibitions,
            'canMessage' => $staff->isNotEmpty(),
            'conversationTypes' => $channelTypes,
        ];
    }
}

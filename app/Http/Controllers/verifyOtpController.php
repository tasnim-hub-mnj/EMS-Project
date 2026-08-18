<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\VerificationCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class verifyOtpController extends Controller
{
    public function verifyOtp(Request $request)//التحقق من ملكية الايميل//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp)
        {
            return response()->json(['message' => 'OTP not found'], 404);
        }

        if ($otp->expires_at && now()->greaterThan($otp->expires_at))
        {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        $user = User::where('id', $otp->user_id)->first();

        $otp->update(['is_used' => true]);
        $user->update([
            'is_verified' => true,
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'OTP verified successfully',
            'user' => $user
        ], 200);
    }
    //================================================================
    public function resendOtp(Request $request)//اعادة ارسال كود التحقق//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        OtpCode::where('user_id', $user->id)->delete();

        $newOtp = rand(100000, 999999);
        while (OtpCode::where('code', $newOtp)->exists())
        {
            $newOtp = rand(100000, 999999);
        }

        $otp = OtpCode::create([
            'user_id' => $user->id,
            // 'email' => $user->email,
            'code' => $newOtp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        Mail::to($user->email)->queue(new VerificationCodeMail($otp));

        return response()->json([
            'message' => 'OTP resent successfully',
            // 'otp'=>$otp
        ], 200);
    }
    //================================================================
    public function forgotPassword1(Request $request)//1//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        OtpCode::where('user_id', $user->id)->delete();


        $code = rand(100000, 999999);
        while (OtpCode::where('code', $code)->exists())
        {
            $code = rand(100000, 999999);
        }

        $otp = OtpCode::create([
            'user_id' => $user->id,
            // 'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        Mail::to($user->email)->queue(new VerificationCodeMail($otp));

        return response()->json([
            'message' => 'Verification code sent to your email.',
            // 'otp'=>$otp
        ], 200);
    }
    //================================================================
    public function forgotPassword2(Request $request)//2-التحقق من الكود//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp)
        {
            return response()->json(['message' => 'OTP not found'], 404);
        }

        if ($otp->expires_at && now()->greaterThan($otp->expires_at))
        {
            return response()->json([
                'message' => 'OTP expired'
            ], 400);
        }

        $otp->update(['is_used' => true]);

        return response()->json([
            'message' => 'OTP verified successfully'
        ], 200);
    }
    //================================================================
    public function resetPassword(Request $request)//3//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('is_used', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp)
        {
            return response()->json(['message' => 'OTP not found'], 404);
        }

        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.'
        ], 200);
    }
    //================================================================
    public function updatePassword(Request $request)//✅
    {
        $user = Auth::user();
        $request->validate([
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password))
        {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.'
        ], 200);

    }
    //================================================================
    public function saveFcmToken(Request $request)//✅
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();

        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'FCM token saved successfully.',
            'fcm_token' => $user->fcm_token
        ], 200);
    }
    //================================================================
    public function logout(Request $request)//✅
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }
    //================================================================
    public function deleteAccount(Request $request)//✅
    {
        $user = Auth::user();

        if (!$user)
        {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $user->delete();

        if ($request->user()->currentAccessToken())
        {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Account deleted successfully'
        ], 200);
    }
    //================================================================
    public function resetPassword2(Request $request)//o✅
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        //التحقق من صة الكود و مدة
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $request->token)
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp)
        {
            return response()->json(['message' => 'OTP not found'], 404);
        }

        if ($otp->expires_at && now()->greaterThan($otp->expires_at))
        {
            return response()->json([
                'message' => 'OTP expired'
            ], 400);
        }

        $otp->update(['is_used' => true]);

        //اعادة التعيين
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.'
        ], 200);
    }
    //================================================================
    //================================================================
    //================================================================
    //================================================================
    public function reviewStatus($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'not_found'
            ], 404);
        }

        return response()->json([
            'status' => $user->status   // pending | approved | rejected
        ], 200);
    }
    //================================================================
    public function approve($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // تحديث حالة المستخدم
        $user->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User approved successfully'
        ], 200);
    }
    //================================================================

}

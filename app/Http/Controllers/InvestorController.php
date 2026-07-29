<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorRegisterRequest;
use App\Http\Requests\InvestorRequest;
use App\Http\Requests\UpdateInvestorProfileRequest;
use App\Mail\VerificationCodeMail;
use App\Models\Investor;
use App\Models\OtpCode;
use App\Models\SocialLink;
use App\Models\User;
use App\Models\VerifyOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvestorController extends Controller
{
    public function register(InvestorRegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'investor',
            'status' => 'pending',
            // 'fcm_token'=> $data['fcm_token'],
        ]);

        $investor_data =
        [
            'user_id' => $user->id,
            'company_name' => $data['company_name'],
            'trade_name' => $data['trade_name'],
            'location' => $data['location'],
            'website' => $data['website'],
            'activity_type' => $data['activity_type'],

        ];

        $investor = Investor::create($investor_data);

        $data=
        [
            'id'     => $user->id,
            'company_name'=> $investor->company_name,
            'email'  => $user->email,
            'phone' => $user->phone,
            'trade_name' => $investor->trade_name,
            'location' => $investor->location,
            'website' => $investor->website,
            'avatar_url'=> $investor->logo,
        ];

        //----------------------------------
        $newOtp = rand(100000, 999999);
        while (OtpCode::where('code', $newOtp)->exists())
        {
            $newOtp = rand(100000, 999999);
        }
        OtpCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $newOtp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);
        Mail::to($user->email)->send(new VerificationCodeMail($newOtp));
        //----------------------------------

        return response()->json([
            'message' => 'Investor registered successfully',
            'data' => $data,
            // 'user' => $user,
            // 'investor' => $investor,
        ], 201);
    }
    //================================================================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->role !== 'investor')
        {
            return response()->json([
                'message' => 'This account is not an investor'
            ], 403);
        }

        if (!Hash::check($request->password, $user->password))
        {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        $investor = $user->investor;

        if ($user->status === 'pending')
        {
            return response()->json([
                'message' => 'Your account is pending review'
            ], 403);
        }

        $token = $user->createToken('investor_token')->plainTextToken;

        $data=
        [
            'token' =>$token,
            'id'     => $user->id,
            'email'  => $user->email,
            'company_name'=> $investor->company_name,
            'avatar_url'=> $investor->logo,
        ];

        return response()->json([
            'message'  => 'Login successful',
            'data' => $data,
            // 'token'    => $token,
            // 'user'     => $user,
            // 'investor' => $investor,
            // 'social_links'=> $investor->socialLinks,
        ], 200);
    }
    //================================================================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }

    //================================================================
    public function verifyOtp(Request $request)//التحقق من ملكية الايميل
    {
        $request->validate([
            'otp' => 'required|string'
        ]);

        $user = Auth::user();

        // جلب آخر OTP للمستخدم
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

        if ($request->otp !== $otp->code)
        {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        $otp->update(['is_used' => true]);
        $user->update(['is_verified' => true]);

        return response()->json([
            'message' => 'OTP verified successfully',
            'user' => $user
        ], 200);
    }
    //================================================================
    public function resendOtp()//اعادة ارسال كود التحقق
    {
        $user = Auth::user();

        $newOtp = rand(100000, 999999);
        while (OtpCode::where('code', $newOtp)->exists())
        {
            $newOtp = rand(100000, 999999);
        }

        OtpCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $newOtp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($newOtp));

        return response()->json([
            'message' => 'OTP resent successfully',
        ], 200);
    }
    //================================================================
    public function forgotPassword(Request $request)
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

        OtpCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($code));

        return response()->json([
            'message' => 'Verification code sent to your email.'
        ], 200);
    }

    //================================================================
    public function resetPassword(Request $request)
    {
        $request->validate([
            // 'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

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

        if ($request->otp !== $otp->code)
        {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $otp->update(['is_used' => true]);

        return response()->json([
            'message' => 'Password changed successfully.'
        ], 200);
    }
    //================================================================
    public function updatePassword(Request $request)
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
    public function saveFcmToken(Request $request)
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
    public function getPorfile()
    {
        $user=Auth::user();
        $investor=$user->investor;
        return response()->json([
            'user'=>$user,
            'investor' =>$investor,
            'social_links'=> $investor->socialLinks,
        ], 200);
    }
    //================================================================
    public function UpdatePorfile(UpdateInvestorProfileRequest $request)
    {
        $user = Auth::user();
        $investor = $user->investor;

        $user->update($request->only(['email','phone']));

        if ($request->hasFile('logo'))
        {
            if ($investor->logo)
            {
                Storage::disk('public')->delete($investor->logo);
            }
            $path = $request->file('logo')->store('investor_logo', 'public');
            $investor->logo = $path;
            $investor->update(['logo' => $path]);
        }

        $investor->update($request->only(['bio','location','website']));

        // تحديث الروابط
        /*
        الروابط التي فيها id → يتم تعديلها
        ✔ الروابط التي بدون id → يتم إنشاؤها
        ✔ الروابط التي لم تُرسل → يتم حذفها
        */
        if ($request->filled('links'))
        {
            $newLinks = collect($request->links);
            // 1) حذف الروابط التي لم يتم إرسالها
            $investor->socialLinks()
                ->whereNotIn('id', $newLinks->pluck('id')->filter())
                ->delete();

            // 2) تعديل أو إضافة الروابط
            foreach ($newLinks as $item)
            {
                // تعديل رابط موجود
                if (isset($item['id']))
                {
                    SocialLink::where('id', $item['id'])
                        ->update([
                            'link' => $item['link'],
                            'type' => $item['type'],
                        ]);
                }
                else// إضافة رابط جديد
                {
                    SocialLink::create([
                        'investor_id' => $investor->id,
                        'link' => $item['link'],
                        'type' => $item['type'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Updated profile',
            'user' => $user,
            'investor' => $investor,
            'social_links' => $investor->socialLinks,
        ], 200);
    }
    //================================================================
    public function deleteAccount(Request $request)
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
    //================================================================


}

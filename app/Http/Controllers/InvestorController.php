<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorRegisterRequest;
use App\Http\Requests\UpdateInvestorProfileRequest;
use App\Mail\VerificationCodeMail;
use App\Models\Investor;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvestorController extends Controller
{
    public function register(InvestorRegisterRequest $request)//✅
    {
        $data = $request->validated();
        $user = User::create([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'investor',
        ]);

        //----------------------------------
        $newOtp = rand(100000, 999999);
        while (OtpCode::where('code', $newOtp)->exists())
        {
            $newOtp = rand(100000, 999999);
        }
        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $newOtp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);
        Mail::to($user->email)->sendNow(new VerificationCodeMail($otp));
        //----------------------------------

        $investor_data =
            [
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'trade_name' => $data['trade_name'],
                'location' => $data['location'],
                'website' => $data['website'] ?? null,
                'activity_type' => $data['activity_type'],
            ];

        $investor = Investor::create($investor_data);

        $data =
            [
                'id' => $user->id,
                'email' => $user->email,
                'company_name' => $investor->company_name,
                'avatar_url' => $investor->logo,
            ];

        return response()->json([
            'message' => 'Investor registered successfully',
            'data' => $data,
        ], 201);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'investor')
            ->firstOrFail();

        OtpCode::where('user_id', $user->id)->delete();

        do {
            $newOtp = rand(100000, 999999);
        } while (OtpCode::where('code', $newOtp)->exists());

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $newOtp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        Mail::to($user->email)->sendNow(new VerificationCodeMail($otp));

        return response()->json([
            'message' => 'OTP resent successfully',
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'investor')
            ->firstOrFail();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('is_used', false)
            ->orderByDesc('created_at')
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'OTP not found'], 404);
        }

        if ($otp->expires_at && now()->greaterThan($otp->expires_at)) {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        $otp->update(['is_used' => true]);
        $user->update([
            'is_verified' => true,
            'status' => 'pending',
        ]);

        $investor = $user->investor;
        $token = $user->createToken('investor_token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully',
            'data' => [
                'token' => $token,
                'id' => $user->id,
                'email' => $user->email,
                'company_name' => $investor?->company_name,
                'avatar_url' => $investor?->logo,
            ],
        ], 200);
    }
    //================================================================
    public function login(Request $request)//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
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

        $data =
            [
                'token' => $token,
                'id' => $user->id,
                'email' => $user->email,
                'company_name' => $investor->company_name,
                'avatar_url' => $investor->logo,
            ];

        return response()->json([
            'message' => 'Login successful',
            'data' => $data,
        ], 200);
    }
    //================================================================
    public function getProfile()//✅
    {
        $user = Auth::user();
        $investor = $user->investor;

        $social =
            [
                'linkedin' => optional($investor->socialLinks()->where('type', 'linkedin')->first())->link,
                'twitter' => optional($investor->socialLinks()->where('type', 'twitter')->first())->link,
                'instagram' => optional($investor->socialLinks()->where('type', 'instagram')->first())->link,
                'facebook' => optional($investor->socialLinks()->where('type', 'facebook')->first())->link,
            ];

        return response()->json([
            'data' =>
                [
                    'id' => $user->id,
                    'name' => $investor->company_name,
                    'email' => $user->email,
                    'company_name' => $investor->company_name,
                    'avatar_url' => $investor->logo ? asset('storage/' . $investor->logo) : null,
                    'location' => $investor->location,
                    'phone' => $user->phone,
                    'website' => $investor->website,
                    'bio' => $investor->bio,
                    'social' => $social,
                ]
        ], 200);
    }

    public function getMap(int $id)
    {
        $map = \App\Models\Map::where('exhibition_id', $id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (!$map) {
            return response()->json([
                'status' => false,
                'message' => 'Published map not found for this exhibition',
            ], 404);
        }

        $mapData = is_array($map->map_json) ? $map->map_json : [];
        $mapData['map_id'] = (int) $map->id;
        $mapData['exhibition_id'] = $id;
        $mapData['exhibition_name'] = $map->exhibition?->name ?? '';

        return response()->json([
            'status' => true,
            'data' => $mapData,
        ], 200);
    }
    //================================================================
    public function updateProfile(UpdateInvestorProfileRequest $request)//✅
    {
        $user = Auth::user();
        $investor = $user->investor;

        $user->update([
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $investor->update([
            'company_name' => $request->company_name,
            'location' => $request->location,
            'website' => $request->website,
            'bio' => $request->bio,
        ]);

        if ($request->filled('social'))
        {
            $social = $request->social;
            $types = ['linkedin', 'twitter', 'instagram', 'facebook'];

            foreach ($types as $type)
            {
                $linkValue = $social[$type] ?? null;
                if ($linkValue)
                {
                    $investor->socialLinks()->updateOrCreate(
                        ['type' => $type],
                        ['link' => $linkValue]
                    );
                } else
                {
                    $investor->socialLinks()->where('type', $type)->delete();
                }
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $this->profileData()
        ], 200);
    }
    //================================================================
    public function uploadAvatar(Request $request)//✅
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096'
        ]);

        $user = Auth::user();
        $investor = $user->investor;

        // حذف الصورة القديمة
        if ($investor->logo)
        {
            Storage::disk('public')->delete($investor->logo);
        }

        // رفع الصورة الجديدة
        $path = $request->file('avatar')->store('investor_avatar', 'public');

        $investor->update([
            'logo' => $path
        ]);

        return response()->json([
            'message' => 'Avatar updated successfully',
            'avatar_url' => asset('storage/' . $path)
        ], 200);
    }
    //================================================================
    private function profileData()//↕️
    {
        $user = Auth::user();
        $investor = $user->investor;

        return
            [
                'id' => $user->id,
                'name' => $investor->company_name,
                'email' => $user->email,
                'company_name' => $investor->company_name,
                'avatar_url' => $investor->logo ? asset('storage/' . $investor->logo) : null,
                'location' => $investor->location,
                'phone' => $user->phone,
                'website' => $investor->website,
                'bio' => $investor->bio,
                'social' => [
                    'linkedin' => optional($investor->socialLinks()->where('type', 'linkedin')->first())->link,
                    'twitter' => optional($investor->socialLinks()->where('type', 'twitter')->first())->link,
                    'instagram' => optional($investor->socialLinks()->where('type', 'instagram')->first())->link,
                    'facebook' => optional($investor->socialLinks()->where('type', 'facebook')->first())->link,
                ]
            ];
    }
    //================================================================
    //================================================================



}

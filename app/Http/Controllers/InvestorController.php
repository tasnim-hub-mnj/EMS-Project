<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorRegisterRequest;
use App\Http\Requests\InvestorRequest;
use App\Http\Requests\UpdateInvestorProfileRequest;
use App\Models\Investor;
use App\Models\SocialLink;
use App\Models\User;
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
            'token_fcm'=> $data['token_fcm'],
        ]);

        $investor_data =
        [
            'user_id' => $user->id,
            'company_name' => $data['company_name'],
            'trade_name' => $data['trade_name'],
            'location' => $data['location'],
            'website' => $data['website'],
            'activity_type' => $data['activity_type'],
            'terms_accepted' => $data['terms_accepted'],
            // 'bio' => $data['bio'],
        ];

        // if ($request->hasFile('logo'))
        // {
        //     $path = $request->file('logo')->store('investor_logo', 'public');
        //     $investor_data['logo'] = $path;
        // }

        $investor = Investor::create($investor_data);

        // if ($request->filled('links'))
        // {
        //     foreach ($request->links as $item)
        //     {
        //         SocialLink::create([
        //             'investor_id' => $investor->id,
        //             'link' => $item['link'],
        //             'type' => $item['type'],
        //         ]);
        //     }
        // }

        return response()->json([
            'message' => 'Investor registered successfully',
            'user' => $user,
            'investor' => $investor,
            // 'social_links' => $investor->socialLinks,
        ], 201);
    }
    //================================================================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
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

        return response()->json([
            'message'  => 'Login successful',
            'token'    => $token,
            'user'     => $user,
            'investor' => $investor,
            'social_links'=> $investor->socialLinks,
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
    // public function forgotPassword(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:users,email',
    //     ]);

    //     // إنشاء كود 6 أرقام
    //     $code = rand(100000, 999999);

    //     // حذف أي أكواد قديمة لنفس الإيميل
    //     DB::table('password_reset_codes')->where('email', $request->email)->delete();

    //     // حفظ الكود
    //     DB::table('password_reset_codes')->insert([
    //         'email'      => $request->email,
    //         'code'       => $code,
    //         'expires_at' => now()->addMinutes(10),
    //         'created_at' => now(),
    //     ]);

    //     // إرسال الكود إلى الإيميل
    //     Mail::raw("Your verification code is: $code", function ($message) use ($request) {
    //         $message->to($request->email)
    //                 ->subject('Password Reset Code');
    //     });

    //     return response()->json([
    //         'message' => 'Verification code sent to your email.'
    //     ], 200);
    // }
    // //================================================================
    // public function resetPassword(Request $request)
    // {
    //     $request->validate([
    //         'email'       => 'required|email|exists:users,email',
    //         'newpassword'    => 'required|string|min:6|confirmed',
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     $user->update([
    //         'password' => Hash::make($request->newpassword),
    //     ]);

    //     return response()->json([
    //         'message' => 'Password changed successfully'
    //     ], 200);
    // }

}

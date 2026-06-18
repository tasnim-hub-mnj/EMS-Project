<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorRequest;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvestorController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'company_name'   => 'required|string|max:200',
            'trade_name'     => 'nullable|string|max:200',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|unique:users,phone',
            'website'        => 'nullable|url',
            'activity_type'  => 'required|string|max:200',
            'password'       => 'required|string|min:6|confirmed',
            'terms_accepted' => 'required|boolean|in:1',
        ]);

        $user = User::create([
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'investor',
        ]);

        $investor = Investor::create([
            'user_id'        => $user->id,
            'company_name'   => $request->company_name,
            'trade_name'     => $request->trade_name,
            'website'        => $request->website,
            'activity_type'  => $request->activity_type,
            'terms_accepted' => true,
            'status'         => 'pending',
        ]);

        return response()->json([
            'message'  => 'Investor registered successfully',
            'user'     => $user,
            'investor' => $investor,
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
            return response()->json(['message' => 'This account is not an investor'], 403);
        }

        if (!Hash::check($request->password, $user->password))
        {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        $investor = $user->investor;

        if ($investor->status === 'pending')
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
        ], 200);
    }
    //================================================================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful'], 200);
    }
    //================================================================
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // إنشاء كود 6 أرقام
        $code = rand(100000, 999999);

        // حذف أي أكواد قديمة لنفس الإيميل
        DB::table('password_reset_codes')->where('email', $request->email)->delete();

        // حفظ الكود
        DB::table('password_reset_codes')->insert([
            'email'      => $request->email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // إرسال الكود إلى الإيميل
        Mail::raw("Your verification code is: $code", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Password Reset Code');
        });

        return response()->json([
            'message' => 'Verification code sent to your email.'
        ], 200);
    }
    //================================================================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'       => 'required|email|exists:users,email',
            'newpassword'    => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => Hash::make($request->newpassword),
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }


}

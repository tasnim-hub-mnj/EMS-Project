<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function register(RegisterRequest $request)//تسجيل مستخدم
    {
        $user=User::create(
            [
                'email'=>$request->email,
                'phone'=>$request->phone,
                'password'=>Hash::make($request->password),
                'role'=>$request->role,
            ]);

        return response()->json([
            'message'=>'User Register Successfully',
            'User'=>$user,
        ],201);
    }
//__________________________________________________________________________
    public function login(Request $request)//تسجيل دخول
    {
        $request->validate([
            'email'    => 'nullable|email|required_without:phone|prohibited_with:phone',
            'phone'    => 'nullable|required_without:email|prohibited_with:email',
            'password' => 'required|string',
        ]);
        // تحديد طريقة تسجيل الدخول
        if ($request->email) {
            $user = User::where('email', $request->email)->first();
        } else
        {
            $user = User::where('phone', $request->phone)->first();
        }

        // التحقق من وجود المستخدم
        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        // التحقق من كلمة المرور
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }

        $token=$user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message'=>'User Logged In Successfully',
            'Token'=>$token,
            'User'=>$user,
        ], 200);
    }
//__________________________________________________________________________
    public function logout(Request $request)//تسجيل خروج
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message'=>'User Successfully Log Out'
        ],200);
    }
//__________________________________________________________________________

}


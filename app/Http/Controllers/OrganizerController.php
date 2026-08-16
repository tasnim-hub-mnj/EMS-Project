<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizerRegisterRequest;
use App\Mail\VerificationCodeMail;
use App\Models\Organizer;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OrganizerController extends Controller
{
    public function register(OrganizerRegisterRequest $request)//✅
    {
        $data = $request->validated();
        $user = User::create([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'organizer',
            'status' => 'pending',
            'fcm_token' => $data['fcm_token'],
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
        Mail::to($user->email)->queue(new VerificationCodeMail($otp));
        //----------------------------------

        $organizer_data =
        [
            'user_id' => $user->id,
            'company_name' => $data['company_name'],
            'category' => $data['category'],
            'headquarters' => $data['headquarters'],
            'reg_number' => $data['registration_number'],
            'location' => $data['exhibition_location'],
            'description' => $data['description'],
        ];

        $organizer = Organizer::create($organizer_data);

        // $data =
        //     [
        //         'id' => $user->id,
        //         'email' => $user->email,
        //         'company_name' => $organizer->company_name,
        //     ];

        return response()->json([
            'status' => $user->status,
            'userId' => $user->id
        ], 201);

    }
    //================================================================
    public function login(Request $request)//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->role !== 'organizer')
        {
            return response()->json([
                'message' => 'This account is not an organizer'
            ], 403);
        }

        if (!Hash::check($request->password, $user->password))
        {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        $organizer = $user->organizer;

        if ($user->status === 'pending')
        {
            return response()->json([
                'message' => 'Your account is pending review'
            ], 403);
        }

        $token = $user->createToken('organizer_token')->plainTextToken;

        // $data =
        //     [
        //         'token' => $token,
        //         'id' => $user->id,
        //         'email' => $user->email,
        //         'company_name' => $organizer->company_name,
        //     ];

        // return response()->json([
        //     'message' => 'Login successful',
        //     'data' => $data,
        // ], 200);
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $organizer->company_name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => 
                [
                    "exhibitions:read",
                    "exhibitions:write",
                    "staff:read",
                    "staff:write",
                    "map:write",
                    "map:publish"
                ]
            ]
        ], 200);
    }
    //================================================================
    public function updateProfile(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $user=Auth::user();
        $user->update([
            'phone' => $request->phone,
        ]);

        // return response()->json([
        //     'message' => 'Profile updated successfully',
        //     'user' => $user->phone
        // ], 200);
        return response()->json([
            'success' => true,
            'message' => 'Operation completed successfully'
        ], 200);

    }
    //================================================================
    public function updateCompany(Request $request)
    {
        $request->validate([
            'company_name'   => 'nullable|string|max:200',
            'category'     => 'nullable|json',
            'headquarters'       => 'nullable|string|max:200',
            'registration_number'        => 'nullable|string|max:200',
            'exhibition_location'        => 'nullable|string|max:200',
            'description'      => 'nullable|string|max:500',
        ]);

        $user=Auth::user();
        $organizer=$user->organizer;
        $organizer->update([
            'company_name'   => $request->company_name,
            'category'   => $request->category,
            'headquarters'   => $request->headquarters,
            'reg_number'   => $request->registration_number,
            'location'   => $request->exhibition_location,
            'description'   => $request->description,
        ]);

        // return response()->json([
        //     'message' => 'Company Profile updated successfully',
        //     'organizer' => $organizer
        // ], 200);
        return response()->json([
            'success' => true,
            'message' => 'Operation completed successfully'
        ], 200);

    }
    //================================================================
    public function getPorfile()
    {
        $user=Auth::user();
        $organizer=$user->organizer;

        // return response()->json([
        //     'data' =>
        //         [
        //             'id' => $user->id,
        //             'name' => $organizer->company_name,
        //             'email' => $user->email,
        //             'company_name' => $organizer->company_name,
        //             'exhibition_location' => $organizer->location,
        //             'phone' => $user->phone,
        //             'category'   => $organizer->category,
        //             'headquarters'   => $organizer->headquarters,
        //             'registration_number'   => $organizer->reg_number,
        //         ]
        // ], 200);
        
        return response()->json([
            'id' => $user->id,
            'name' => $organizer->company_name,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => [
                "exhibitions:read",
                "exhibitions:write",
                "staff:read",
                "staff:write",
                "map:write",
                "map:publish"
            ]
        ], 200);

    }
    //================================================================
    //================================================================

}

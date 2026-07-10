<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizerRegisterRequest;
use App\Http\Requests\UpdateOrganizeProfileRequest;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class OrganizerController extends Controller
{
    public function register(OrganizerRegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'organizer',
            'status' => 'pending',
            'token_fcm'=> $data['token_fcm'],
        ]);

        $organizer_data =
        [
            'user_id' => $user->id,
            'company_name' => $data['company_name'],
            'category' => $data['category'],
            'headquarters' => $data['headquarters'],
            'reg_number' => $data['reg_number'],
            'location' => $data['location'],
            'description' => $data['description'],
        ];

        $pathFile = $request->file('file')->store('organizer_files', 'public');
        $organizer_data['file'] = $pathFile;

        if ($request->hasFile('logo'))
        {
            $path = $request->file('logo')->store('organizer_logo', 'public');
            $organizer_data['logo'] = $path;
        }

        $organizer = Organizer::create($organizer_data);

        return response()->json([
            'message' => 'Organizer registered successfully',
            'user' => $user,
            'organizer' => $organizer,
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

        return response()->json([
            'message'  => 'Login successful',
            'token'    => $token,
            'user'     => $user,
            'organizer' => $organizer,
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
        $organizer=$user->organizer;
        return response()->json([
            'user'=>$user,
            'organizer' =>$organizer,
        ], 200);
    }
    //================================================================
    public function UpdatePorfile(UpdateOrganizeProfileRequest $request)
    {
        $user = Auth::user();
        $organizer = $user->organizer;

        $user->update($request->only(['email','phone']));

        if ($request->hasFile('logo'))
        {
            if ($organizer->logo)
            {
                Storage::disk('public')->delete($organizer->logo);
            }
            $path = $request->file('logo')->store('organizer_logo', 'public');
            $organizer->logo = $path;
            $organizer->update(['logo' => $path]);
        }

        $organizer->update($request->only(['company_name']));

        return response()->json([
            'message' => 'Updated profile',
            'user' => $user,
            'organizer' => $organizer,
        ], 200);
    }
    //================================================================
}

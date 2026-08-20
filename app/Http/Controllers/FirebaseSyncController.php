<?php

namespace App\Http\Controllers;

use App\Models\FirebaseSync;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirebaseSyncController extends Controller
{
    public function principalToken(Request $request)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['organizer', 'admin', 'investor', 'visitor'], true)) {
            return response()->json(['message' => 'Firebase principal token is not available.'], 403);
        }

        $uid = 'principal:' . $user->id;
        $token = app('firebase.auth')->createCustomToken($uid, [
            'principal_type' => $user->role,
            'principal_id' => (string) $user->id,
        ]);

        return response()->json([
            'token' => $token->toString(),
            'uid' => $uid,
        ]);
    }

    public function sync(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'firebase_uid' => 'required|string',
            'role' => 'nullable|string|in:admin,investor,visitor,organizer,staff',
        ]);

        $user = Auth::user() ?? User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found for Firebase sync.',
            ], 422);
        }

        $role = $request->role ?? $user->role ?? 'organizer';

        $sync = FirebaseSync::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'firebase_uid' => $request->firebase_uid,
                'role' => $role,
            ]
        );

        app('firebase.auth')->setCustomUserClaims($request->firebase_uid, [
            'principal_type' => $role,
            'principal_id' => (string) $user->id,
        ]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'firebase_uid' => $sync->firebase_uid,
                'role' => $sync->role,
            ],
        ], 200);
    }
}

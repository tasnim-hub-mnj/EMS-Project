<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FirebaseSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirebaseSyncController extends Controller
{
    public function sync(Request $request)
    {
        $request->validate([
            'firebase_uid' => 'required|string',
            'role' => 'required|in:admin,investor,visitor',
        ]);

        $user = Auth::user();

        $sync = FirebaseSync::updateOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'firebase_uid' => $request->firebase_uid,
                'role' => $request->role,
            ]
        );

        return response()->json([
            'success' => true,
            'user' =>
            [
                'id' => $user->id,
                'email' => $user->email,
                'firebase_uid' => $sync->firebase_uid,
                'role' => $sync->role,
            ]
        ], 200);
    }
}

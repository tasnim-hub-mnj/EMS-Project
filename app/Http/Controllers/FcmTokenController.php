<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        FcmToken::updateOrCreate(
            ['user_id' => Auth::id()],
            ['fcm_token' => $request->fcm_token]
        );

        return ['success' => true];
    }
    //===============================================================
}

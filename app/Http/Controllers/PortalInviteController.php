<?php

namespace App\Http\Controllers;

use App\Mail\PortalInviteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PortalInviteController extends Controller
{
    public function send(Request $request)//إرسال إيميل دعوة للبوابة
    {
        $request->validate([
            'staff_id' => 'required',
            'staff_name' => 'required|string|max:255',
            'staff_email' => 'required|email',
            'portal_url' => 'required|url',
            'role' => 'required|string',
            'exhibition_name' => 'required|string|max:255',
        ]);

        
        Mail::to($request->staff_email)->send(new PortalInviteMail($request->portal_url ,$request->staff_name ,$request->exhibition_name));

        return response()->json([
            'success' => true,
            'message' => 'Portal invite email sent successfully'
        ], 200);
    }
    //================================================================
}

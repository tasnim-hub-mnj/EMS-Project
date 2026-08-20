<?php

namespace App\Http\Controllers;

use App\Mail\PortalInviteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\PortalLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function sendFromPortal(Request $request)
    {
        $data = $request->validate([
            'parent_token' => ['required', 'uuid'],
            'portal_token' => ['required', 'uuid'],
            'staff_id' => ['required'],
            'staff_name' => ['required', 'string', 'max:255'],
            'staff_email' => ['required', 'email'],
            'portal_url' => ['required', 'url'],
            'exhibition_name' => ['required', 'string', 'max:255'],
        ]);

        $parent = PortalLink::where('token', $data['parent_token'])
            ->where('active', true)
            ->where('is_manager', true)
            ->whereHas('staff', fn ($query) => $query->where('user_id', Auth::id()))
            ->firstOrFail();

        $child = PortalLink::with('staff.user')->where('token', $data['portal_token'])
            ->where('parent_token', $parent->token)
            ->where('staff_id', $data['staff_id'])
            ->first();

        abort_unless($child, 403, 'رابط الموظف غير مرتبط بهذا المدير.');

        $recipient = $child->staff_email ?: $child->staff?->user?->email;
        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'لا يوجد بريد إلكتروني محفوظ للموظف.'], 422);
        }

        try {
            Mail::to($recipient)->send(new PortalInviteMail(
                $data['portal_url'],
                $child->staff_name,
                $child->exhibition_name
            ));
        } catch (\Throwable $exception) {
            Log::error('Portal child invitation email failed', [
                'portal_link_id' => $child->id,
                'recipient' => $recipient,
                'exception' => $exception,
            ]);
            return response()->json(['success' => false, 'message' => 'تعذر إرسال البريد الإلكتروني للموظف.'], 503);
        }

        return response()->json(['success' => true, 'message' => 'Portal invite email sent successfully']);
    }
    //================================================================
}

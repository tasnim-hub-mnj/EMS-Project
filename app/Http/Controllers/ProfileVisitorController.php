<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileVisitorController extends Controller
{
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $visitor = $user->visitor;
        $totalTickets = 0;
        if ($visitor) {
            $totalTickets = $visitor->tickets()->count() +              // تذاكر المعارض (Ticket)
                $visitor->eventTickets()->count() +         // تذاكر الفعاليات (EventTicket)
                $visitor->sponsorEventTickets()->count();   // تذاكر الفعاليات الرعائية/الإعلانية (SponserEventTicket)
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'first_name' => $visitor ? $visitor->first_name : '',
                'last_name' => $visitor ? $visitor->last_name : '',
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $visitor ? $visitor->avatar_url : null,
                'interests' => $visitor ? ($visitor->interests ?? []) : [],
                'profession' => $visitor ? $visitor->profession : '',
                'city' => $visitor ? $visitor->city : '',
                'hobby' => $visitor ? $visitor->hobby : '',
                'preferred_lang' => $user->preferred_lang ?? 'ar',

                'schedule_count' => $visitor ? $visitor->schedules()->count() : 0,
                'tickets_count' => $totalTickets,
                'favorites_count' => $visitor ? $visitor->favorites()->count() : 0,
            ]
        ], 200);
    }
    //===============================================================
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $visitor = $user->visitor;

        if (!$visitor) {
            return response()->json(['status' => false, 'message' => 'Visitor profile not found'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'hobby' => 'nullable|string|max:255',
            'preferred_lang' => 'nullable|string|in:ar,en',
            'interests' => 'nullable|array',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $updatedData = [];

        if ($request->has('preferred_lang')) {
            $user->update(['preferred_lang' => $validated['preferred_lang']]);
            $updatedData['preferred_lang'] = $validated['preferred_lang'];
        }

        if ($request->hasFile('avatar')) {

            if ($visitor->avatar_url) {
                $oldPath = str_replace(url('storage/'), '', $visitor->avatar_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $visitor->avatar_url = url('storage/' . $path);

            $visitor->save();

            $updatedData['avatar'] = $visitor->avatar_url;
        }
        $visitorFields = ['first_name', 'last_name', 'profession', 'city', 'hobby', 'interests'];

        foreach ($visitorFields as $field) {
            if ($request->has($field)) {
                $visitor->update([$field => $validated[$field]]);
                $updatedData[$field] = $validated[$field];
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $updatedData
        ], 200);
    }
    //================================================================
    ### تابع حذف الحساب نهائياً (Delete Account)
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);
        $user = $request->user();


        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'كلمة المرور غير صحيحة.'
            ], 421);
        }

        $visitor = $user->visitor;
        if ($visitor && $visitor->avatar_url) {
            $oldPath = str_replace(url('storage/'), '', $visitor->avatar_url);
            Storage::disk('public')->delete($oldPath);
        }

        $user->tokens()->delete();

        if ($visitor) {
            $visitor->delete();
        }
        $user->delete();
        return response()->json([
            'status' => true,
            'message' => 'تم حذف الحساب بنجاح وجميع البيانات التابعة له.'
        ], 200);
    }
}

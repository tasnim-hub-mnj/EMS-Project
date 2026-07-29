<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileVisitorController extends Controller
{
    public function getProfile(Request $request)
    {
        $user = $request->user();

        // تحميل علاقة الزائر مع الأعداد تلقائياً
        $visitor = $user->visitor()
            ->withCount(['schedules', 'tickets', 'eventTickets', 'sponsorEventTickets', 'favorites'])
            ->first();

        $totalTickets = 0;
        if ($visitor) {
            $totalTickets = ($visitor->tickets_count ?? 0)
                + ($visitor->event_tickets_count ?? 0)
                + ($visitor->sponsor_event_tickets_count ?? 0);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $visitor ? $visitor->first_name : '',
                'last_name' => $visitor ? $visitor->last_name : '',
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $visitor ? $visitor->avatar_url : null,
                'interests' => $visitor ? ($visitor->interests ?? []) : [],
                'profession' => $visitor ? ($visitor->profession ?? '') : '',
                'city' => $visitor ? $visitor->city : '',
                'hobby' => $visitor ? $visitor->hobby : '',
                'preferred_lang' => $user->preferred_lang ?? 'ar',

                'schedule_count' => $visitor ? ($visitor->schedules_count ?? 0) : 0,
                'tickets_count' => $totalTickets,
                'favorites_count' => $visitor ? ($visitor->favorites_count ?? 0) : 0,
            ]
        ], 200);
    }
    //===============================================================
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $visitor = $user->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'Visitor profile not found'
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'hobby' => 'nullable|string|max:255',
            'preferred_lang' => 'nullable|string|in:ar,en',
            'interests' => 'nullable|array',
            'interests.*' => 'string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->has('preferred_lang')) {
            $user->update(['preferred_lang' => $validated['preferred_lang']]);
        }

        if ($request->hasFile('avatar')) {
            if ($visitor->avatar_url) {
                $path = ltrim(parse_url($visitor->avatar_url, PHP_URL_PATH), '/');
                $oldPath = str_replace('storage/', '', $path);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $visitor->avatar_url = url('storage/' . $path);
        }

        $visitorFields = ['first_name', 'last_name', 'profession', 'city', 'hobby', 'interests'];
        $visitorData = [];

        foreach ($visitorFields as $field) {
            if ($request->has($field)) {
                $visitorData[$field] = $validated[$field];
            }
        }

        if (!empty($visitorData) || $request->hasFile('avatar')) {
            $visitor->fill($visitorData);
            $visitor->save();
        }

        $visitor->loadCount(['schedules', 'tickets', 'eventTickets', 'sponsorEventTickets', 'favorites']);

        $totalTickets = ($visitor->tickets_count ?? 0)
            + ($visitor->event_tickets_count ?? 0)
            + ($visitor->sponsor_event_tickets_count ?? 0);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $visitor->first_name ?? '',
                'last_name' => $visitor->last_name ?? '',
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $visitor->avatar_url,
                'interests' => $visitor->interests ?? [],
                'profession' => $visitor->profession ?? '',
                'city' => $visitor->city ?? '',
                'hobby' => $visitor->hobby ?? '',
                'preferred_lang' => $user->preferred_lang ?? 'ar',

                'schedule_count' => $visitor->schedules_count ?? 0,
                'tickets_count' => $totalTickets,
                'favorites_count' => $visitor->favorites_count ?? 0,
            ]
        ], 200);
    }
    //================================================================
    ### تابع حذف الحساب نهائياً (Delete Account)
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password',
            ], 422);
        }
        DB::transaction(function () use ($user) {
            $visitor = $user->visitor;

            if ($visitor && $visitor->avatar_url) {
                $path = ltrim(parse_url($visitor->avatar_url, PHP_URL_PATH), '/');
                $storagePath = str_replace('storage/', '', $path);

                if (Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->delete($storagePath);
                }
            }

            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Account deleted successfully',
        ], 200);
    }
}

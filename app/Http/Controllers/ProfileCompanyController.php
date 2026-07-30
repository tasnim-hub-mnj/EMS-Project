<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInvestorProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileCompanyController extends Controller
{
    public function getProfile()//✅
    {
        $user = Auth::user();
        $investor = $user->investor;

        $social =
        [
            'linkedin'  => optional($investor->socialLinks()->where('type', 'linkedin')->first())->link,
            'twitter'   => optional($investor->socialLinks()->where('type', 'twitter')->first())->link,
            'instagram' => optional($investor->socialLinks()->where('type', 'instagram')->first())->link,
            'facebook'  => optional($investor->socialLinks()->where('type', 'facebook')->first())->link,
        ];

        return response()->json([
            'data' =>
            [
                'id'            => $user->id,
                'name'          => $investor->company_name,
                'email'         => $user->email,
                'company_name'  => $investor->company_name,
                'avatar_url'    => $investor->logo ? asset('storage/' . $investor->logo) : null,
                'location'      => $investor->location,
                'phone'         => $user->phone,
                'website'       => $investor->website,
                'bio'           => $investor->bio,
                'social'        => $social,
            ]
        ], 200);
    }
    //================================================================
    public function updateProfile(UpdateInvestorProfileRequest $request)//✅
    {
        $user = Auth::user();
        $investor = $user->investor;

        $user->update([
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $investor->update([
            'company_name' => $request->company_name,
            'location'     => $request->location,
            'website'      => $request->website,
            'bio'          => $request->bio,
        ]);

        if ($request->filled('social'))
        {
            $social = $request->social;
            $types = ['linkedin', 'twitter', 'instagram', 'facebook'];

            foreach ($types as $type)
            {
                $linkValue = $social[$type] ?? null;
                if ($linkValue)
                {
                    $investor->socialLinks()->updateOrCreate(
                        ['type' => $type],
                        ['link' => $linkValue]
                    );
                } else
                {
                    $investor->socialLinks()->where('type', $type)->delete();
                }
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $this->profileData()
        ], 200);
    }
    //================================================================
    public function uploadAvatar(Request $request)//✅
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096'
        ]);

        $user = Auth::user();
        $investor = $user->investor;

        // حذف الصورة القديمة
        if ($investor->logo)
        {
            Storage::disk('public')->delete($investor->logo);
        }

        // رفع الصورة الجديدة
        $path = $request->file('avatar')->store('investor_avatar', 'public');

        $investor->update([
            'logo' => $path
        ]);

        return response()->json([
            'message' => 'Avatar updated successfully',
            'avatar_url' => asset('storage/' . $path)
        ], 200);
    }
    //================================================================
    private function profileData()//↕️
    {
        $user = Auth::user();
        $investor = $user->investor;

        return
        [
            'id'            => $user->id,
            'name'          => $investor->company_name,
            'email'         => $user->email,
            'company_name'  => $investor->company_name,
            'avatar_url'    => $investor->logo ? asset('storage/' . $investor->logo) : null,
            'location'      => $investor->location,
            'phone'         => $user->phone,
            'website'       => $investor->website,
            'bio'           => $investor->bio,
            'social'        => [
                'linkedin'  => optional($investor->socialLinks()->where('type', 'linkedin')->first())->link,
                'twitter'   => optional($investor->socialLinks()->where('type', 'twitter')->first())->link,
                'instagram' => optional($investor->socialLinks()->where('type', 'instagram')->first())->link,
                'facebook'  => optional($investor->socialLinks()->where('type', 'facebook')->first())->link,
            ]
        ];
    }
}

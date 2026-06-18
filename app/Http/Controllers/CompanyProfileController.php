<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyProfileRequest;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyProfileController extends Controller
{
    public function store(CompanyProfileRequest $request)
    {
        $investorId = Auth::id();

        // منع إنشاء بروفايل ثاني
        if (CompanyProfile::where('investor_id', $investorId)->exists()) {
            return response()->json(['message' => 'لديك بروفايل مسبقاً'], 409);
        }

        $data = $request->validated();
        $data['investor_id'] = $investorId;

        // رفع الشعار
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company_logos', 'public');
        }

        $profile = CompanyProfile::create($data);

        return response()->json([
            'message' => 'تم إنشاء بروفايل الشركة بنجاح',
            'profile' => $profile,
        ], 201);
    }
    //___________________________________________________________
    public function update(CompanyProfileRequest $request)
    {
        $investorId = Auth::id();

        $profile = CompanyProfile::firstOrCreate(
            ['investor_id' => $investorId],
            []
        );

        $data = $request->validated();

        // رفع الشعار الجديد
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company_logos', 'public');
        }

        $profile->update($data);

        return response()->json([
            'message' => 'تم تحديث بروفايل الشركة بنجاح',
            'profile' => $profile,
        ], 200);
    }
    //___________________________________________________________
    public function show()
    {
        $user = Auth::user();
        $investor = $user->investor;

        // جلب بروفايل الشركة إذا كان موجودًا
        $profile = $investor ? CompanyProfile::where('investor_id', $investor->id)->first() : null;

        return response()->json([
            'company_name' => $profile->company_name ?? $investor->company_name ?? null,
            'about'        => $profile->about ?? $investor->bio ?? null,

            // بيانات الاتصال
            'email'        => $profile->email ?? $user->email,
            'phone'        => $profile->phone ?? $user->phone,
            'website'      => $profile->website ?? $investor->website,

            // السوشيال ميديا
            'social_links' => $profile->social_links ?? $investor->social_links ?? [],

            // الشعار
            'logo'         => $profile && $profile->logo ? asset('storage/' . $profile->logo) : null,

            // حالة الحساب
            'status'       => $investor->status ?? 'active',
            'role'         => $user->role ?? 'investor',
            'created_at'   => $profile?->created_at,
            'updated_at'   => $profile?->updated_at,
        ], 200);
    }

}

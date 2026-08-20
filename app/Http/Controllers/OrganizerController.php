<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizerRegisterRequest;
use App\Mail\VerificationCodeMail;
use App\Models\Organizer;
use App\Models\OtpCode;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrganizerController extends Controller
{
    public function register(OrganizerRegisterRequest $request)//✅
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($request, $data) {
                $user = User::create([
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'role' => 'organizer',
                    'status' => 'pending',
                ]);

                $newOtp = rand(100000, 999999);
                while (OtpCode::where('code', $newOtp)->exists()) {
                    $newOtp = rand(100000, 999999);
                }
                $otp = OtpCode::create([
                    'user_id' => $user->id,
                    'code' => $newOtp,
                    'expires_at' => now()->addMinutes(10),
                    'is_used' => false,
                ]);
                Mail::to($user->email)->send(new VerificationCodeMail($otp));

                $logoPath = $request->file('logo')->store('organizer_logo', 'public');
                $legalDocumentPath = $request->file('legalDocument')->store('organizer_documents', 'public');

                $organizer = Organizer::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'category' => $data['category'],
                    'headquarters' => $data['headquarters'],
                    'reg_number' => $data['registration_number'],
                    'location' => $data['exhibition_location'],
                    'description' => $data['description'] ?? null,
                    'logo' => $logoPath,
                    'legal_document' => $legalDocumentPath,
                ]);

                return response()->json([
                    'status' => $user->status,
                    'userId' => 'user_'.$user->id,
                    'data' => [
                        'company_name' => $organizer->company_name,
                        'logo_url' => asset('storage/' . $organizer->logo),
                        'legal_document_url' => asset('storage/' . $organizer->legal_document),
                    ]
                ], 201);
            });
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'organizers')) {
                return response()->json([
                    'message' => 'This registration number is already used.',
                    'errors' => [
                        'registration_number' => ['The registration number already exists.'],
                    ],
                ], 422);
            }

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'errors' => [
                    'general' => [$e->getMessage()],
                ],
            ], 500);
        }
    }
    //================================================================
    public function login(Request $request)//✅
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->role !== 'organizer')
        {
            return response()->json([
                'message' => 'This account is not an organizer'
            ], 403);
        }

        if ($user->status !== 'approved' || !$user->is_verified) {
            return response()->json([
                'message' => 'طلبك قيد المراجعة. ستتمكن من تسجيل الدخول بعد الموافقة عليه.',
            ], 403);
        }

        if (!Hash::check($request->password, $user->password))
        {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        $organizer = $user->organizer;
        $exhibition = $organizer
            ? Exhibition::where('organizer_id', $organizer->id)->first()
            : null;

        $token = $user->createToken('organizer_token')->plainTextToken;

        // $data =
        //     [
        //         'token' => $token,
        //         'id' => $user->id,
        //         'email' => $user->email,
        //         'company_name' => $organizer->company_name,
        //     ];

        // return response()->json([
        //     'message' => 'Login successful',
        //     'data' => $data,
        // ], 200);
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $organizer?->company_name ?? $user->email,
                'full_name' => $exhibition?->admin_full_name,
                'company_name' => $organizer?->company_name ?? null,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'category' => $organizer?->category ?? null,
                'headquarters' => $organizer?->headquarters ?? null,
                'registration_number' => $organizer?->reg_number ?? null,
                'exhibition_location' => $organizer?->location ?? null,
                'description' => $organizer?->description ?? null,
                'logo_url' => $organizer && $organizer->logo ? asset('storage/' . $organizer->logo) : null,
                'permissions' => [
                    "exhibitions:read",
                    "exhibitions:write",
                    "staff:read",
                    "staff:write",
                    "map:write",
                    "map:publish"
                ]
            ]
        ], 200);
    }
    //================================================================
    public function updateProfile(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|min:2|max:200',
            'phone' => 'required|string'
        ]);

        $user = Auth::user();
        $user->update([
            'phone' => $request->phone,
        ]);

        $organizer = $user->organizer;
        $exhibition = $organizer
            ? Exhibition::where('organizer_id', $organizer->id)->first()
            : null;

        if (!$exhibition) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد معرض مرتبط بهذا المنظم لحفظ الاسم الكامل.',
            ], 422);
        }

        $exhibition->update(['admin_full_name' => $request->full_name]);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed successfully'
        ], 200);

    }
    //================================================================
    public function updateCompany(Request $request)
    {
        $request->validate([
            'company_name'   => 'nullable|string|max:200',
            'category'       => 'nullable|string|max:200',
            'headquarters'   => 'nullable|string|max:200',
            'registration_number' => 'nullable|string|max:200',
            'exhibition_location' => 'nullable|string|max:200',
            'description'    => 'nullable|string|max:500',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'legalDocument'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:8192',
        ]);

        $user=Auth::user();
        $organizer=$user->organizer;

        if ($request->hasFile('logo')) {
            if ($organizer->logo) {
                Storage::disk('public')->delete($organizer->logo);
            }
            $organizer->logo = $request->file('logo')->store('organizer_logo', 'public');
        }

        if ($request->hasFile('legalDocument')) {
            if ($organizer->legal_document) {
                Storage::disk('public')->delete($organizer->legal_document);
            }
            $organizer->legal_document = $request->file('legalDocument')->store('organizer_documents', 'public');
        }

        $organizer->update([
            'company_name'   => $request->company_name,
            'category'       => $request->category,
            'headquarters'   => $request->headquarters,
            'reg_number'     => $request->registration_number,
            'location'       => $request->exhibition_location,
            'description'    => $request->description,
            'logo'           => $organizer->logo,
            'legal_document' => $organizer->legal_document,
        ]);

        // return response()->json([
        //     'message' => 'Company Profile updated successfully',
        //     'organizer' => $organizer
        // ], 200);
        return response()->json([
            'success' => true,
            'message' => 'Operation completed successfully'
        ], 200);

    }
    //================================================================
    public function getPorfile()
    {
        $user = Auth::user();
        $organizer = $user->organizer;
        $exhibition = $organizer
            ? Exhibition::where('organizer_id', $organizer->id)->first()
            : null;

        return response()->json([
            'id' => $user->id,
            'name' => $organizer?->company_name ?? $user->email,
            'full_name' => $exhibition?->admin_full_name,
            'company_name' => $organizer?->company_name ?? null,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'category' => $organizer?->category ?? null,
            'headquarters' => $organizer?->headquarters ?? null,
            'registration_number' => $organizer?->reg_number ?? null,
            'exhibition_location' => $organizer?->location ?? null,
            'description' => $organizer?->description ?? null,
            'logo_url' => $organizer && $organizer->logo ? asset('storage/' . $organizer->logo) : null,
            'permissions' => [
                "exhibitions:read",
                "exhibitions:write",
                "staff:read",
                "staff:write",
                "map:write",
                "map:publish"
            ]
        ], 200);

    }
    //================================================================
    //================================================================

}

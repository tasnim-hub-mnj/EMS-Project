<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Mail\StaffLinkRoleMail;
use App\Mail\VerificationCodeMail;
use App\Models\Exhibition;
use App\Models\OtpCode;
use App\Models\PortalLink;
use App\Models\StaffRole;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class StaffMemberController extends Controller
{
    private function organizerExhibition(): ?Exhibition
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return null;
        }

        return $user->organizer()->first()?->exhibition()->first();
    }

    private function currentExhibition(): ?Exhibition
    {
        if ($exhibition = $this->organizerExhibition()) {
            return $exhibition;
        }

        return PortalLink::query()
            ->where('token', request()->header('X-Portal-Token'))
            ->where('active', true)
            ->first()?->exhibition;
    }

    private function resolveStaffByIdentifier($staffId): StaffMember
    {
        $exhibition = $this->currentExhibition();

        return StaffMember::query()
            ->where('exhibition_id', $exhibition?->id)
            ->where(function ($query) use ($staffId) {
                $query->where('number', $staffId)->orWhere('id', $staffId);
            })
            ->firstOrFail();
    }

    public function index()
    {
        $exhibition = $this->currentExhibition();

        if (!$exhibition)
        {
            return response()->json([], 200);
        }

        $query = StaffMember::with(['user', 'portalLinks'])
            ->where('exhibition_id', $exhibition->id);

        if (request()->has('team'))
        {
            $team = request('team') === 'service' ? 'services' : request('team');
            $query->where('team', $team);
        }

        $staff = $query->orderByDesc('id')->get();

        return StaffResource::collection($staff);
    }

    public function portalAssignees(Request $request)
    {
        $portalToken = $request->header('X-Portal-Token');
        $portal = PortalLink::where('token', $portalToken)
            ->where('active', true)
            ->where('role', 'organizational')
            ->whereJsonContains('permissions', 'org.tasks')
            ->first();

        abort_unless($portal, 403, 'رابط البوابة غير صالح أو لا يملك صلاحية إدارة المهام.');

        $exhibitionId = (string) ($request->query('exhibition_id') ?: $portal->exhibition_id);
        abort_unless($exhibitionId !== '' && (string) $portal->exhibition_id === $exhibitionId, 403, 'المعرض غير مرتبط بهذه البوابة.');

        $staff = StaffMember::with(['user', 'portalLinks'])
            ->where('team', 'organizational')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('name')
            ->get();

        return StaffResource::collection($staff);
    }
    //================================================================
    public function show($staff_id)
    {
        $staff = StaffMember::with(['user', 'portalLinks'])
            ->where('exhibition_id', $this->currentExhibition()?->id)
            ->where(function ($query) use ($staff_id) {
                $query->where('number', $staff_id)->orWhere('id', $staff_id);
            })
            ->firstOrFail();

        return new StaffResource($staff);
    }
    //================================================================
    public function searchById(Request $request)
    {
        $identifier = trim((string) $request->input('id'));
        $role = strtolower(trim((string) $request->input('role')));
        $user = Auth::user();
        $exhibitionId = $this->organizerExhibition()?->id;

        if ($identifier === '' || !$exhibitionId) {
            return response()->json(null, 404);
        }

        $staff = StaffMember::with('user')
            ->where('exhibition_id', $exhibitionId)
            ->where(function ($query) use ($identifier) {
                $query->where('number', $identifier)->orWhere('id', $identifier);
            })
            ->first();

        if (!$staff) {
            return response()->json(null, 404);
        }

        $matchesRole = match ($role) {
            'administrative' => $staff->team === 'administrative',
            'organizational' => $staff->team === 'organizational',
            'services' => in_array($staff->team, ['services', 'service'], true),
            'external' => $staff->team === 'external',
            default => true,
        };

        if (!$matchesRole) {
            return response()->json([
                'message' => 'المعرف لا يتناسب مع الدور المختار.',
                'expected_role' => $role,
                'staff_team' => $staff->team,
            ], 422);
        }

        return response()->json([
            'id' => $staff->number,
            'name' => $staff->name,
            'title' => $staff->role ?? $staff->rank ?? '',
            'email' => $staff->user?->email,
        ]);
    }
    //================================================================
    public function store(StoreStaffRequest $request)
    {
        $exhibition = $this->currentExhibition();

        if (!$exhibition)
        {
            return response()->json(['message' => 'Organizer exhibition not found'], 404);
        }

        $data = $request->validated();

        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $normalizedEmail = $email !== '' ? mb_strtolower($email) : null;
        $normalizedPhone = $phone !== '' ? preg_replace('/\s+/', '', $phone) : null;

        $existingUser = null;

        if ($normalizedEmail !== null) {
            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->first();
        }

        if (!$existingUser && $normalizedPhone !== null) {
            $existingUser = User::query()
                ->whereRaw('REPLACE(phone, " ", "") = ?', [$normalizedPhone])
                ->first();
        }

        if ($normalizedEmail !== null) {
            $emailAlreadyRegistered = StaffMember::query()
                ->where('exhibition_id', $exhibition->id)
                ->whereHas('user', function ($query) use ($normalizedEmail) {
                    $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                })
                ->exists();

            if ($emailAlreadyRegistered) {
                return response()->json([
                    'message' => 'This email is already registered for a staff member in this exhibition.',
                ], 422);
            }
        }

        if ($existingUser && $existingUser->role && $existingUser->role !== 'staff') {
            return response()->json([
                'message' => 'This email is already associated with a non-staff user and cannot be reused for staff in another exhibition.',
            ], 422);
        }

        $account = $existingUser ?? User::create([
            'email'  => $email,
            'phone'  => $phone,
            'role'   => 'staff',
            'status' => 'approved',
        ]);

        if ($normalizedEmail && $account->email !== $email) {
            $account->email = $email;
            $account->save();
        }
        if ($normalizedPhone && $account->phone !== $phone) {
            $account->phone = $phone;
            $account->save();
        }
        if (!$account->role || $account->role === 'staff') {
            $account->role = 'staff';
            $account->save();
        }

        $alreadyExistsInThisExhibition = StaffMember::query()
            ->where('user_id', $account->id)
            ->where('exhibition_id', $exhibition->id)
            ->exists();

        if ($alreadyExistsInThisExhibition) {
            return response()->json([
                'message' => 'This staff member is already registered in this exhibition using the same email.',
            ], 422);
        }

        $idImagePath = $request->hasFile('idImage')
            ? $request->file('idImage')->store('staff/id', 'public')
            : ($request->hasFile('id_image') ? $request->file('id_image')->store('staff/id', 'public') : null);

        $profileImagePath = $request->hasFile('profileImage')
            ? $request->file('profileImage')->store('staff/profile', 'public')
            : ($request->hasFile('profile_image') ? $request->file('profile_image')->store('staff/profile', 'public') : null);

        $cvPath = $request->hasFile('cvFile')
            ? $request->file('cvFile')->store('staff/cv', 'public')
            : ($request->hasFile('cv_file') ? $request->file('cv_file')->store('staff/cv', 'public') : null);

        $contractPath = $request->hasFile('contractFile')
            ? $request->file('contractFile')->store('staff/contracts', 'public')
            : ($request->hasFile('contract_file') ? $request->file('contract_file')->store('staff/contracts', 'public') : null);

        $team = $data['team'] ?? null;
        if (is_string($team)) {
            $team = strtolower(trim($team));
            $team = match ($team) {
                'service' => 'services',
                'technical' => 'organizational',
                default => $team,
            };
        }

        $paymentPeriod = $data['paymentPeriod'] ?? null;
        if (is_string($paymentPeriod)) {
            $paymentPeriod = strtolower(trim($paymentPeriod));
            $paymentPeriod = $paymentPeriod === 'biweekly' ? 'bi-weekly' : $paymentPeriod;
        }

        $workDays = $data['workDays'] ?? ($data['work_days'] ?? null);
        if (is_string($workDays)) {
            $decoded = json_decode($workDays, true);
            $workDays = is_array($decoded)
                ? array_values($decoded)
                : array_values(array_filter(
                    array_map('trim', preg_split('/[,\[\]]+/', $workDays, -1, PREG_SPLIT_NO_EMPTY)),
                    fn ($day) => $day !== ''
                ));
        }

        $qrCode = $data['qrCode'] ?? $data['qr_code'] ?? null;

        $staff = StaffMember::create([
            'user_id'        => $account->id,
            'exhibition_id'  => $exhibition->id,
            'name'           => $data['name'],
            'type'           => $data['type'] ?? null,
            'role'           => $data['role'] ?? null,
            'rank'           => $data['rank'] ?? null,
            'team'           => $team,
            'nationalId'     => $data['nationalId'] ?? ($data['national_id'] ?? null),
            'schedule'       => $data['schedule'] ?? null,
            'attendanceRate' => $data['attendanceRate'] ?? ($data['attendance_rate'] ?? 0),
            'tasksCompleted' => $data['tasksCompleted'] ?? ($data['tasks_completed'] ?? 0),
            'tasksTotal'     => $data['tasksTotal'] ?? ($data['tasks_total'] ?? 0),
            'salary'         => $data['salary'] ?? 0,
            'paymentPeriod'  => $paymentPeriod ?? 'monthly',
            'workDays'       => $workDays,
            'qr_code'        => $qrCode,
            'idImage'        => $idImagePath,
            'profileImage'   => $profileImagePath,
            'cvFile'         => $cvPath,
            'contractFile'   => $contractPath,
        ]);

        // أسماء الملفات
        if ($cvPath)
        {
            $staff->cvFileName = $staff->number . '-cv.pdf';
        }
        if ($contractPath)
        {
            $staff->contractFileName = $staff->number . '-contract.pdf';
        }
        $staff->save();

        app(NotificationService::class)->forExhibition(
            $exhibition,
            'تمت إضافة موظف جديد',
            'تمت إضافة الموظف ' . $staff->name . ' إلى المعرض بنجاح.',
            'staff',
            'admin.staff',
            ['staffId' => (string) $staff->id],
            '/staff'
        );

        // إنشاء portalLink (بدون إرسال الرابط حالياً)
        // PortalLink::create([
        //     'exhibition_id' => $data['exhibition_id'],
        //     'staff_id'      => $staff->id,
        //     'token'          => null,
        //     'is_used'       => false,
        //     'qr_value'      => 'STAFF:' . $staff->number . ':' . $staff->name,
        // ]);

        return new StaffResource($staff);
    }
    //================================================================
    public function update(UpdateStaffRequest $request, $staff_id)
    {
        $staff = $this->resolveStaffByIdentifier($staff_id);
        $data  = $request->validated();

        $staff->fill($data);

        // تحديث الملفات
        if ($request->hasFile('idImage'))
        {
            Storage::disk('public')->delete($staff->idImage);
            $staff->idImage = $request->file('idImage')->store('staff/id', 'public');
        }

        if ($request->hasFile('profileImage'))
        {
            Storage::disk('public')->delete($staff->profileImage);
            $staff->profileImage = $request->file('profileImage')->store('staff/profile', 'public');
        }

        if ($request->hasFile('cvFile'))
        {
            Storage::disk('public')->delete($staff->cvFile);
            $staff->cvFile = $request->file('cvFile')->store('staff/cv', 'public');
            $staff->cvFileName = $staff->number . '-cv.pdf';
        }

        if ($request->hasFile('contractFile'))
        {
            Storage::disk('public')->delete($staff->contractFile);
            $staff->contractFile = $request->file('contractFile')->store('staff/contracts', 'public');
            $staff->contractFileName = $staff->number . '-contract.pdf';
        }

        $staff->save();

        $exhibition = $staff->exhibition_id ? Exhibition::find($staff->exhibition_id) : null;
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, 'تم تعديل بيانات موظف', 'تم تعديل بيانات الموظف ' . $staff->name . '.', 'staff', 'admin.staff',
                ['staffId' => (string) $staff->id], '/staff'
            );
        }

        return new StaffResource($staff);
    }
    //================================================================
    public function destroy($staff_id)
    {
        $staff = $this->resolveStaffByIdentifier($staff_id);
        $exhibitionId = $staff->exhibition_id;
        $staffName = $staff->name;

        Storage::disk('public')->delete([
            $staff->idImage,
            $staff->profileImage,
            $staff->cvFile,
            $staff->contractFile,
        ]);

        $staff->delete();

        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, 'تم حذف موظف', 'تم حذف الموظف ' . $staffName . '.', 'staff', 'admin.staff', [], '/staff'
            );
        }

        return response()->json([
            'message' => 'Staff deleted successfully'
        ], 200);
    }

    public function applications()
    {
        return response()->json([], 200);
    }

    public function updateApplicationStatus(Request $request, $id)
    {
        return response()->json([
            'id' => $id,
            'status' => $request->input('status', 'new'),
        ], 200);
    }

    public function acceptApplication($id)
    {
        return response()->json([
            'application' => null,
            'staff' => null,
            'id' => $id,
        ], 200);
    }
    //================================================================
    public function getByExhibition($exhibition_id)
    {
        $roles = portalLink::where('exhibition_id', $exhibition_id)
            ->with(['staff.user'])
            ->get();

        $staff = $roles->map(fn($role) => new StaffResource($role->staff));

        return response()->json([
            'staff' => $staff
        ], 200);
    }
    //================================================================
    // public function claimRole($link)
    // {
    //     // البحث عن الدور عبر الرابط
    //     $staffRole = StaffRole::where('link', $link)->first();

    //     if (!$staffRole)
    //     {
    //         return response()->json([
    //             'message' => 'Invalid link'
    //         ], 404);
    //     }

    //     // إذا الرابط مستخدم سابقًا
    //     if ($staffRole->is_used)
    //     {
    //         return response()->json([
    //             'message' => 'This link has already been used'
    //         ], 400);
    //     }

    //     // تحديث حالة الرابط
    //     $staffRole->is_used = true;
    //     $staffRole->save();

    //     return response()->json([
    //         'message' => 'Role claimed successfully',
    //         'staff_role' => $staffRole
    //     ], 200);
    // }
    //================================================================
    //================================================================
    //================================================================
    //================================================================


    // public function updateStaff(UpdateStaffRequest $request, $staff_id)
    // {
    //     $staff = StaffMember::findOrFail($staff_id);
    //     $data  = $request->validated();

    //     $staff->fill([
    //         'name'             => $data['name']             ?? $staff->name,
    //         'type'             => $data['type']             ?? $staff->type,
    //         'role'             => $data['role']             ?? $staff->role,
    //         'rank'             => $data['rank']             ?? $staff->rank,
    //         'team'             => $data['team']             ?? $staff->team,
    //         'nationalId'       => $data['nationalId']       ?? $staff->nationalId,
    //         'schedule'         => $data['schedule']         ?? $staff->schedule,
    //         'attendanceRate'   => $data['attendanceRate']   ?? $staff->attendanceRate,
    //         'tasksCompleted'   => $data['tasksCompleted']   ?? $staff->tasksCompleted,
    //         'tasksTotal'       => $data['tasksTotal']       ?? $staff->tasksTotal,
    //         'salary'           => $data['salary']           ?? $staff->salary,
    //         'paymentPeriod'    => $data['paymentPeriod']    ?? $staff->paymentPeriod,
    //         'workDays'    => $data['workDays']    ?? $staff->workDays,

    //     ]);
    //     if ($request->hasFile('idImage'))
    //     {
    //         if ($staff->idImage)
    //         {
    //             Storage::disk('public')->delete($staff->idImage);
    //         }

    //         $path = $request->file('idImage')->store('staff/id', 'public');
    //         $staff->idImage = $path;
    //     }
    //     if ($request->hasFile('profileImage'))
    //     {
    //         if ($staff->profileImage)
    //         {
    //             Storage::disk('public')->delete($staff->profileImage);
    //         }

    //         $path = $request->file('profileImage')->store('staff/profile', 'public');
    //         $staff->profileImage = $path;
    //     }
    //     if ($request->hasFile('cvFile'))
    //     {
    //         if ($staff->cvFile)
    //         {
    //             Storage::disk('public')->delete($staff->cvFile);
    //         }

    //         $path = $request->file('cvFile')->store('staff/cv', 'public');
    //         $staff->cvFile = $path;
    //     }
    //     if ($request->hasFile('contractFile'))
    //     {
    //         if ($staff->contractFile)
    //         {
    //             Storage::disk('public')->delete($staff->contractFile);
    //         }

    //         $path = $request->file('contractFile')->store('staff/contracts', 'public');
    //         $staff->contractFile = $path;
    //     }

    //     $staff->save();

    //     return response()->json([
    //         'message' => 'Staff updated successfully',
    //         'data'    => $staff
    //     ], 200);
    // }
    // //================================================================
    // public function getOneStaff($staff_id)
    // {
    //     $staff = StaffMember::find($staff_id);

    //     if (!$staff)
    //     {
    //         return response()->json([
    //             'message' => 'Staff not found'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'message' => 'Staff retrieved successfully',
    //         'data'    => $staff
    //     ], 200);
    // }
    // //================================================================
    // public function deleteStaff($staff_id)
    // {
    //     $staff = StaffMember::find($staff_id);

    //     if (!$staff)
    //     {
    //         return response()->json([
    //             'message' => 'Staff not found'
    //         ], 404);
    //     }

    //     if ($staff->idImage)
    //     {
    //         Storage::disk('public')->delete($staff->idImage);
    //     }
    //     if ($staff->profileImage)
    //     {
    //         Storage::disk('public')->delete($staff->profileImage);
    //     }
    //     if ($staff->cvFile)
    //     {
    //         Storage::disk('public')->delete($staff->cvFile);
    //     }
    //     if ($staff->contractFile)
    //     {
    //         Storage::disk('public')->delete($staff->contractFile);
    //     }
    //     $staff->delete();

    //     return response()->json([
    //         'message' => 'Staff deleted successfully'
    //     ], 200);
    // }
    // //================================================================
    // public function getAllStaff($exhibition_id)
    // {
    //     $roles = StaffRole::where('exhibition_id', $exhibition_id)
    //         ->with(['staff.user'])
    //         ->get();

    //     $staff_data = $roles->map(function($role)
    //     {
    //         $staff = $role->staff;
    //         $user  = $staff->user;

    //         return
    //         [
    //             'user_id'         => $user->id,
    //             'staff_id'        => $staff->id,
    //             'staff_number'    => $staff->number,
    //             'name'            => $staff->name,
    //             'email'           => $user->email,
    //             'phone'           => $user->phone,
    //             'role'            => $staff->role,
    //             'rank'            => $staff->rank,
    //             'team'            => $staff->team,
    //             'schedule'        => $staff->schedule,
    //             'attendanceRate'  => $staff->attendanceRate,
    //             'tasksCompleted'  => $staff->tasksCompleted,
    //             'tasksTotal'      => $staff->tasksTotal,
    //             //staff_roles
    //             'qrCode'          => $role->qrCode,
    //             'is_used'         => $role->is_used,
    //         ];
    //     });

    //     return response()->json([
    //         'staffs' => $staff_data,
    //     ], 200);
    // }
    //================================================================




    //================================================================
    //================================================================
    //================================================================
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:users,email',
    //         // 'password' => 'required|string',
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     if (!$user || $user->role !== 'staff')
    //     {
    //         return response()->json([
    //             'message' => 'This account is not an staff'
    //         ], 403);
    //     }

    //     // if (!Hash::check($request->password, $user->password))
    //     // {
    //     //     return response()->json([
    //     //         'message' => 'Invalid password'
    //     //     ], 401);
    //     // }

    //     $staff = $user->staff;

    //     if ($user->status === 'pending')
    //     {
    //         return response()->json([
    //             'message' => 'Your account is pending review'
    //         ], 403);
    //     }

    //     $token = $user->createToken('organizer_token')->plainTextToken;

    //     $data =
    //         [
    //             'token' => $token,
    //             'user'=>
    //             [
    //                 'user_id' => $user->id ,
    //                 'name' => $staff->name,
    //                 'email' => $user->email ,
    //                 'team' => $staff->team,
    //                 'role' => $staff->role,
    //                 'rank' => $staff->rank,
    //                 'permissions' =>//⛔
    //                 [

    //                 ]
    //             ]
    //         ];

    //     return response()->json([
    //         'message' => 'Login successful',
    //         'data' => $data,
    //     ], 200);
    // }
    // //================================================================

    // //================================================================
    // public function getOneStaff2($staff_id)
    // {
    //     $staff = StaffMember::findOrFail($staff_id);
    //     $user = $staff->user;

    //     $staff_data =
    //     [
    //         'id' => $staff->number,
    //         'name' => $staff->name,
    //         'email' => $user->email,
    //         'phone' => $user->phone,
    //         'type' => $staff->type,
    //         'role' => $staff->role,
    //         'rank' => $staff->rank,
    //         'team' => $staff->team,
    //         'qrCode' => $staff->qr_code,
    //         'schedule' => $staff->schedule,
    //         'attendanceRate' => $staff->attendanceRate,
    //         'tasksCompleted' => $staff->tasksCompleted,
    //         'tasksTotal' => $staff->tasksTotal,
    //         'nationalId' => $staff->nationalId,
    //         'idImage' => $staff->idImage,
    //         'profileImage' => $staff->profileImage,
    //         'cvFile' => $staff->cvFile,
    //         'salary' => $staff->salary,
    //         'paymentPeriod' => $staff->paymentPeriod,
    //         'workDays' => $staff->workDays,
    //         'contractFile' => $staff->contractFile,
    //     ];
    //     return response()->json([
    //         'staff' => $staff_data,
    //     ], 200);
    // }
    // //================================================================
    // public function UpdateStaff(UpdateStaffRequest $request,$staff_id)
    // {
    //     $data = $request->validated();
    //     $staff = StaffMember::findOrFail($staff_id);
    //     $user = $staff->user;

    //     $user->update([
    //         'email' => $data['email'],
    //         'phone' => $data['phone'],
    //     ]);
    //     $staff->update([
    //         'id' => $data['id'],
    //         'name' => $data['name'],
    //         'type' => $data['type'],
    //         'role' => $data['role'],
    //         'rank' => $data['rank'],
    //         'team' => $data['team'],
    //         'qr_code' => $data['qrCode'],
    //         'schedule' => $data['schedule'],
    //         'attendanceRate' => $data['attendanceRate'],
    //         'tasksCompleted' => $data['tasksCompleted'],
    //         'tasksTotal' => $data['tasksTotal'],
    //         'nationalId' => $data['nationalId'],
    //         'idImage' => $data['idImage'],
    //         'profileImage' => $data['profileImage'],
    //         'cvFile' => $data['cvFile'],
    //         'salary' => $data['salary'],
    //         'paymentPeriod' => $data['paymentPeriod'],
    //         'workDays' => $data['workDays'],
    //         'contractFile' => $data['contractFile'],
    //     ]);

    //     return response()->json([
    //         'message' => 'Staff Updated successfully',
    //         'user' => $user,
    //         'staff' => $staff
    //     ], 200);
    // }
    //================================================================
    // public function deleteAccount(Request $request,$staff_id)
    // {
    //     $staff = StaffMember::findOrFail($staff_id);
    //     $user = $staff->user;

    //     $user->delete();
    //     if ($request->user()->currentAccessToken())
    //     {
    //         $request->user()->currentAccessToken()->delete();
    //     }
    //     $staff->delete();

    //     return response()->json([
    //         'message' => 'staff deleted successfully'
    //     ], 200);
    // }
    //================================================================
    //================================================================
    //================================================================
}

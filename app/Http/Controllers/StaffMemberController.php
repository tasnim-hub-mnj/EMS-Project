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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffMemberController extends Controller
{
    public function index()
    {
        $query = StaffMember::with(['user', 'portalLink']);

        if (request()->has('team'))
        {
            $query->where('team', request('team'));
        }

        if (request()->has('exhibition_id'))
        {
            $query->whereHas('portalLink', function ($q)
            {
                $q->where('exhibition_id', request('exhibition_id'));
            });
        }

        $staff = $query->orderByDesc('id')->get();

        return StaffResource::collection($staff);
    }
    //================================================================
    public function show($staff_id)
    {
        $staff = StaffMember::with(['user', 'portalLink'])->findOrFail($staff_id);

        return new StaffResource($staff);
    }
    //================================================================
    public function store(StoreStaffRequest $request)
    {
        $data = $request->validated();

        // إنشاء مستخدم للموظف
        $user = User::create([
            'email'  => $data['email'],
            'phone'  => $data['phone'],
            'role'   => 'staff',
            'status' => 'approved',
        ]);

        // رفع الملفات
        $idImagePath = $request->hasFile('idImage')
            ? $request->file('idImage')->store('staff/id', 'public')
            : null;

        $profileImagePath = $request->hasFile('profileImage')
            ? $request->file('profileImage')->store('staff/profile', 'public')
            : null;

        $cvPath = $request->hasFile('cvFile')
            ? $request->file('cvFile')->store('staff/cv', 'public')
            : null;

        $contractPath = $request->hasFile('contractFile')
            ? $request->file('contractFile')->store('staff/contracts', 'public')
            : null;

        // إنشاء الموظف
        $staff = StaffMember::create([
            'user_id'        => $user->id,
            'name'           => $data['name'],
            'type'           => $data['type'] ?? null,
            'role'           => $data['role'] ?? null,
            'rank'           => $data['rank'] ?? null,
            'team'           => $data['team'] ?? null,
            'nationalId'     => $data['nationalId'] ?? null,
            'schedule'       => $data['schedule'] ?? null,
            'attendanceRate' => $data['attendanceRate'] ?? 0,
            'tasksCompleted' => $data['tasksCompleted'] ?? 0,
            'tasksTotal'     => $data['tasksTotal'] ?? 0,
            'salary'         => $data['salary'] ?? 0,
            'paymentPeriod'  => $data['paymentPeriod'] ?? null,
            'workDays'       => $data['workDays'] ?? null,
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
        $staff = StaffMember::findOrFail($staff_id);
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

        return new StaffResource($staff);
    }
    //================================================================
    public function destroy($staff_id)
    {
        $staff = StaffMember::findOrFail($staff_id);

        Storage::disk('public')->delete([
            $staff->idImage,
            $staff->profileImage,
            $staff->cvFile,
            $staff->contractFile,
        ]);

        $staff->delete();

        return response()->json([
            'message' => 'Staff deleted successfully'
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

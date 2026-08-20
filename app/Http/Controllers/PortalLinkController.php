<?php

namespace App\Http\Controllers;

use App\Models\PortalLink;
use App\Models\StaffMember;
use Illuminate\Support\Str;

use App\Http\Requests\StorePortalLinkRequest;
// use App\Http\Requests\UpdatePortalLinkRequest;
use App\Http\Resources\PortalLinkResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PortalLinkController extends Controller
{
    public function searchForPortal(Request $request)
    {
        $data = $request->validate([
            'parent_token' => ['required', 'uuid'],
            'staff_id' => ['required', 'string'],
        ]);

        $parent = $this->authorizedParent($data['parent_token']);
        $staff = StaffMember::with('user')
            ->where('exhibition_id', $parent->exhibition_id)
            ->where(function ($query) use ($data) {
                $query->where('id', $data['staff_id'])->orWhere('number', $data['staff_id']);
            })
            ->first();

        if (!$staff) {
            return response()->json(['found' => false, 'message' => 'المعرف غير موجود']);
        }

        if (!$this->roleMatchesTeam($parent->role, $staff->team)) {
            return response()->json(['message' => 'لا يمكن لهذا الرابط اختيار هذا الموظف.'], 422);
        }

        if (PortalLink::where('staff_id', $staff->id)
            ->where('exhibition_id', $parent->exhibition_id)
            ->exists()) {
            return response()->json(['found' => false, 'alreadyLinked' => true, 'message' => 'تم إنشاء رابط بالفعل للموظف']);
        }

        return response()->json([
            'id' => (string) $staff->id,
            'number' => $staff->number,
            'name' => $staff->name,
            'title' => $staff->role ?? $staff->rank ?? '',
            'email' => $staff->user?->email,
            'team' => $staff->team,
            'exhibitionId' => (string) $staff->exhibition_id,
        ]);
    }

    public function subLinksForPortal(Request $request)
    {
        $parent = $this->authorizedParent($request->validate([
            'parent_token' => ['required', 'uuid'],
        ])['parent_token']);

        return PortalLinkResource::collection(
            PortalLink::where('parent_token', $parent->token)->orderByDesc('id')->get()
        );
    }

    public function storeFromPortal(Request $request)
    {
        $data = $request->validate([
            'parent_token' => ['required', 'uuid'],
            'staff_id' => ['required', 'exists:staff_members,id'],
            'staff_name' => ['required', 'string', 'max:255'],
            'staff_email' => ['nullable', 'email'],
            'staff_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:administrative,organizational,services,external'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'messaging_channels' => ['nullable', 'array'],
            'messaging_channels.*' => ['string'],
            'is_manager' => ['required', 'boolean'],
        ]);

        $parent = $this->authorizedParent($data['parent_token']);
        $staff = StaffMember::with('user')->findOrFail($data['staff_id']);

        abort_unless(
            (string) $staff->exhibition_id === (string) $parent->exhibition_id
            && $this->roleMatchesTeam($parent->role, $staff->team),
            422,
            'معرف الموظف لا يتناسب مع الدور المختار أو المعرض.'
        );
        abort_unless($data['role'] === $parent->role, 422);
        abort_unless(!PortalLink::where('staff_id', $staff->id)
            ->where('exhibition_id', $parent->exhibition_id)
            ->exists(), 409, 'تم إنشاء رابط بالفعل للموظف');
        abort_unless(
            empty(array_diff($data['permissions'], $parent->permissions ?? [])),
            422
        );

        $allowedChannels = $parent->messaging_channels ?? [];
        if (empty($allowedChannels)) {
            $allowedChannels = [
                'visitors-complaints',
                'investors-companies',
                'team-admin',
                'team-org',
            ];
        }

        $hasMessagingPermission = collect($data['permissions'])->contains(fn ($permission) => str_ends_with($permission, '.messaging'));
        abort_unless(!$hasMessagingPermission || !empty($data['messaging_channels']), 422, 'يجب اختيار نوع محادثة واحد على الأقل.');
        abort_unless(
            empty(array_diff($data['messaging_channels'] ?? [], $allowedChannels)),
            422
        );

        $link = PortalLink::create([
            ...$data,
            'token' => Str::uuid()->toString(),
            'firebase_uid' => 'staff:' . $staff->user_id,
            'staff_number' => $staff->number,
            'exhibition_id' => $parent->exhibition_id,
            'exhibition_name' => $parent->exhibition_name,
            'created_by' => $parent->token,
            'created_by_name' => $parent->staff_name,
            'active' => true,
        ]);

        return new PortalLinkResource($link);
    }

    public function destroyFromPortal(Request $request)
    {
        $data = $request->validate([
            'parent_token' => ['required', 'uuid'],
            'portal_token' => ['required', 'uuid'],
        ]);

        $parent = $this->authorizedParent($data['parent_token']);
        $child = PortalLink::where('token', $data['portal_token'])
            ->where('parent_token', $parent->token)
            ->first();

        abort_unless($child, 404, 'رابط الموظف غير موجود.');
        $child->delete();

        return response()->json(['success' => true]);
    }

    private function authorizedParent(string $token): PortalLink
    {
        $user = Auth::user();
        $parentQuery = PortalLink::where('token', $token)
            ->where('active', true)
            ->where('is_manager', true);

        if ($user?->role === 'organizer') {
            $userId = (string) $user->id;
            $parentQuery->whereIn('created_by', [$userId, 'user_' . $userId, 'org_' . $userId]);
        } elseif ($user?->role === 'staff' && $user->staff) {
            $parentQuery->whereHas('staff', fn ($query) => $query->where('user_id', $user->id));
        } else {
            abort(403, 'رابط المدير غير صالح أو لا يملك صلاحية إنشاء روابط.');
        }

        $parent = $parentQuery->first();

        if (!$parent) {
            abort(403, 'رابط المدير غير صالح أو لا يملك صلاحية إنشاء روابط.');
        }

        $permission = match ($parent->role) {
            'administrative' => 'admin.links',
            'organizational' => 'org.links',
            default => null,
        };
        abort_unless($permission && in_array($permission, $parent->permissions ?? [], true), 403, 'لا تملك صلاحية إنشاء روابط.');
        return $parent;
    }

    private function roleMatchesTeam(string $role, ?string $team): bool
    {
        return match ($role) {
            'administrative' => $team === 'administrative',
            'organizational' => $team === 'organizational',
            'services' => in_array($team, ['services', 'service'], true),
            'external' => $team === 'external',
            default => false,
        };
    }

    public function index()//فلترة حسب: created_by, parent_token, token
    {
        $query = PortalLink::query();

        if (request('created_by'))
        {
            $query->where('created_by', request('created_by'));
        }

        if (request('parent_token'))
        {
            $query->where('parent_token', request('parent_token'));
        }

        if (request('token'))
        {
            $query->where('token', request('token'));
        }

        $links = $query->orderByDesc('id')->get();

        return PortalLinkResource::collection($links);
    }
    //================================================================
    public function store(StorePortalLinkRequest $request)//إنشاء رابط وصول جديد
    {
        $data = $request->validated();
        unset($data['qr_value'], $data['qrValue']);

        $staff = StaffMember::find($data['staff_id']);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $organizer = Auth::user()?->organizer;
        $exhibition = $organizer?->exhibition()->first();
        abort_unless(
            $exhibition && (string) $staff->exhibition_id === (string) $exhibition->id,
            422,
            'الموظف غير مرتبط بمعرض المنظم.'
        );
        abort_unless(
            $this->roleMatchesTeam($data['role'], $staff->team),
            422,
            'معرف الموظف لا يتناسب مع الدور المختار.'
        );

        $data['firebase_uid'] = 'staff:' . $staff->user_id;

        // توليد UUID للرابط
        $data['token'] = Str::uuid()->toString();

        // QR يتم حفظه في جدول الموظفين فقط، والربط يتم عبر staff_id
        $staff->qr_code = $staff->qr_code ?: ('STAFF:' . $staff->id . ':' . $staff->number);
        $staff->save();

        // حالة الرابط
        $data['active'] = true;

        $link = PortalLink::create($data);

        return new PortalLinkResource($link);
    }
    //================================================================
    public function show($token)
    {
        $link = PortalLink::where('token', $token)->firstOrFail();

        return new PortalLinkResource($link);
    }
    //================================================================
    public function deactivate($token)//تعطيل الرابط
    {
        $link = PortalLink::where('token', $token)->firstOrFail();

        $link->update([
            'active' => false
        ]);

        return new PortalLinkResource($link);
    }
    //================================================================
    public function destroy($token)//حذف الرابط نهائيًا
    {
        $link = PortalLink::where('token', $token)->firstOrFail();

        $link->delete();

        return response()->json([
            'success' => true,
            'message' => 'Portal link deleted successfully'
        ], 200);
    }
    //================================================================
}

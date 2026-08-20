<?php

namespace App\Http\Controllers;

use App\Models\ExternalTeam;
use App\Models\ExternalTeamMember;
use App\Models\ExternalTeamTask;

use App\Http\Requests\StoreExternalTeamRequest;
use App\Http\Requests\UpdateExternalTeamRequest;
use App\Http\Resources\ExternalTeamMemberResource;
use App\Http\Resources\ExternalTeamResource;
use App\Http\Resources\ExternalTeamTaskResource;
use App\Models\PortalLink;
use App\Models\User;
use App\Models\Exhibition;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ExternalTeamController extends Controller
{
    private function currentExhibitionId(): ?int
    {
        $user = Auth::user();
        $organizerExhibitionId = $user instanceof User
            ? $user->organizer()->first()?->exhibition()->first()?->id
            : null;

        return $organizerExhibitionId ?? PortalLink::query()
            ->where('token', request()->header('X-Portal-Token'))
            ->where('active', true)
            ->value('exhibition_id');
    }

    private function teamQuery()
    {
        return ExternalTeam::where('exhibition_id', $this->currentExhibitionId());
    }

    private function resolveId($externalId, string $prefix = 'ext'): string
    {
        return preg_replace('/^' . preg_quote($prefix, '/') . '/i', '', (string) $externalId);
    }

    public function index()
    {
        $teams = $this->teamQuery()
            ->with(['externalTeamMembers', 'externalTeamTasks'])
            ->orderByDesc('id')
            ->get();

        return ExternalTeamResource::collection($teams);
    }
    //================================================================
    public function store(StoreExternalTeamRequest $request)
    {
        $data = $request->validated();
        $members = $data['members'] ?? [];
        $tasks = $data['tasks'] ?? [];
        unset($data['members'], $data['tasks']);
        $data['exhibition_id'] = $this->currentExhibitionId();

        $team = ExternalTeam::create($data);

        $this->notifyExternal($team->exhibition_id, 'تمت إضافة فريق خارجي', 'تمت إضافة فريق خارجي جديد للمعرض.', 'external_team.created');

        // إضافة أعضاء الفريق
        if (!empty($members))
        {
            foreach ($members as $member)
            {
                ExternalTeamMember::create([
                    'external_team_id' => $team->id,
                    'name' => $member['name'],
                    'role' => $member['role'],
                    'phone' => $member['phone'] ?? null,
                    'email' => $member['email'] ?? null,
                ]);
            }
        }

        // إضافة المهام
        if (!empty($tasks))
        {
            foreach ($tasks as $task)
            {
                ExternalTeamTask::create([
                    'external_team_id' => $team->id,
                    'external_team_member_id' => $task['external_team_member_id'],
                    'title' => $task['title'],
                    'due_date' => $task['due_date'] ?? null,
                    'status' => $task['status'] ?? 'pending',
                ]);
            }
        }

        return new ExternalTeamResource($team);
    }
    //================================================================
    public function update(UpdateExternalTeamRequest $request, $external_id)
    {
        $team = $this->teamQuery()->findOrFail($this->resolveId($external_id));

        $team->update($request->validated());

        $this->notifyExternal($team->exhibition_id, 'تم تعديل فريق خارجي', 'تم تعديل بيانات فريق خارجي.', 'external_team.updated');

        return new ExternalTeamResource($team);
    }
    //================================================================
    public function destroy($external_id)
    {
        $team = $this->teamQuery()->findOrFail($this->resolveId($external_id));

        $team->delete();

        $this->notifyExternal($team->exhibition_id, 'تم حذف فريق خارجي', 'تم حذف فريق خارجي من المعرض.', 'external_team.deleted');

        return response()->json(['message' => 'External team deleted successfully'], 200);
    }
    //================================================================
    //================================================================
    //------------------Member--------------------------
    public function storeMember(Request $request, $teamId)
    {
        $team = $this->teamQuery()->findOrFail($this->resolveId($teamId));

        $member = ExternalTeamMember::create([
            'external_team_id' => $team->id,
            'name' => $request->name,
            'role' => $request->role,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        $this->notifyExternal($team->exhibition_id, 'تمت إضافة عضو لفريق خارجي', 'تمت إضافة عضو جديد إلى فريق خارجي.', 'external_team.member_created');

        return new ExternalTeamMemberResource($member);
    }
    //================================================================
    public function updateMember(Request $request, $teamId, $memberId)
    {
        $team = $this->teamQuery()->findOrFail($this->resolveId($teamId));

        $member = ExternalTeamMember::where('external_team_id', $team->id)
            ->findOrFail($this->resolveId($memberId, 'm'));

        $member->update($request->only(['name', 'role', 'phone', 'email']));

        $this->notifyExternal($team->exhibition_id, 'تم تعديل عضو فريق خارجي', 'تم تعديل بيانات عضو في فريق خارجي.', 'external_team.member_updated');

        return new ExternalTeamMemberResource($member);
    }
    //================================================================
    public function destroyMember($teamId, $memberId)
    {
        $team = $this->teamQuery()->findOrFail($this->resolveId($teamId));

        $member = ExternalTeamMember::where('external_team_id', $team->id)
            ->findOrFail($this->resolveId($memberId, 'm'));

        $member->delete();

        $this->notifyExternal($team->exhibition_id, 'تم حذف عضو فريق خارجي', 'تم حذف عضو من فريق خارجي.', 'external_team.member_deleted');

        return response()->json(['message' => 'Member deleted successfully'], 200);
    }

    private function notifyExternal(int $exhibitionId, string $title, string $body, string $event): void
    {
        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, $title, $body, 'external_team', 'admin.external', ['event' => $event], '/staff/external', ['ext.tasks', 'admin.staff']
            );
        }
    }
    //================================================================
    //================================================================
    //------------Task-------------------------
    public function storeTask(Request $request, $teamId)
    {
        $team = $this->teamQuery()->findOrFail($this->resolveId($teamId));
        $memberId = $request->input('external_team_member_id');

        if (!$memberId) {
            return response()->json([
                'message' => 'Please select a member for this task.',
            ], 422);
        }

        $memberId = $this->resolveId($memberId, 'm');
        $memberExists = ExternalTeamMember::where('external_team_id', $team->id)
            ->whereKey($memberId)
            ->exists();

        if (!$memberExists) {
            return response()->json([
                'message' => 'The selected member does not belong to this external team.',
            ], 422);
        }

        $task = ExternalTeamTask::create([
            'external_team_id' => $team->id,
            'external_team_member_id' => $memberId,
            'title' => $request->title,
            'due_date' => $request->due_date,
            'status' => $request->status ?? 'pending',
        ]);

        $this->notifyExternal($team->exhibition_id, 'تم إنشاء مهمة لفريق خارجي', 'تم إنشاء مهمة جديدة لفريق خارجي.', 'external_task.created');

        return new ExternalTeamTaskResource($task);
    }
    //================================================================
    public function updateTask(Request $request, $teamId, $taskId)
    {
        $team = ExternalTeam::findOrFail($this->resolveId($teamId));

        $task = ExternalTeamTask::where('external_team_id', $team->id)
            ->findOrFail($this->resolveId($taskId, 't'));

        $task->update($request->only(['title', 'due_date', 'status', 'external_team_member_id']));

        $this->notifyExternal($team->exhibition_id, 'تم تعديل مهمة فريق خارجي', 'تم تعديل مهمة لفريق خارجي.', 'external_task.updated');

        return new ExternalTeamTaskResource($task);
    }
    //================================================================
}

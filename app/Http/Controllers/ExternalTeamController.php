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
use Illuminate\Http\Request;

class ExternalTeamController extends Controller
{
    public function index()
    {
        $teams = ExternalTeam::with(['externalTeamMembers', 'externalTeamTasks'])
            ->orderByDesc('id')
            ->get();

        return ExternalTeamResource::collection($teams);
    }
    //================================================================
    public function store(StoreExternalTeamRequest $request)
    {
        $data = $request->validated();

        $team = ExternalTeam::create($data);

        // إضافة أعضاء الفريق
        if (!empty($data['members']))
        {
            foreach ($data['members'] as $member)
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
        if (!empty($data['tasks']))
        {
            foreach ($data['tasks'] as $task)
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
        $team = ExternalTeam::findOrFail($external_id);

        $team->update($request->validated());

        return new ExternalTeamResource($team);
    }
    //================================================================
    public function destroy($external_id)
    {
        $team = ExternalTeam::findOrFail($external_id);

        $team->delete();

        return response()->json(['message' => 'External team deleted successfully'], 200);
    }
    //================================================================
    //================================================================
    //------------------Member--------------------------
    public function storeMember(Request $request, $teamId)
    {
        $team = ExternalTeam::findOrFail($teamId);

        $member = ExternalTeamMember::create([
            'external_team_id' => $team->id,
            'name' => $request->name,
            'role' => $request->role,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return new ExternalTeamMemberResource($member);
    }
    //================================================================
    public function updateMember(Request $request, $teamId, $memberId)
    {
        $team = ExternalTeam::findOrFail($teamId);

        $member = ExternalTeamMember::where('external_team_id', $team->id)
            ->findOrFail($memberId);

        $member->update($request->only(['name', 'role', 'phone', 'email']));

        return new ExternalTeamMemberResource($member);
    }
    //================================================================
    public function destroyMember($teamId, $memberId)
    {
        $team = ExternalTeam::findOrFail($teamId);

        $member = ExternalTeamMember::where('external_team_id', $team->id)
            ->findOrFail($memberId);

        $member->delete();

        return response()->json(['message' => 'Member deleted successfully'], 200);
    }
    //================================================================
    //================================================================
    //------------Task-------------------------
    public function storeTask(Request $request, $teamId)
    {
        $team = ExternalTeam::findOrFail($teamId);

        $task = ExternalTeamTask::create([
            'external_team_id' => $team->id,
            'external_team_member_id' => $request->external_team_member_id,
            'title' => $request->title,
            'due_date' => $request->due_date,
            'status' => $request->status ?? 'pending',
        ]);

        return new ExternalTeamTaskResource($task);
    }
    //================================================================
    public function updateTask(Request $request, $teamId, $taskId)
    {
        $team = ExternalTeam::findOrFail($teamId);

        $task = ExternalTeamTask::where('external_team_id', $team->id)
            ->findOrFail($taskId);

        $task->update($request->only(['title', 'due_date', 'status', 'external_team_member_id']));

        return new ExternalTeamTaskResource($task);
    }
    //================================================================
}

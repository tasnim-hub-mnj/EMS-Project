<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\PortalLink;
use App\Http\Requests\StoreStaffTaskRequest;
use App\Http\Requests\UpdateStaffTaskRequest;
use App\Http\Resources\StaffTaskResource;
use App\Models\Exhibition;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class StaffTaskController extends Controller
{
    private function resolveTaskId($taskId): string
    {
        return preg_replace('/^t-?/i', '', (string) $taskId);
    }

    private function syncStaffTaskCounters(array $staffNumbers): void
    {
        $staffNumbers = array_values(array_unique(array_filter(array_map('strval', $staffNumbers))));
        if (empty($staffNumbers)) {
            return;
        }

        $staffMembers = StaffMember::whereIn('number', $staffNumbers)->get();
        foreach ($staffMembers as $staff) {
            $tasks = Task::where('exhibition_id', $staff->exhibition_id)->get(['assigned_staff_ids', 'status']);
            $assignedTasks = $tasks->filter(function ($task) use ($staff) {
                return in_array($staff->number, array_map('strval', $task->assigned_staff_ids ?? []), true);
            });

            $staff->tasksTotal = $assignedTasks->count();
            $staff->tasksCompleted = $assignedTasks->where('status', 'completed')->count();
            $staff->saveQuietly();
        }
    }

    private function organizerExhibitionId(): ?int
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return null;
        }

        return $user->organizer()->first()?->exhibition()->first()?->id
            ?? PortalLink::query()
                ->where('token', request()->header('X-Portal-Token'))
                ->where('active', true)
                ->value('exhibition_id');
    }

    private function assertStaffBelongToExhibition(array $staffNumbers, ?int $exhibitionId): void
    {
        $staffNumbers = array_values(array_unique(array_filter(array_map('strval', $staffNumbers))));
        if (!$exhibitionId || empty($staffNumbers)) {
            return;
        }

        $scopedCount = StaffMember::where('exhibition_id', $exhibitionId)
            ->whereIn('number', $staffNumbers)
            ->count();

        abort_unless($scopedCount === count($staffNumbers), 422, 'لا يمكن إسناد المهمة إلى موظف من معرض آخر.');
    }

    public function index()//?exhibition_id=1
    {
        $exhibitionId = $this->organizerExhibitionId();
        $tasks = Task::where('exhibition_id', $exhibitionId)
            ->orderByDesc('id')
            ->get();

        return StaffTaskResource::collection($tasks);
    }
    //================================================================
    public function store(StoreStaffTaskRequest $request)
    {
        $data = $request->validated();
        $exhibitionId = $this->organizerExhibitionId();
        $this->assertStaffBelongToExhibition($data['assigned_staff_ids'] ?? [], $exhibitionId);
        $data['exhibition_id'] = $exhibitionId;

        $task = Task::create($data);
        $this->syncStaffTaskCounters($task->assigned_staff_ids ?? []);

        $exhibition = Exhibition::find($task->exhibition_id);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition,
                'تم إنشاء مهمة جديدة',
                'تم إنشاء مهمة جديدة لفريق المعرض.',
                'task',
                'serv.tasks',
                ['taskId' => (string) $task->id],
                '/staff/tasks'
            );
        }

        return new StaffTaskResource($task);
    }
    //================================================================
    public function update(UpdateStaffTaskRequest $request, $task_id)
    {
        $task = Task::where('exhibition_id', $this->organizerExhibitionId())
            ->findOrFail($this->resolveTaskId($task_id));
        $previousStaffNumbers = $task->assigned_staff_ids ?? [];
        $data = $request->validated();
        $this->assertStaffBelongToExhibition($data['assigned_staff_ids'] ?? [], $task->exhibition_id);

        $task->update($data);
        $this->syncStaffTaskCounters(array_merge($previousStaffNumbers, $task->assigned_staff_ids ?? []));

        $this->notifyTask($task, 'تم تعديل مهمة', 'تم تعديل مهمة من مهام فريق المعرض.', 'task.updated');

        return new StaffTaskResource($task);
    }
    //================================================================
    public function destroy($task_id)
    {
        $task = Task::where('exhibition_id', $this->organizerExhibitionId())
            ->findOrFail($this->resolveTaskId($task_id));
        $staffNumbers = $task->assigned_staff_ids ?? [];
        $exhibitionId = $task->exhibition_id;
        $task->delete();
        $this->syncStaffTaskCounters($staffNumbers);

        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, 'تم حذف مهمة', 'تم حذف مهمة من مهام فريق المعرض.', 'task', 'serv.tasks', [], '/staff/tasks', ['org.tasks', 'ext.tasks']
            );
        }

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }

    private function notifyTask(Task $task, string $title, string $body, string $event): void
    {
        $exhibition = Exhibition::find($task->exhibition_id);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, $title, $body, 'task', 'serv.tasks', ['taskId' => (string) $task->id, 'event' => $event], '/staff/tasks', ['org.tasks', 'ext.tasks']
            );
        }
    }
    //================================================================
}

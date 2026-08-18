<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreStaffTaskRequest;
use App\Http\Requests\UpdateStaffTaskRequest;
use App\Http\Resources\StaffTaskResource;

class StaffTaskController extends Controller
{
    public function index()//?exhibition_id=1
    {
        $tasks = Task::when(request('exhibition_id'), fn($q) =>
            $q->where('exhibition_id', request('exhibition_id'))
        )->orderByDesc('id')->get();

        return StaffTaskResource::collection($tasks);
    }
    //================================================================
    public function store(StoreStaffTaskRequest $request)
    {
        $task = Task::create($request->validated());

        return new StaffTaskResource($task);
    }
    //================================================================
    public function update(UpdateStaffTaskRequest $request, $task_id)
    {
        $task = Task::findOrFail($task_id);

        $task->update($request->validated());

        return new StaffTaskResource($task);
    }
    //================================================================
    public function destroy($task_id)
    {
        Task::findOrFail($task_id)->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }
    //================================================================
}

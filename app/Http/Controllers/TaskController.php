<?php

namespace App\Http\Controllers;

use App\Enums\MemberRole;
use App\Http\Requests\CompleteTaskRequest;
use App\Models\Member;
use App\Models\Task;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function today(): View
    {
        $member = app(Member::class);
        $team = app(Team::class);

        $tasks = Task::with(['assignedMember', 'completedByMember'])
            ->where('task_date', today()->toDateString())
            ->orderBy('id')
            ->get();

        if ($member->role === MemberRole::Employer) {
            return view('tasks.today-employer', [
                'tasks' => $tasks,
                'team' => $team,
                'member' => $member,
            ]);
        }

        $myTasks = $tasks->filter(
            fn (Task $task) => $task->assigned_member_id === null || $task->assigned_member_id === $member->id
        );

        return view('tasks.today-employee', [
            'tasks' => $myTasks,
            'member' => $member,
        ]);
    }

    public function complete(CompleteTaskRequest $request, Task $task, TaskService $taskService): RedirectResponse
    {
        $member = app(Member::class);

        Gate::forUser($member)->authorize('complete', $task);

        $taskService->completeTask($task, $member, $request->validated('note'));

        return redirect()->route('tasks.today')
            ->with('completed_task', $task->title)
            ->with('completed_at', now()->format('H:i'));
    }
}

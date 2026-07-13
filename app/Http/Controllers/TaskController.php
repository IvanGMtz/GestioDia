<?php

namespace App\Http\Controllers;

use App\Enums\MemberRole;
use App\Http\Requests\CompleteTaskRequest;
use App\Models\Member;
use App\Models\Task;
use App\Models\Team;
use App\Models\WorkSession;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\NotSupportedException;

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

        $openSession = WorkSession::where('member_id', $member->id)->whereNull('clocked_out_at')->first();

        return view('tasks.today-employee', [
            'tasks' => $myTasks,
            'member' => $member,
            'openSession' => $openSession,
        ]);
    }

    public function complete(CompleteTaskRequest $request, Task $task, TaskService $taskService): RedirectResponse
    {
        $member = app(Member::class);

        Gate::forUser($member)->authorize('complete', $task);

        try {
            $taskService->completeTask($task, $member, $request->validated('note'), $request->file('photo'));
        } catch (DecoderException|NotSupportedException) {
            return back()->withErrors([
                'photo' => 'No se pudo procesar esa foto. Prueba a hacerla de nuevo o cambia el formato de la cámara a JPEG (Ajustes → Cámara → Formatos → Más compatible).',
            ]);
        }

        return redirect()->route('tasks.today')
            ->with('completed_task', $task->title)
            ->with('completed_at', now()->format('H:i'));
    }
}

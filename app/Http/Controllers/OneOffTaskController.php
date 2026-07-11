<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOneOffTaskRequest;
use App\Http\Requests\UpdateOneOffTaskRequest;
use App\Models\Member;
use App\Models\Task;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OneOffTaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function store(StoreOneOffTaskRequest $request): RedirectResponse
    {
        $this->taskService->createOneOff(app(Team::class), $request->validated());

        return redirect()->route('tasks.index')->with('status', 'Tarea puntual creada.');
    }

    public function edit(Task $task): View
    {
        $members = Member::where('active', true)->orderBy('name')->get();

        return view('tasks.oneoff.edit', compact('task', 'members'));
    }

    public function update(UpdateOneOffTaskRequest $request, Task $task): RedirectResponse
    {
        $this->taskService->updateOneOff($task, $request->validated());

        return redirect()->route('tasks.index')->with('status', 'Tarea puntual actualizada.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->taskService->deleteOneOff($task);

        return redirect()->route('tasks.index')->with('status', 'Tarea puntual eliminada.');
    }
}

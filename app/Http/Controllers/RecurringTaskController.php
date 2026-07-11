<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringTaskRequest;
use App\Http\Requests\UpdateRecurringTaskRequest;
use App\Models\Member;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecurringTaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(): View
    {
        $recurringTasks = RecurringTask::with('assignedMember')
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $oneOffTasks = Task::with('assignedMember')
            ->whereNull('recurring_task_id')
            ->where('task_date', '>=', today()->toDateString())
            ->orderBy('task_date')
            ->get();

        $members = Member::where('active', true)->orderBy('name')->get();

        return view('tasks.index', compact('recurringTasks', 'oneOffTasks', 'members'));
    }

    public function create(): View
    {
        $members = Member::where('active', true)->orderBy('name')->get();

        return view('tasks.recurring.create', compact('members'));
    }

    public function store(StoreRecurringTaskRequest $request): RedirectResponse
    {
        $this->taskService->createRecurring(app(Team::class), $request->validated());

        return redirect()->route('tasks.index')->with('status', 'Tarea recurrente creada.');
    }

    public function edit(RecurringTask $recurringTask): View
    {
        $members = Member::where('active', true)->orderBy('name')->get();

        return view('tasks.recurring.edit', compact('recurringTask', 'members'));
    }

    public function update(UpdateRecurringTaskRequest $request, RecurringTask $recurringTask): RedirectResponse
    {
        $this->taskService->updateRecurring($recurringTask, $request->validated());

        return redirect()->route('tasks.index')->with('status', 'Tarea recurrente actualizada.');
    }

    public function destroy(RecurringTask $recurringTask): RedirectResponse
    {
        $this->taskService->deactivateRecurring($recurringTask);

        return redirect()->route('tasks.index')->with('status', 'Tarea recurrente desactivada.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOneOffTaskRequest;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;

class OneOffTaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function store(StoreOneOffTaskRequest $request): RedirectResponse
    {
        $this->taskService->createOneOff(app(Team::class), $request->validated());

        return redirect()->route('tasks.index')->with('status', 'Tarea puntual creada.');
    }
}

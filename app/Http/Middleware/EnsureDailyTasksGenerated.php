<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Services\TaskService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDailyTasksGenerated
{
    public function __construct(private readonly TaskService $taskService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->taskService->ensureGeneratedForToday(app(Team::class));

        return $next($request);
    }
}

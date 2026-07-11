<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function generateForTeam(Team $team, CarbonImmutable $date): int
    {
        $recurringTasks = RecurringTask::withoutGlobalScopes()
            ->where('team_id', $team->id)
            ->where('active', true)
            ->get();

        if ($recurringTasks->isEmpty()) {
            $team->update(['tasks_generated_until' => $date->toDateString()]);

            return 0;
        }

        $now = now();

        $rows = $recurringTasks->map(fn (RecurringTask $recurringTask) => [
            'team_id' => $team->id,
            'recurring_task_id' => $recurringTask->id,
            'task_date' => $date->toDateString(),
            'title' => $recurringTask->title,
            'description' => $recurringTask->description,
            'assigned_member_id' => $recurringTask->assigned_member_id,
            'requires_photo' => $recurringTask->requires_photo,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $inserted = DB::table('tasks')->insertOrIgnore($rows);

        $team->update(['tasks_generated_until' => $date->toDateString()]);

        return $inserted;
    }

    public function ensureGeneratedForToday(Team $team): void
    {
        $today = CarbonImmutable::today();

        if ($team->tasks_generated_until && ! $team->tasks_generated_until->lt($today)) {
            return;
        }

        try {
            $this->generateForTeam($team, $today);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function createRecurring(Team $team, array $data): RecurringTask
    {
        $recurringTask = $team->recurringTasks()->create($data);

        // Genera la instancia de hoy de inmediato: si no lo hiciéramos, una tarea
        // creada a media mañana no aparecería hasta el día siguiente, porque
        // tasks_generated_until del equipo ya podría estar marcado como "hoy".
        $this->generateForTeam($team, CarbonImmutable::today());

        return $recurringTask;
    }

    public function updateRecurring(RecurringTask $recurringTask, array $data): RecurringTask
    {
        $recurringTask->update($data);

        return $recurringTask;
    }

    public function deactivateRecurring(RecurringTask $recurringTask): void
    {
        $recurringTask->update(['active' => false]);
    }

    public function createOneOff(Team $team, array $data): Task
    {
        return $team->tasks()->create($data);
    }

    public function updateOneOff(Task $task, array $data): Task
    {
        $task->update($data);

        return $task;
    }

    public function deleteOneOff(Task $task): void
    {
        $task->delete();
    }

    public function completeTask(Task $task, Member $completedBy, ?string $note): Task
    {
        $task->update([
            'completed_at' => now(),
            'completed_by_member_id' => $completedBy->id,
            'completion_note' => $note,
        ]);

        return $task;
    }
}

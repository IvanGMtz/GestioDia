<?php

namespace Tests\Feature;

use App\Models\RecurringTask;
use App\Models\Team;
use App\Services\TaskService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_for_team_creates_a_task_per_active_recurring_task(): void
    {
        $team = Team::factory()->create();
        RecurringTask::factory()->for($team)->count(3)->create(['active' => true]);
        RecurringTask::factory()->for($team)->create(['active' => false]);

        $inserted = app(TaskService::class)->generateForTeam($team, CarbonImmutable::today());

        $this->assertSame(3, $inserted);
        $this->assertDatabaseCount('tasks', 3);
    }

    public function test_generate_for_team_is_idempotent(): void
    {
        $team = Team::factory()->create();
        RecurringTask::factory()->for($team)->count(2)->create(['active' => true]);

        $service = app(TaskService::class);
        $service->generateForTeam($team, CarbonImmutable::today());
        $secondRun = $service->generateForTeam($team, CarbonImmutable::today());

        $this->assertSame(0, $secondRun);
        $this->assertDatabaseCount('tasks', 2);
    }

    public function test_generate_for_team_updates_tasks_generated_until(): void
    {
        $team = Team::factory()->create(['tasks_generated_until' => null]);
        RecurringTask::factory()->for($team)->create(['active' => true]);

        app(TaskService::class)->generateForTeam($team, CarbonImmutable::today());

        $this->assertSame(today()->toDateString(), $team->fresh()->tasks_generated_until->toDateString());
    }

    public function test_creating_a_recurring_task_generates_todays_instance_immediately(): void
    {
        $team = Team::factory()->create();

        // Simula que ya se generaron las tareas de hoy antes de crear la nueva recurrente
        // (p. ej. el empleado visitó "Hoy" por la mañana, antes de que el empleador añadiera esta tarea).
        app(TaskService::class)->generateForTeam($team, CarbonImmutable::today());

        app(TaskService::class)->createRecurring($team, ['title' => 'Regar jardín']);

        $this->assertDatabaseHas('tasks', ['team_id' => $team->id, 'title' => 'Regar jardín']);
    }

    public function test_generate_daily_command_processes_all_teams(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        RecurringTask::factory()->for($teamA)->create(['active' => true]);
        RecurringTask::factory()->for($teamB)->count(2)->create(['active' => true]);

        $this->artisan('tasks:generate-daily')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 3);
    }
}

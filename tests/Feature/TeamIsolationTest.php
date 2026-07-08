<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\Team;
use App\Models\WorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoped_models_only_return_records_of_the_bound_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $membersA = Member::factory()->for($teamA)->count(2)->create();
        $membersB = Member::factory()->for($teamB)->count(3)->create();

        RecurringTask::factory()->for($teamA)->create();
        RecurringTask::factory()->for($teamB)->count(2)->create();

        Task::factory()->for($teamA)->create();
        Task::factory()->for($teamB)->count(2)->create();

        WorkSession::factory()->for($teamA)->for($membersA->first())->create();
        WorkSession::factory()->for($teamB)->for($membersB->first())->count(2)->create();

        $this->app->instance(Team::class, $teamA);

        $this->assertSame(2, Member::count());
        $this->assertSame(1, RecurringTask::count());
        $this->assertSame(1, Task::count());
        $this->assertSame(1, WorkSession::count());

        $this->assertTrue(Member::all()->every(fn (Member $member) => $member->team_id === $teamA->id));
        $this->assertTrue(RecurringTask::all()->every(fn (RecurringTask $task) => $task->team_id === $teamA->id));
        $this->assertTrue(Task::all()->every(fn (Task $task) => $task->team_id === $teamA->id));
        $this->assertTrue(WorkSession::all()->every(fn (WorkSession $session) => $session->team_id === $teamA->id));
    }

    public function test_scoped_models_return_all_records_when_no_team_is_bound(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Member::factory()->for($teamA)->create();
        Member::factory()->for($teamB)->create();

        $this->assertSame(2, Member::count());
    }

    public function test_team_model_itself_is_never_scoped(): void
    {
        $teamA = Team::factory()->create();
        Team::factory()->create();

        $this->app->instance(Team::class, $teamA);

        $this->assertSame(2, Team::count());
    }
}

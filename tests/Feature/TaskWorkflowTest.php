<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsMember(Member $member): TestResponse|static
    {
        $token = (string) Str::uuid();

        $member->devices()->create([
            'device_token' => $token,
            'last_used_at' => now(),
        ]);

        return $this->withCookie('gestiodia_device', $token);
    }

    public function test_today_route_generates_tasks_lazily_on_first_visit(): void
    {
        $team = Team::factory()->create(['tasks_generated_until' => null]);
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);
        RecurringTask::factory()->for($team)->create(['active' => true, 'title' => 'Regar plantas']);

        $this->actingAsMember($employer)->get(route('tasks.today'))->assertOk()->assertSee('Regar plantas');

        $this->assertDatabaseHas('tasks', ['team_id' => $team->id, 'title' => 'Regar plantas']);
    }

    public function test_employee_only_sees_tasks_assigned_to_them_or_unassigned(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee, 'name' => 'Luis']);
        $otherEmployee = Member::factory()->for($team)->create(['role' => MemberRole::Employee, 'name' => 'Ana']);

        Task::factory()->for($team)->create(['title' => 'Para Luis', 'assigned_member_id' => $employee->id]);
        Task::factory()->for($team)->create(['title' => 'Para Ana', 'assigned_member_id' => $otherEmployee->id]);
        Task::factory()->for($team)->create(['title' => 'Sin asignar', 'assigned_member_id' => null]);

        $response = $this->actingAsMember($employee)->get(route('tasks.today'));

        $response->assertSee('Para Luis');
        $response->assertSee('Sin asignar');
        $response->assertDontSee('Para Ana');
    }

    public function test_completing_a_task_marks_it_done_and_flashes_confirmation(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        $task = Task::factory()->for($team)->create(['assigned_member_id' => $employee->id]);

        $response = $this->actingAsMember($employee)->post(route('tasks.complete', $task));

        $response->assertRedirect(route('tasks.today'));
        $response->assertSessionHas('completed_task', $task->title);

        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertSame($employee->id, $task->fresh()->completed_by_member_id);
    }

    public function test_employee_cannot_complete_a_task_assigned_to_someone_else(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        $otherEmployee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        $task = Task::factory()->for($team)->create(['assigned_member_id' => $otherEmployee->id]);

        $this->actingAsMember($employee)->post(route('tasks.complete', $task))->assertForbidden();

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_employee_cannot_access_recurring_task_management(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $this->actingAsMember($employee)->get(route('tasks.index'))->assertForbidden();
    }

    public function test_employer_can_create_and_deactivate_a_recurring_task(): void
    {
        $team = Team::factory()->create();
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);

        $this->actingAsMember($employer)->post(route('tasks.recurring.store'), [
            'title' => 'Barrer patio',
            'requires_photo' => '1',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('recurring_tasks', [
            'team_id' => $team->id,
            'title' => 'Barrer patio',
            'requires_photo' => true,
        ]);

        $recurringTask = RecurringTask::where('title', 'Barrer patio')->firstOrFail();

        $this->actingAsMember($employer)
            ->delete(route('tasks.recurring.destroy', $recurringTask))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('recurring_tasks', ['id' => $recurringTask->id, 'active' => false]);
    }
}

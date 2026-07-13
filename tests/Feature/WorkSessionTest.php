<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Team;
use App\Models\WorkSession;
use App\Services\WorkSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsMember;
use Tests\TestCase;

class WorkSessionTest extends TestCase
{
    use RefreshDatabase;
    use ActsAsMember;

    public function test_clock_in_creates_an_open_session(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $this->actingAsMember($employee)->post(route('work-sessions.clock-in'))
            ->assertRedirect(route('tasks.today'));

        $this->assertDatabaseHas('work_sessions', [
            'member_id' => $employee->id,
            'clocked_out_at' => null,
        ]);
    }

    public function test_clock_in_twice_the_same_day_does_not_duplicate(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        app(WorkSessionService::class)->clockIn($employee, CarbonImmutable::now());
        app(WorkSessionService::class)->clockIn($employee, CarbonImmutable::now()->addMinutes(2));

        $this->assertSame(1, WorkSession::where('member_id', $employee->id)->count());
    }

    public function test_clock_in_auto_closes_a_stale_open_session_from_a_previous_day(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $yesterday = CarbonImmutable::yesterday();
        app(WorkSessionService::class)->clockIn($employee, $yesterday->setTime(8, 0));

        app(WorkSessionService::class)->clockIn($employee, CarbonImmutable::now());

        $stale = WorkSession::where('member_id', $employee->id)->where('work_date', $yesterday->toDateString())->firstOrFail();
        $this->assertTrue((bool) $stale->auto_closed);
        $this->assertSame('23:59:59', $stale->clocked_out_at->format('H:i:s'));

        $this->assertSame(2, WorkSession::where('member_id', $employee->id)->count());
        $this->assertSame(1, WorkSession::where('member_id', $employee->id)->whereNull('clocked_out_at')->count());
    }

    public function test_clock_out_closes_the_open_session(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        app(WorkSessionService::class)->clockIn($employee, CarbonImmutable::now());

        $this->actingAsMember($employee)->post(route('work-sessions.clock-out'))
            ->assertRedirect(route('tasks.today'));

        $this->assertDatabaseMissing('work_sessions', ['member_id' => $employee->id, 'clocked_out_at' => null]);
    }

    public function test_clock_out_without_open_session_shows_friendly_error(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $response = $this->actingAsMember($employee)->post(route('work-sessions.clock-out'));

        $response->assertRedirect(route('tasks.today'));
        $response->assertSessionHas('clock_error');
    }

    public function test_employer_can_edit_a_work_session_with_reason_and_snapshot(): void
    {
        $team = Team::factory()->create();
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $session = WorkSession::factory()->for($team)->for($employee)->create([
            'clocked_in_at' => CarbonImmutable::now()->setTime(8, 0),
            'clocked_out_at' => CarbonImmutable::now()->setTime(16, 0),
        ]);

        $response = $this->actingAsMember($employer)->put(route('work-sessions.update', $session), [
            'clocked_in_at' => CarbonImmutable::now()->setTime(9, 0)->format('Y-m-d\TH:i'),
            'clocked_out_at' => CarbonImmutable::now()->setTime(17, 0)->format('Y-m-d\TH:i'),
            'edit_reason' => 'Olvidó fichar la salida real',
        ]);

        $response->assertRedirect(route('work-sessions.weekly'));

        $session->refresh();
        $this->assertSame(9, $session->clocked_in_at->hour);
        $this->assertSame($employer->id, $session->edited_by_member_id);
        $this->assertSame('Olvidó fichar la salida real', $session->edit_reason);
        $this->assertNotNull($session->original_values);
        $this->assertSame(8, CarbonImmutable::parse($session->original_values['clocked_in_at'])->hour);
    }

    public function test_employee_cannot_edit_work_sessions(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        $session = WorkSession::factory()->for($team)->for($employee)->create();

        $this->actingAsMember($employee)
            ->put(route('work-sessions.update', $session), [
                'clocked_in_at' => now()->format('Y-m-d\TH:i'),
                'edit_reason' => 'intento no autorizado',
            ])
            ->assertForbidden();
    }

    public function test_weekly_summary_totals_hours_per_member(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $monday = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        WorkSession::factory()->for($team)->for($employee)->create([
            'work_date' => $monday->toDateString(),
            'clocked_in_at' => $monday->setTime(8, 0),
            'clocked_out_at' => $monday->setTime(16, 0),
        ]);

        $summary = app(WorkSessionService::class)->weeklySummary($team, $monday);
        $employeeRow = collect($summary)->firstWhere(fn ($row) => $row['member']->is($employee));

        $this->assertSame(8 * 60, $employeeRow['total_minutes']);
    }

    public function test_export_csv_contains_expected_headers_and_row(): void
    {
        $team = Team::factory()->create();
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee, 'name' => 'Luis']);

        $today = CarbonImmutable::now();
        WorkSession::factory()->for($team)->for($employee)->create([
            'work_date' => $today->toDateString(),
            'clocked_in_at' => $today->setTime(8, 0),
            'clocked_out_at' => $today->setTime(16, 0),
        ]);

        $response = $this->actingAsMember($employer)->get(route('work-sessions.export', [
            'from' => $today->toDateString(),
            'to' => $today->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->getContent();
        $this->assertStringContainsString('Luis', $content);
        $this->assertStringContainsString('Empleado,Fecha,Entrada,Salida,Horas,Auto-cerrada,Editada', $content);
    }
}

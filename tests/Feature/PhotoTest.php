<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Task;
use App\Models\Team;
use App\Services\PhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Tests\Concerns\ActsAsMember;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;
    use ActsAsMember;

    public function test_photo_service_resizes_and_stores_under_team_folder(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create();
        $file = UploadedFile::fake()->image('foto.jpg', 3000, 2000);

        $path = app(PhotoService::class)->store($team, $file);

        $this->assertStringStartsWith("photos/{$team->id}/".now()->format('Y-m').'/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);

        $stored = ImageManager::gd()->read(Storage::disk('public')->path($path));
        $this->assertLessThanOrEqual(1600, $stored->width());
        $this->assertLessThanOrEqual(1600, $stored->height());
    }

    public function test_completing_a_task_that_requires_photo_without_one_fails_validation(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        $task = Task::factory()->for($team)->create([
            'assigned_member_id' => $employee->id,
            'requires_photo' => true,
        ]);

        $response = $this->actingAsMember($employee)->post(route('tasks.complete', $task));

        $response->assertSessionHasErrors('photo');
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_completing_a_task_that_requires_photo_with_one_succeeds(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);
        $task = Task::factory()->for($team)->create([
            'assigned_member_id' => $employee->id,
            'requires_photo' => true,
        ]);

        $response = $this->actingAsMember($employee)->post(route('tasks.complete', $task), [
            'photo' => UploadedFile::fake()->image('evidencia.jpg', 2000, 1500),
        ]);

        $response->assertRedirect(route('tasks.today'));

        $task->refresh();
        $this->assertNotNull($task->completed_at);
        $this->assertNotNull($task->photo_path);
        Storage::disk('public')->assertExists($task->photo_path);
    }

    public function test_photos_prune_deletes_old_photos_and_nulls_the_path(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create();
        Storage::disk('public')->put('photos/'.$team->id.'/2020-01/old.jpg', 'contenido');

        Storage::disk('public')->put('photos/'.$team->id.'/2026-07/recent.jpg', 'contenido');

        $oldTask = Task::factory()->for($team)->create([
            'photo_path' => 'photos/'.$team->id.'/2020-01/old.jpg',
            'completed_at' => now()->subDays(120),
        ]);

        $recentTask = Task::factory()->for($team)->create([
            'photo_path' => 'photos/'.$team->id.'/2026-07/recent.jpg',
            'completed_at' => now()->subDays(5),
        ]);

        $prunedCount = app(PhotoService::class)->prune(90);

        $this->assertSame(1, $prunedCount);
        Storage::disk('public')->assertMissing('photos/'.$team->id.'/2020-01/old.jpg');
        Storage::disk('public')->assertExists('photos/'.$team->id.'/2026-07/recent.jpg');

        $this->assertNull($oldTask->fresh()->photo_path);
        $this->assertNotNull($oldTask->fresh()->photo_pruned_at);
        $this->assertNotNull($recentTask->fresh()->photo_path);
        $this->assertNull($recentTask->fresh()->photo_pruned_at);
    }

    public function test_employer_sees_photo_thumbnail_on_today_screen(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create();
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $task = Task::factory()->for($team)->create([
            'assigned_member_id' => $employee->id,
            'requires_photo' => true,
        ]);

        $this->actingAsMember($employee)->post(route('tasks.complete', $task), [
            'photo' => UploadedFile::fake()->image('evidencia.jpg', 1200, 900),
        ]);

        $response = $this->actingAsMember($employer)->get(route('tasks.today'));

        $response->assertOk();
        $response->assertSee($task->fresh()->photoUrl(), false);
    }

    public function test_employer_sees_no_thumbnail_when_task_has_no_photo(): void
    {
        $team = Team::factory()->create();
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);
        Task::factory()->for($team)->create(['requires_photo' => false, 'photo_path' => null]);

        $response = $this->actingAsMember($employer)->get(route('tasks.today'));

        $response->assertOk();
        $response->assertDontSee('Foto de evidencia', false);
    }
}

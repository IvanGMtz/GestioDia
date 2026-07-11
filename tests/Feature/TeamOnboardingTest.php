<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_team_form_renders(): void
    {
        $this->get(route('team.create.show'))->assertOk();
    }

    public function test_creating_a_team_sets_device_cookie_and_redirects_to_home(): void
    {
        $response = $this->post(route('team.create.store'), [
            'business_name' => 'Jardines Pérez',
            'owner_name' => 'Ana',
        ]);

        $response->assertRedirect(route('home.authenticated'));
        $response->assertCookie('gestiodia_device');

        $this->assertDatabaseHas('teams', ['name' => 'Jardines Pérez']);
        $this->assertDatabaseHas('members', ['name' => 'Ana', 'role' => 'EMPLOYER']);
    }

    public function test_device_cookie_resolves_member_on_subsequent_request(): void
    {
        $create = $this->post(route('team.create.store'), [
            'business_name' => 'Jardines Pérez',
            'owner_name' => 'Ana',
        ]);

        $deviceToken = $create->getCookie('gestiodia_device')->getValue();

        $response = $this->withCookie('gestiodia_device', $deviceToken)->get(route('home.authenticated'));

        $response->assertOk();
        $response->assertSee('Ana');
        $response->assertSee('Jardines Pérez');
    }

    public function test_landing_redirects_to_home_when_already_resolved(): void
    {
        $create = $this->post(route('team.create.store'), [
            'business_name' => 'Jardines Pérez',
            'owner_name' => 'Ana',
        ]);

        $deviceToken = $create->getCookie('gestiodia_device')->getValue();

        $this->withCookie('gestiodia_device', $deviceToken)
            ->get(route('home'))
            ->assertRedirect(route('home.authenticated'));
    }

    public function test_home_authenticated_redirects_to_landing_without_cookie(): void
    {
        $this->get(route('home.authenticated'))->assertRedirect(route('home'));
    }

    public function test_join_team_with_valid_code_creates_employee_member(): void
    {
        $team = Team::factory()->create(['code' => 'JARDIN-1234']);

        $response = $this->post(route('team.join.store'), [
            'code' => 'jardin-1234',
            'member_name' => 'Luis',
        ]);

        $response->assertRedirect(route('home.authenticated'));
        $response->assertCookie('gestiodia_device');

        $this->assertDatabaseHas('members', [
            'team_id' => $team->id,
            'name' => 'Luis',
            'role' => 'EMPLOYEE',
        ]);
    }

    public function test_join_team_with_invalid_code_fails_validation(): void
    {
        $response = $this->post(route('team.join.store'), [
            'code' => 'NOEXISTE-0000',
            'member_name' => 'Luis',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_join_team_at_capacity_shows_error(): void
    {
        $team = Team::factory()->create(['code' => 'JARDIN-1234', 'max_members' => 1]);
        Member::factory()->for($team)->create(['active' => true]);

        $response = $this->post(route('team.join.store'), [
            'code' => 'JARDIN-1234',
            'member_name' => 'Luis',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('members', ['team_id' => $team->id, 'name' => 'Luis']);
    }
}

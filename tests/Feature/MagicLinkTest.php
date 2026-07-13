<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\Member;
use App\Models\MemberDevice;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ActsAsMember;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;
    use ActsAsMember;

    public function test_linking_email_from_settings_sends_a_magic_link(): void
    {
        Mail::fake();

        $team = Team::factory()->create();
        $member = Member::factory()->for($team)->create(['role' => MemberRole::Employer, 'email' => null]);

        $this->actingAsMember($member)->post(route('settings.link-email'), [
            'email' => 'marta@example.com',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame('marta@example.com', $member->email);
        $this->assertNull($member->email_verified_at);
        $this->assertDatabaseCount('magic_links', 1);

        Mail::assertSent(MagicLinkMail::class);
    }

    public function test_consuming_a_valid_link_verifies_email_and_registers_a_new_device(): void
    {
        $team = Team::factory()->create();
        $member = Member::factory()->for($team)->create(['email' => 'marta@example.com', 'email_verified_at' => null]);

        $plainToken = 'plain-token-1234567890';
        $member->magicLinks()->create([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(15),
        ]);

        $devicesBefore = MemberDevice::where('member_id', $member->id)->count();

        $response = $this->get(route('magic-link.consume', ['token' => $plainToken]));

        $response->assertRedirect(route('tasks.today'));
        $response->assertCookie('gestiodia_device');

        $member->refresh();
        $this->assertNotNull($member->email_verified_at);
        $this->assertSame($devicesBefore + 1, MemberDevice::where('member_id', $member->id)->count());

        $magicLink = MagicLink::first();
        $this->assertNotNull($magicLink->used_at);
    }

    public function test_consuming_an_expired_link_fails(): void
    {
        $team = Team::factory()->create();
        $member = Member::factory()->for($team)->create();

        $plainToken = 'expired-token-1234567890';
        $member->magicLinks()->create([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get(route('magic-link.consume', ['token' => $plainToken]))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_consuming_an_already_used_link_fails(): void
    {
        $team = Team::factory()->create();
        $member = Member::factory()->for($team)->create();

        $plainToken = 'used-token-1234567890';
        $member->magicLinks()->create([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(15),
            'used_at' => now(),
        ]);

        $this->get(route('magic-link.consume', ['token' => $plainToken]))
            ->assertRedirect(route('home'))
            ->assertSessionHas('magic_link_error');
    }

    public function test_requesting_a_login_link_with_unverified_email_sends_nothing_but_shows_generic_message(): void
    {
        Mail::fake();

        $team = Team::factory()->create();
        Member::factory()->for($team)->create(['email' => 'sin-verificar@example.com', 'email_verified_at' => null]);

        $response = $this->post(route('magic-link.request.store'), ['email' => 'sin-verificar@example.com']);

        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_requesting_a_login_link_with_verified_email_sends_one(): void
    {
        Mail::fake();

        $team = Team::factory()->create();
        Member::factory()->for($team)->create(['email' => 'verificado@example.com', 'email_verified_at' => now()]);

        $response = $this->post(route('magic-link.request.store'), ['email' => 'verificado@example.com']);

        $response->assertSessionHas('status');
        Mail::assertSent(MagicLinkMail::class);
    }

    public function test_employer_can_regenerate_access_for_a_member(): void
    {
        $team = Team::factory()->create();
        $employer = Member::factory()->for($team)->create(['role' => MemberRole::Employer]);
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $response = $this->actingAsMember($employer)->post(route('team.members.regenerate-access', $employee));

        $response->assertRedirect();
        $response->assertSessionHas('recovery_link');

        $url = session('recovery_link');
        $this->assertStringContainsString('/enlace/', $url);

        $devicesBefore = MemberDevice::where('member_id', $employee->id)->count();

        $this->get($url)->assertRedirect(route('tasks.today'));
        $this->assertSame($devicesBefore + 1, MemberDevice::where('member_id', $employee->id)->count());
    }

    public function test_employee_cannot_access_team_members_screen(): void
    {
        $team = Team::factory()->create();
        $employee = Member::factory()->for($team)->create(['role' => MemberRole::Employee]);

        $this->actingAsMember($employee)->get(route('team.members.index'))->assertForbidden();
    }
}

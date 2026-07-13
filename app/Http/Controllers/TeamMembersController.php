<?php

namespace App\Http\Controllers;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Team;
use App\Services\MagicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamMembersController extends Controller
{
    public function index(): View
    {
        $team = app(Team::class);

        $members = Member::where('active', true)
            ->orderBy('name')
            ->get()
            ->sortByDesc(fn (Member $member) => $member->role === MemberRole::Employer)
            ->values();

        return view('team.show', [
            'team' => $team,
            'members' => $members,
        ]);
    }

    public function regenerateAccess(Member $member, MagicLinkService $magicLinkService): RedirectResponse
    {
        $url = $magicLinkService->issueRecoveryLink($member);

        return back()->with('recovery_link', $url)->with('recovery_member', $member->name);
    }
}

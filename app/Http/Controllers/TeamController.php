<?php

namespace App\Http\Controllers;

use App\Exceptions\TeamCapacityExceededException;
use App\Http\Requests\CreateTeamRequest;
use App\Http\Requests\JoinTeamRequest;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private readonly TeamService $teamService) {}

    public function createShow(): View
    {
        return view('team.create');
    }

    public function createStore(CreateTeamRequest $request): RedirectResponse
    {
        $result = $this->teamService->createTeam(
            $request->validated('business_name'),
            $request->validated('owner_name'),
        );

        $this->rememberDevice($request, $result['deviceToken']);

        return redirect()->route('tasks.today');
    }

    public function joinShow(): View
    {
        return view('team.join');
    }

    public function joinStore(JoinTeamRequest $request): RedirectResponse
    {
        try {
            $result = $this->teamService->joinTeam(
                $request->validated('code'),
                $request->validated('member_name'),
            );
        } catch (TeamCapacityExceededException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        $this->rememberDevice($request, $result['deviceToken']);

        return redirect()->route('tasks.today');
    }
}

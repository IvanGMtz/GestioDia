<?php

namespace App\Http\Controllers;

use App\Exceptions\TeamCapacityExceededException;
use App\Http\Requests\CreateTeamRequest;
use App\Http\Requests\JoinTeamRequest;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    private const DEVICE_COOKIE_MINUTES = 60 * 24 * 400;

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

        return redirect()->route('home.authenticated');
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

        return redirect()->route('home.authenticated');
    }

    private function rememberDevice(Request $request, string $deviceToken): void
    {
        cookie()->queue(cookie(
            'gestiodia_device',
            $deviceToken,
            self::DEVICE_COOKIE_MINUTES,
            '/',
            null,
            $request->secure(),
            true,
            false,
            'lax',
        ));
    }
}

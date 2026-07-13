<?php

namespace App\Http\Controllers;

use App\Exceptions\NoOpenWorkSessionException;
use App\Http\Requests\ClockEditRequest;
use App\Http\Requests\ExportWorkSessionsRequest;
use App\Models\Member;
use App\Models\Team;
use App\Models\WorkSession;
use App\Services\WorkSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WorkSessionController extends Controller
{
    public function __construct(private readonly WorkSessionService $workSessionService) {}

    public function clockIn(): RedirectResponse
    {
        $session = $this->workSessionService->clockIn(app(Member::class), CarbonImmutable::now());

        return redirect()->route('tasks.today')
            ->with('clock_message', 'Jornada empezada — '.$session->clocked_in_at->format('H:i'));
    }

    public function clockOut(): RedirectResponse
    {
        try {
            $session = $this->workSessionService->clockOut(app(Member::class), CarbonImmutable::now());
        } catch (NoOpenWorkSessionException $e) {
            return redirect()->route('tasks.today')->with('clock_error', $e->getMessage());
        }

        return redirect()->route('tasks.today')
            ->with('clock_message', 'Jornada terminada — '.$session->clocked_out_at->format('H:i'));
    }

    public function mine(Request $request): View
    {
        $member = app(Member::class);
        $weekStart = $this->weekStartFromRequest($request);

        $sessions = WorkSession::where('member_id', $member->id)
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekStart->addDays(6)->toDateString()])
            ->orderBy('clocked_in_at')
            ->get();

        return view('work-sessions.mine', [
            'sessions' => $sessions,
            'weekStart' => $weekStart,
        ]);
    }

    public function weekly(Request $request): View
    {
        $team = app(Team::class);
        $weekStart = $this->weekStartFromRequest($request);

        $summary = $this->workSessionService->weeklySummary($team, $weekStart);

        return view('work-sessions.weekly', [
            'summary' => $summary,
            'weekStart' => $weekStart,
        ]);
    }

    public function update(ClockEditRequest $request, WorkSession $workSession): RedirectResponse
    {
        $this->workSessionService->edit(
            $workSession,
            app(Member::class),
            [
                'clocked_in_at' => $request->validated('clocked_in_at'),
                'clocked_out_at' => $request->validated('clocked_out_at'),
            ],
            $request->validated('edit_reason'),
        );

        return redirect()->route('work-sessions.weekly')->with('status', 'Jornada actualizada.');
    }

    public function export(ExportWorkSessionsRequest $request): Response
    {
        $team = app(Team::class);
        $from = CarbonImmutable::parse($request->validated('from'));
        $to = CarbonImmutable::parse($request->validated('to'));

        $rows = $this->workSessionService->exportRows($team, $from, $to);

        $csv = fopen('php://temp', 'w+');

        if (! empty($rows)) {
            fputcsv($csv, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($csv, $row);
            }
        }

        rewind($csv);
        $contents = stream_get_contents($csv);
        fclose($csv);

        $filename = "jornadas-{$from->toDateString()}_a_{$to->toDateString()}.csv";

        return response($contents, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function weekStartFromRequest(Request $request): CarbonImmutable
    {
        $day = $request->query('week')
            ? CarbonImmutable::parse($request->query('week'))
            : CarbonImmutable::now();

        return $day->startOfWeek(CarbonImmutable::MONDAY);
    }
}

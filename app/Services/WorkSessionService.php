<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NoOpenWorkSessionException;
use App\Models\Member;
use App\Models\Team;
use App\Models\WorkSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class WorkSessionService
{
    public function clockIn(Member $member, CarbonImmutable $now): WorkSession
    {
        return DB::transaction(function () use ($member, $now) {
            // Bloquea la fila del member para serializar dobles clics simultáneos
            // (dos peticiones a la vez no deben poder crear dos sesiones abiertas).
            Member::withoutGlobalScopes()->whereKey($member->id)->lockForUpdate()->first();

            $open = WorkSession::where('member_id', $member->id)
                ->whereNull('clocked_out_at')
                ->lockForUpdate()
                ->first();

            if ($open) {
                if ($open->work_date->isSameDay($now)) {
                    // Ya había fichado entrada hoy: no duplicar por reenvío/doble clic.
                    return $open;
                }

                // Sesión abierta olvidada de un día anterior: se cierra sola a las 23:59.
                $open->update([
                    'clocked_out_at' => $open->work_date->copy()->setTime(23, 59, 59),
                    'auto_closed' => true,
                ]);
            }

            return WorkSession::create([
                'team_id' => $member->team_id,
                'member_id' => $member->id,
                'work_date' => $now->toDateString(),
                'clocked_in_at' => $now,
            ]);
        });
    }

    public function clockOut(Member $member, CarbonImmutable $now): WorkSession
    {
        return DB::transaction(function () use ($member, $now) {
            Member::withoutGlobalScopes()->whereKey($member->id)->lockForUpdate()->first();

            $open = WorkSession::where('member_id', $member->id)
                ->whereNull('clocked_out_at')
                ->lockForUpdate()
                ->first();

            if (! $open) {
                throw new NoOpenWorkSessionException;
            }

            $open->update(['clocked_out_at' => $now]);

            return $open;
        });
    }

    public function edit(WorkSession $session, Member $editedBy, array $newValues, string $reason): WorkSession
    {
        $session->update([
            'original_values' => [
                'clocked_in_at' => $session->clocked_in_at->toIso8601String(),
                'clocked_out_at' => $session->clocked_out_at?->toIso8601String(),
            ],
            'edited_by_member_id' => $editedBy->id,
            'edit_reason' => $reason,
            ...$newValues,
        ]);

        return $session;
    }

    public function weeklySummary(Team $team, CarbonImmutable $weekStart): array
    {
        $weekEnd = $weekStart->addDays(6);

        $members = Member::where('active', true)->orderBy('name')->get();

        $sessions = WorkSession::whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('clocked_in_at')
            ->get()
            ->groupBy('member_id');

        return $members->map(function (Member $member) use ($sessions) {
            $memberSessions = $sessions->get($member->id, collect());

            $totalMinutes = (int) $memberSessions->sum(
                fn (WorkSession $session) => $session->clocked_out_at
                    ? $session->clocked_in_at->diffInMinutes($session->clocked_out_at)
                    : 0
            );

            return [
                'member' => $member,
                'sessions' => $memberSessions,
                'total_minutes' => $totalMinutes,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function exportRows(Team $team, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sessions = WorkSession::with('member')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date')
            ->orderBy('clocked_in_at')
            ->get();

        return $sessions->map(function (WorkSession $session) {
            $hours = $session->clocked_out_at
                ? round($session->clocked_in_at->diffInMinutes($session->clocked_out_at) / 60, 2)
                : null;

            return [
                'Empleado' => $session->member->name,
                'Fecha' => $session->work_date->toDateString(),
                'Entrada' => $session->clocked_in_at->format('H:i'),
                'Salida' => $session->clocked_out_at?->format('H:i') ?? '',
                'Horas' => $hours !== null ? (string) $hours : '',
                'Auto-cerrada' => $session->auto_closed ? 'Sí' : '',
                'Editada' => $session->edited_by_member_id ? 'Sí' : '',
            ];
        })->all();
    }
}

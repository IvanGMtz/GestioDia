@extends('layouts.app')

@section('title', 'Mi semana — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 34rem;">
    <h1 class="mb-1">Mi semana</h1>
    <p class="text-secondary mb-4">
        {{ $weekStart->translatedFormat('j \d\e F') }} — {{ $weekStart->addDays(6)->translatedFormat('j \d\e F') }}
    </p>

    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('work-sessions.mine', ['week' => $weekStart->subWeek()->toDateString()]) }}" class="btn btn-outline-secondary">&larr; Semana anterior</a>
        <a href="{{ route('work-sessions.mine', ['week' => $weekStart->addWeek()->toDateString()]) }}" class="btn btn-outline-secondary">Semana siguiente &rarr;</a>
    </div>

    @if ($sessions->isEmpty())
        <p class="text-secondary">No hay jornadas registradas esta semana.</p>
    @else
        <div class="d-flex flex-column gap-2">
            @foreach ($sessions as $session)
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="fw-medium mb-1">{{ $session->work_date->translatedFormat('l j \d\e F') }}</p>
                            <p class="text-secondary mb-0">
                                {{ $session->clocked_in_at->format('H:i') }} —
                                {{ $session->clocked_out_at?->format('H:i') ?? 'en curso' }}
                                @if ($session->auto_closed) · cerrada automáticamente @endif
                                @if ($session->edited_by_member_id) · editada por tu empleador @endif
                            </p>
                        </div>
                        @if ($session->clocked_out_at)
                            @php $minutes = $session->clocked_in_at->diffInMinutes($session->clocked_out_at); @endphp
                            <span class="fw-medium text-nowrap">
                                {{ sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60) }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

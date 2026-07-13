@extends('layouts.app')

@section('title', 'Jornadas del equipo — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 44rem;">
    <a href="{{ route('tasks.today') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Hoy</a>

    <h1 class="mb-1">Jornadas del equipo</h1>
    <p class="text-secondary mb-4">
        {{ $weekStart->translatedFormat('j \d\e F') }} — {{ $weekStart->addDays(6)->translatedFormat('j \d\e F') }}
    </p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('work-sessions.weekly', ['week' => $weekStart->subWeek()->toDateString()]) }}" class="btn btn-outline-secondary">&larr; Semana anterior</a>
        <a href="{{ route('work-sessions.weekly', ['week' => $weekStart->addWeek()->toDateString()]) }}" class="btn btn-outline-secondary">Semana siguiente &rarr;</a>
    </div>

    <form method="GET" action="{{ route('work-sessions.export') }}" class="card mb-5">
        <div class="card-body">
            <p class="fw-medium mb-3">Exportar rango de fechas (CSV)</p>
            <div class="d-flex gap-2 flex-wrap">
                <input type="date" name="from" class="form-control" value="{{ $weekStart->toDateString() }}" required>
                <input type="date" name="to" class="form-control" value="{{ $weekStart->addDays(6)->toDateString() }}" required>
                <button type="submit" class="btn btn-primary">Exportar CSV</button>
            </div>
        </div>
    </form>

    <div class="d-flex flex-column gap-4">
        @foreach ($summary as $row)
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="fw-medium mb-0">{{ $row['member']->name }}</p>
                        <span class="gd-big-figure text-primary" style="font-size: 1.5rem;">
                            {{ sprintf('%dh %02dm', intdiv($row['total_minutes'], 60), $row['total_minutes'] % 60) }}
                        </span>
                    </div>

                    @if ($row['sessions']->isEmpty())
                        <p class="text-secondary mb-0">Sin jornadas esta semana.</p>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach ($row['sessions'] as $session)
                                <div x-data="{ editing: false }" class="border-top pt-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-0">
                                                {{ $session->work_date->translatedFormat('D j \d\e F') }} ·
                                                {{ $session->clocked_in_at->format('H:i') }} —
                                                {{ $session->clocked_out_at?->format('H:i') ?? 'en curso' }}
                                            </p>
                                            @if ($session->auto_closed)
                                                <p class="text-secondary mb-0">Cerrada automáticamente</p>
                                            @endif
                                            @if ($session->edited_by_member_id)
                                                <p class="text-secondary mb-0">Editada: {{ $session->edit_reason }}</p>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary" @click="editing = !editing">Editar</button>
                                    </div>

                                    <form method="POST" action="{{ route('work-sessions.update', $session) }}" x-show="editing" x-cloak class="mt-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label text-secondary">Entrada</label>
                                                <input type="datetime-local" name="clocked_in_at" class="form-control"
                                                       value="{{ $session->clocked_in_at->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary">Salida</label>
                                                <input type="datetime-local" name="clocked_out_at" class="form-control"
                                                       value="{{ $session->clocked_out_at?->format('Y-m-d\TH:i') }}">
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label text-secondary">Motivo del cambio (obligatorio)</label>
                                            <input type="text" name="edit_reason" class="form-control" maxlength="255" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Guardar corrección</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Hoy — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 34rem;">
    <p class="text-secondary mb-1">{{ $team->name }} · {{ now()->translatedFormat('l, j \d\e F') }}</p>
    <h1 class="mb-4">Hola, {{ $member->name }}</h1>

    @unless ($member->email_verified_at)
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3">
            <span>Vincula tu correo para no perder el acceso si pierdes este dispositivo.</span>
            <a href="{{ route('settings.show') }}" class="btn btn-outline-secondary text-nowrap">Vincular correo</a>
        </div>
    @endunless

    @php $completedCount = $tasks->whereNotNull('completed_at')->count(); @endphp

    <div class="d-flex gap-3 flex-wrap mb-4">
        <div class="card flex-fill">
            <div class="card-body">
                <p class="fw-medium mb-1">Tareas completadas</p>
                <p class="gd-big-figure text-primary mb-0">{{ $completedCount }} / {{ $tasks->count() }}</p>
            </div>
        </div>
        <div class="card flex-fill">
            <div class="card-body">
                <p class="fw-medium mb-1">Código de equipo</p>
                <p class="gd-big-figure text-primary mb-0">{{ $team->code }}</p>
                <p class="text-secondary mb-0">Compártelo con quien quieras sumar al equipo.</p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Gestionar tareas</a>
    </div>

    @if ($tasks->isEmpty())
        <p class="text-secondary">Aún no hay tareas hoy. Ve a "Gestionar tareas" para crear una.</p>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($tasks as $task)
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-start gap-3">
                        <div class="d-flex align-items-start gap-3">
                            @if ($task->photoUrl())
                                <a href="{{ $task->photoUrl() }}" target="_blank" rel="noopener">
                                    <img src="{{ $task->photoUrl() }}" alt="Foto de evidencia de {{ $task->title }}"
                                         width="56" height="56" style="object-fit: cover; border-radius: .5rem;">
                                </a>
                            @endif
                            <div>
                                <p class="fw-medium mb-1">{{ $task->title }}</p>
                                <p class="text-secondary mb-0">
                                    {{ $task->assignedMember->name ?? 'Cualquiera del equipo' }}
                                </p>
                            </div>
                        </div>
                        @if ($task->completed_at)
                            <span class="text-primary fw-medium text-nowrap">✓ {{ $task->completed_at->format('H:i') }}</span>
                        @else
                            <span class="text-secondary text-nowrap">Pendiente</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

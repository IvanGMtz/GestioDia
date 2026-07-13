@extends('layouts.app')

@section('title', 'Equipo — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 34rem;">
    <a href="{{ route('tasks.today') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Hoy</a>

    <h1 class="mb-4">Equipo</h1>

    <div class="card mb-4">
        <div class="card-body">
            <p class="fw-medium mb-1">Código de invitación</p>
            <p class="gd-big-figure text-primary mb-0">{{ $team->code }}</p>
            <p class="text-secondary mb-0">Compártelo con quien quieras sumar al equipo.</p>
        </div>
    </div>

    <a href="{{ route('work-sessions.weekly') }}" class="btn btn-outline-secondary btn-lg w-100 mb-4">Ver jornadas semanales</a>

    @if (session('recovery_link'))
        <div class="alert alert-success">
            <p class="fw-medium mb-2">Enlace de recuperación para {{ session('recovery_member') }}:</p>
            <p class="mb-2" style="word-break: break-all;">
                <code>{{ session('recovery_link') }}</code>
            </p>
            <p class="text-secondary mb-0">
                Válido 15 minutos y de un solo uso. Compárteselo por WhatsApp, SMS o en persona.
            </p>
        </div>
    @endif

    <h2 class="h4 mb-3">Miembros</h2>

    <div class="d-flex flex-column gap-2">
        @foreach ($members as $member)
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <p class="fw-medium mb-1">
                            {{ $member->name }}
                            <span class="text-secondary">— {{ $member->role->value === 'EMPLOYER' ? 'Empleador' : 'Empleado' }}</span>
                        </p>
                        <p class="text-secondary mb-0">
                            @if ($member->email && $member->email_verified_at)
                                Correo vinculado
                            @else
                                Sin correo vinculado
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('team.members.regenerate-access', $member) }}"
                          onsubmit="return confirm('¿Generar un enlace de recuperación de acceso para {{ $member->name }}?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Regenerar acceso</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

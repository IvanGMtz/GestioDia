@extends('layouts.app')

@section('title', 'Hoy — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 34rem;">
    <p class="text-secondary mb-1">{{ now()->translatedFormat('l, j \d\e F') }}</p>
    <h1 class="mb-4">Hola, {{ $member->name }}</h1>

    @if (session('completed_task'))
        <div class="alert alert-success fw-medium" role="alert">
            ✓ Tarea completada — {{ session('completed_at') }}
        </div>
    @endif

    @if ($tasks->isEmpty())
        <p class="text-secondary">Aún no hay tareas hoy.</p>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($tasks as $task)
                <div class="card">
                    <div class="card-body">
                        <p class="fw-medium mb-1">{{ $task->title }}</p>
                        @if ($task->description)
                            <p class="text-secondary mb-2">{{ $task->description }}</p>
                        @endif

                        @if ($task->completed_at)
                            <p class="text-primary fw-medium mb-0">✓ Completada — {{ $task->completed_at->format('H:i') }}</p>
                        @elseif ($task->requires_photo)
                            <p class="text-secondary mb-0">Requiere foto de evidencia (próximamente).</p>
                        @else
                            <form method="POST" action="{{ route('tasks.complete', $task) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg w-100">Completar</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

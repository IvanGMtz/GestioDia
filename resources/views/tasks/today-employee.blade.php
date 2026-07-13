@extends('layouts.app')

@section('title', 'Hoy — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 34rem;">
    <p class="text-secondary mb-1">{{ now()->translatedFormat('l, j \d\e F') }}</p>
    <h1 class="mb-4">Hola, {{ $member->name }}</h1>

    @if (session('clock_message'))
        <div class="alert alert-success text-center" role="alert">
            <p class="mb-0">✓</p>
            <p class="gd-big-figure text-primary mb-0">{{ session('clock_message') }}</p>
        </div>
    @endif

    @if (session('clock_error'))
        <div class="alert alert-danger" role="alert">{{ session('clock_error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body text-center">
            @if ($openSession)
                <p class="text-secondary mb-3">Jornada empezada a las {{ $openSession->clocked_in_at->format('H:i') }}</p>
                <form method="POST" action="{{ route('work-sessions.clock-out') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-lg w-100">Terminar jornada</button>
                </form>
            @else
                <form method="POST" action="{{ route('work-sessions.clock-in') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg w-100">Empezar jornada</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('completed_task'))
        <div class="alert alert-success fw-medium" role="alert">
            ✓ Tarea completada — {{ session('completed_at') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                            <form method="POST" action="{{ route('tasks.complete', $task) }}" enctype="multipart/form-data"
                                  x-data="{ uploading: false }" @submit="uploading = true">
                                @csrf
                                <label for="photo-{{ $task->id }}" class="form-label text-secondary">
                                    Esta tarea requiere una foto de evidencia
                                </label>
                                <input type="file" id="photo-{{ $task->id }}" name="photo" accept="image/*" capture="environment"
                                       class="form-control form-control-lg mb-3" required>
                                <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="uploading">
                                    <span x-show="!uploading">Completar con foto</span>
                                    <span x-show="uploading" x-cloak>Subiendo foto…</span>
                                </button>
                            </form>
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

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
                                  x-data="{ compressing: false }">
                                @csrf
                                <label for="photo-{{ $task->id }}" class="form-label text-secondary">
                                    Esta tarea requiere una foto de evidencia
                                </label>
                                <input type="file" id="photo-{{ $task->id }}" name="photo" accept="image/*" capture="environment"
                                       class="form-control form-control-lg mb-3" required
                                       @change="compressing = true; await window.compressPhotoInput($event.target); compressing = false">
                                <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="compressing">
                                    <span x-show="!compressing">Completar con foto</span>
                                    <span x-show="compressing" x-cloak>Comprimiendo foto…</span>
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

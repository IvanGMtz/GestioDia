@extends('layouts.app')

@section('title', 'Tareas — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 40rem;">
    <a href="{{ route('tasks.today') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Hoy</a>

    <h1 class="mb-4">Tareas</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Recurrentes</h2>
        <a href="{{ route('tasks.recurring.create') }}" class="btn btn-primary">Crear tarea recurrente</a>
    </div>

    @if ($recurringTasks->isEmpty())
        <p class="text-secondary mb-5">Aún no hay tareas recurrentes. Pulsa "Crear tarea recurrente" para añadir una.</p>
    @else
        <div class="d-flex flex-column gap-2 mb-5">
            @foreach ($recurringTasks as $recurringTask)
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <p class="fw-medium mb-1">{{ $recurringTask->title }}</p>
                            <p class="text-secondary mb-0">
                                {{ $recurringTask->assignedMember->name ?? 'Cualquiera del equipo' }}
                                @if ($recurringTask->requires_photo) · Requiere foto @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('tasks.recurring.edit', $recurringTask) }}" class="btn btn-outline-secondary">Editar</a>
                            <form method="POST" action="{{ route('tasks.recurring.destroy', $recurringTask) }}"
                                  onsubmit="return confirm('¿Desactivar esta tarea recurrente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-secondary">Desactivar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="h4 mb-3">Tarea puntual</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tasks.oneoff.store') }}" class="card mb-5">
        <div class="card-body">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label fw-medium">Título</label>
                <input type="text" class="form-control form-control-lg" id="title" name="title" maxlength="120" required>
            </div>

            <div class="mb-3">
                <label for="task_date" class="form-label fw-medium">Fecha</label>
                <input type="date" class="form-control form-control-lg" id="task_date" name="task_date"
                       value="{{ today()->toDateString() }}" required>
            </div>

            <div class="mb-3">
                <label for="assigned_member_id" class="form-label fw-medium">Asignar a</label>
                <select class="form-select form-select-lg" id="assigned_member_id" name="assigned_member_id">
                    <option value="">Cualquiera del equipo</option>
                    @foreach ($members as $memberOption)
                        <option value="{{ $memberOption->id }}">{{ $memberOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="requires_photo" name="requires_photo" value="1">
                <label class="form-check-label" for="requires_photo">Requiere foto de evidencia</label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Crear tarea puntual</button>
        </div>
    </form>

    @if ($oneOffTasks->isNotEmpty())
        <h2 class="h4 mb-3">Próximas tareas puntuales</h2>
        <div class="d-flex flex-column gap-2">
            @foreach ($oneOffTasks as $task)
                <div class="card">
                    <div class="card-body">
                        <p class="fw-medium mb-1">{{ $task->title }}</p>
                        <p class="text-secondary mb-0">
                            {{ \Illuminate\Support\Carbon::parse($task->task_date)->translatedFormat('j \d\e F') }} ·
                            {{ $task->assignedMember->name ?? 'Cualquiera del equipo' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

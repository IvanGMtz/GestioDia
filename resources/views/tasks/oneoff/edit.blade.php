@extends('layouts.app')

@section('title', 'Editar tarea puntual — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 30rem;">
    <a href="{{ route('tasks.index') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Tareas</a>

    <h1 class="mb-4">Editar tarea puntual</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $title = old('title', $task->title);
        $description = old('description', $task->description);
        $taskDate = old('task_date', $task->task_date->toDateString());
        $assignedMemberId = old('assigned_member_id', $task->assigned_member_id);
        $requiresPhoto = old('requires_photo', $task->requires_photo);
    @endphp

    <form method="POST" action="{{ route('tasks.oneoff.update', $task) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="title" class="form-label fw-medium">Título</label>
            <input type="text" class="form-control form-control-lg" id="title" name="title"
                   value="{{ $title }}" maxlength="120" required autofocus>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label fw-medium">Descripción (opcional)</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ $description }}</textarea>
        </div>

        <div class="mb-3">
            <label for="task_date" class="form-label fw-medium">Fecha</label>
            <input type="date" class="form-control form-control-lg" id="task_date" name="task_date"
                   value="{{ $taskDate }}" required>
        </div>

        <div class="mb-3">
            <label for="assigned_member_id" class="form-label fw-medium">Asignar a</label>
            <select class="form-select form-select-lg" id="assigned_member_id" name="assigned_member_id">
                <option value="">Cualquiera del equipo</option>
                @foreach ($members as $memberOption)
                    <option value="{{ $memberOption->id }}" @selected((string) $assignedMemberId === (string) $memberOption->id)>
                        {{ $memberOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" class="form-check-input" id="requires_photo" name="requires_photo" value="1" @checked($requiresPhoto)>
            <label class="form-check-label" for="requires_photo">Requiere foto de evidencia</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">Guardar cambios</button>
    </form>

    <form method="POST" action="{{ route('tasks.oneoff.destroy', $task) }}" class="mt-3"
          onsubmit="return confirm('¿Eliminar esta tarea puntual?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-secondary btn-lg w-100">Eliminar tarea</button>
    </form>
</div>
@endsection

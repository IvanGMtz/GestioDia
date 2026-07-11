@php
    $title = old('title', $recurringTask->title ?? '');
    $description = old('description', $recurringTask->description ?? '');
    $assignedMemberId = old('assigned_member_id', $recurringTask->assigned_member_id ?? '');
    $requiresPhoto = old('requires_photo', $recurringTask->requires_photo ?? false);
@endphp

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

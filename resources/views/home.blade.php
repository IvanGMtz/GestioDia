@extends('layouts.app')

@section('title', 'Hoy — GestioDia')

@section('content')
<div class="container py-5">
    <p class="text-secondary mb-1">{{ $team->name }}</p>
    <h1 class="mb-4">Hola, {{ $member->name }}</h1>

    <div class="card" style="max-width: 26rem;">
        <div class="card-body">
            <p class="fw-medium mb-1">Código de equipo</p>
            <p class="gd-big-figure text-primary mb-0">{{ $team->code }}</p>
        </div>
    </div>

    <p class="text-secondary mt-4">
        Aún no hay tareas ni fichaje disponibles — esa parte de la app llega en el siguiente milestone.
    </p>
</div>
@endsection

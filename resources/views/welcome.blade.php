@extends('layouts.app')

@section('title', 'GestioDia — Simple. Útil. Hecho.')

@section('content')
<div class="container py-5">
    <img src="/brand/logo-horizontal.svg" alt="GestioDia" height="60" class="mb-4">

    <h1 class="mb-3">Simple. Útil. Hecho.</h1>
    <p class="text-secondary mb-4" style="max-width: 40rem;">
        Todo lo que necesitas para gestionar el día con tu equipo.
    </p>

    <div class="d-flex gap-3 flex-wrap mb-5">
        <button type="button" class="btn btn-primary btn-lg">Crear equipo</button>
        <button type="button" class="btn btn-outline-secondary btn-lg">Unirse a un equipo</button>
    </div>

    <div class="card" style="max-width: 22rem;">
        <div class="card-body">
            <p class="fw-medium mb-1">Jornada de hoy</p>
            <p class="gd-big-figure text-primary mb-0">6h 30m</p>
        </div>
    </div>
</div>
@endsection

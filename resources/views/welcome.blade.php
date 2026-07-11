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
        <a href="{{ route('team.create.show') }}" class="btn btn-primary btn-lg">Crear equipo</a>
        <a href="{{ route('team.join.show') }}" class="btn btn-outline-secondary btn-lg">Unirse a un equipo</a>
    </div>
</div>
@endsection

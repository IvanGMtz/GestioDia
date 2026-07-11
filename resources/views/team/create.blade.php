@extends('layouts.app')

@section('title', 'Crear equipo — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 30rem;">
    <a href="{{ route('home') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Volver</a>

    <h1 class="mb-4">Crear tu equipo</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('team.create.store') }}">
        @csrf

        <div class="mb-3">
            <label for="business_name" class="form-label fw-medium">Nombre del negocio o equipo</label>
            <input type="text" class="form-control form-control-lg" id="business_name" name="business_name"
                   value="{{ old('business_name') }}" maxlength="80" required autofocus>
        </div>

        <div class="mb-4">
            <label for="owner_name" class="form-label fw-medium">Tu nombre</label>
            <input type="text" class="form-control form-control-lg" id="owner_name" name="owner_name"
                   value="{{ old('owner_name') }}" maxlength="60" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">Crear mi equipo</button>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Unirse a un equipo — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 30rem;">
    <a href="{{ route('home') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Volver</a>

    <h1 class="mb-4">Unirse a un equipo</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('team.join.store') }}">
        @csrf

        <div class="mb-3">
            <label for="code" class="form-label fw-medium">Código de equipo</label>
            <input type="text" class="form-control form-control-lg text-uppercase" id="code" name="code"
                   value="{{ old('code') }}" placeholder="JARDIN-4832" required autofocus>
            <div class="form-text">Te lo debe dar la persona que creó el equipo.</div>
        </div>

        <div class="mb-4">
            <label for="member_name" class="form-label fw-medium">Tu nombre</label>
            <input type="text" class="form-control form-control-lg" id="member_name" name="member_name"
                   value="{{ old('member_name') }}" maxlength="60" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">Unirme al equipo</button>
    </form>
</div>
@endsection

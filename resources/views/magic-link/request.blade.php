@extends('layouts.app')

@section('title', 'Entrar con tu correo — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 30rem;">
    <a href="{{ route('home') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Volver</a>

    <h1 class="mb-3">Entrar con tu correo</h1>
    <p class="text-secondary mb-4">
        Solo funciona si ya vinculaste tu correo desde otro dispositivo, en Ajustes.
    </p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('magic-link.request.store') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label fw-medium">Tu correo</label>
            <input type="email" class="form-control form-control-lg" id="email" name="email" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">Enviarme el enlace</button>
    </form>
</div>
@endsection

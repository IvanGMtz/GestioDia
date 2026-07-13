@extends('layouts.app')

@section('title', 'Ajustes — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 30rem;">
    <a href="{{ route('tasks.today') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Hoy</a>

    <h1 class="mb-4">Ajustes</h1>

    <div class="card mb-4">
        <div class="card-body">
            <p class="fw-medium mb-1">{{ $member->name }}</p>
            <p class="text-secondary mb-0">{{ $member->role->value === 'EMPLOYER' ? 'Empleador' : 'Empleado' }}</p>
        </div>
    </div>

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

    <h2 class="h4 mb-3">Vincular correo</h2>

    @if ($member->email && $member->email_verified_at)
        <p class="text-secondary mb-3">
            Correo vinculado: <strong class="text-primary">{{ $member->email }}</strong>.
            Úsalo para entrar desde otro dispositivo en <a href="{{ route('magic-link.request.show') }}">Entrar con tu correo</a>.
        </p>
    @else
        <p class="text-secondary mb-3">
            Vincula tu correo para poder entrar desde otro dispositivo si pierdes o cambias el tuyo.
            @if ($member->email && ! $member->email_verified_at)
                Te enviamos un enlace a <strong>{{ $member->email }}</strong> — ábrelo para confirmarlo.
            @endif
        </p>
        <form method="POST" action="{{ route('settings.link-email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label fw-medium">Tu correo</label>
                <input type="email" class="form-control form-control-lg" id="email" name="email"
                       value="{{ old('email', $member->email) }}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Vincular correo</button>
        </form>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'Crear tarea recurrente — GestioDia')

@section('content')
<div class="container py-5" style="max-width: 30rem;">
    <a href="{{ route('tasks.index') }}" class="text-secondary text-decoration-none d-inline-block mb-4">&larr; Tareas</a>

    <h1 class="mb-4">Crear tarea recurrente</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tasks.recurring.store') }}">
        @csrf
        @include('tasks.recurring._form', ['recurringTask' => null])

        <button type="submit" class="btn btn-primary btn-lg w-100">Crear tarea</button>
    </form>
</div>
@endsection

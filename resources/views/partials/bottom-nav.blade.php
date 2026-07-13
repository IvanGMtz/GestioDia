@php
    $navMember = app(\App\Models\Member::class);
    $isEmployer = $navMember->role === \App\Enums\MemberRole::Employer;
@endphp
<nav class="navbar fixed-bottom bg-white border-top py-2" style="box-shadow: 0 -2px 8px rgba(0,0,0,.06);">
    <div class="container-fluid d-flex justify-content-around">
        <a href="{{ route('tasks.today') }}"
           class="text-decoration-none text-center {{ request()->routeIs('tasks.today') ? 'text-primary' : 'text-secondary' }}">
            <div>🏠</div>
            <div class="small fw-medium">Hoy</div>
        </a>

        @if ($isEmployer)
            <a href="{{ route('tasks.index') }}"
               class="text-decoration-none text-center {{ request()->routeIs('tasks.index') || request()->routeIs('tasks.recurring.*') || request()->routeIs('tasks.oneoff.*') ? 'text-primary' : 'text-secondary' }}">
                <div>📋</div>
                <div class="small fw-medium">Tareas</div>
            </a>
            <a href="{{ route('team.members.index') }}"
               class="text-decoration-none text-center {{ request()->routeIs('team.members.index') || request()->routeIs('work-sessions.weekly') ? 'text-primary' : 'text-secondary' }}">
                <div>👥</div>
                <div class="small fw-medium">Equipo</div>
            </a>
        @else
            <a href="{{ route('work-sessions.mine') }}"
               class="text-decoration-none text-center {{ request()->routeIs('work-sessions.mine') ? 'text-primary' : 'text-secondary' }}">
                <div>🗓️</div>
                <div class="small fw-medium">Mi semana</div>
            </a>
            <a href="{{ route('settings.show') }}"
               class="text-decoration-none text-center {{ request()->routeIs('settings.show') ? 'text-primary' : 'text-secondary' }}">
                <div>⚙️</div>
                <div class="small fw-medium">Ajustes</div>
            </a>
        @endif
    </div>
</nav>

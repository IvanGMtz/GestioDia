@php $topBarMember = app(\App\Models\Member::class); @endphp
@if ($topBarMember->role === \App\Enums\MemberRole::Employer)
    <div class="d-flex justify-content-end p-3">
        <a href="{{ route('settings.show') }}" class="text-decoration-none text-secondary fs-4" aria-label="Ajustes">⚙️</a>
    </div>
@endif

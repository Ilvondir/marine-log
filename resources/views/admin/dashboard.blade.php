@extends('layouts.marine')

@section('title', 'Admin — Dashboard')

@section('content')
    @include('admin.partials.navigation')

    <p class="marine-kicker">Admin area</p>
    <h1 style="font-size: clamp(1.8rem, 4vw, 2.5rem); margin: 0.5rem 0 2rem;">Operations Harbor</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
        <div class="marine-hero__panel" style="padding: 1.25rem;">
            <span class="marine-kicker">Observations</span>
            <p style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0 0;">{{ \App\Models\Observation::count() }}</p>
        </div>
        <div class="marine-hero__panel" style="padding: 1.25rem;">
            <span class="marine-kicker">Users</span>
            <p style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0 0;">{{ \App\Models\User::count() }}</p>
        </div>
        <div class="marine-hero__panel" style="padding: 1.25rem;">
            <span class="marine-kicker">Blocked</span>
            <p style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0 0; color: #fca5a5;">{{ \App\Models\User::whereNotNull('blocked_at')->count() }}</p>
        </div>
    </div>

    <div class="marine-panel" style="max-width: 36rem;">
        <h2 style="font-size: 1.1rem; margin-bottom: 1rem;">Quick actions</h2>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('admin.observations.index') }}" class="marine-button">Manage observations</a>
            <a href="{{ route('admin.users.index') }}" class="marine-button marine-button--ghost">Manage users</a>
        </div>
    </div>
@endsection

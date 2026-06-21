@extends('layouts.marine')

@section('content')
    <section class="marine-hero">
        <div class="marine-hero__copy">
            <p class="marine-kicker">MarineLog MVP</p>
            @guest
                <h1>Track wildlife observations with a calmer, ocean-inspired interface.</h1>
                <p class="marine-lead">
                    MarineLog helps divers and wildlife watchers record marine and freshwater sightings with the
                    right context, then share them in a public feed that feels built for the water.
                </p>

                <div class="marine-hero__actions">
                    <a href="{{ route('register') }}" class="marine-button">Create account</a>
                    <a href="{{ route('login') }}" class="marine-button marine-button--ghost">Sign in</a>
                </div>

                <dl class="marine-metrics" aria-label="Product highlights">
                    <div>
                        <dt>Public view</dt>
                        <dd>Anyone can browse sightings</dd>
                    </div>
                    <div>
                        <dt>Auth ready</dt>
                        <dd>Sign in, register, and sign out through the same shell</dd>
                    </div>
                    <div>
                        <dt>Roles</dt>
                        <dd>User and Admin access are built into the scaffold</dd>
                    </div>
                </dl>
            @endguest

            @auth
                <h1>Welcome back, {{ auth()->user()->name }}.</h1>
                <p class="marine-lead">
                    You are signed in as
                    <strong>{{ auth()->user()->role?->name ?? 'User' }}</strong>,
                    so the product now shifts from onboarding into the working MarineLog experience.
                </p>

                <div class="marine-hero__actions">
                    @if (auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="marine-button">Open admin area</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post" class="marine-inline-form">
                        @csrf
                        <button type="submit" class="marine-button marine-button--ghost">Sign out</button>
                    </form>
                </div>

                <dl class="marine-metrics" aria-label="Signed in status">
                    <div>
                        <dt>Signed in as</dt>
                        <dd>{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt>Role</dt>
                        <dd>{{ auth()->user()->role?->name ?? 'User' }}</dd>
                    </div>
                    <div>
                        <dt>Access</dt>
                        <dd>
                            {{ auth()->user()?->isAdmin() ? 'Admin tools are available' : 'Public and user areas are available' }}
                        </dd>
                    </div>
                </dl>
            @endauth
        </div>

        <aside class="marine-hero__panel" aria-label="MarineLog preview">
            <div class="marine-panel">
                @guest
                    <p class="marine-panel__eyebrow">Coming foundation</p>
                    <h2>One shared shell for public and auth screens</h2>
                    <p>
                        The app now has a branded starting point, so the auth screens and admin surface inherit
                        the same atmosphere instead of feeling like separate pages.
                    </p>

                    <ul class="marine-panel__list">
                        <li>English-only copy</li>
                        <li>Blade + session flow</li>
                        <li>User and Admin roles</li>
                        <li>Marine color palette</li>
                    </ul>
                @endguest

                @auth
                    <p class="marine-panel__eyebrow">Session active</p>
                    <h2>Your account is live in the current session</h2>
                    <p>
                        This screen now adapts to the signed-in user so you can immediately tell who is logged in
                        and which areas they can reach.
                    </p>

                    <ul class="marine-panel__list">
                        <li>{{ auth()->user()->name }}</li>
                        <li>{{ auth()->user()->role?->name ?? 'User' }} role</li>
                        <li>
                            {{ auth()->user()?->isAdmin() ? 'Admin dashboard unlocked' : 'Standard user access' }}
                        </li>
                    </ul>
                @endauth
            </div>
        </aside>
    </section>
@endsection

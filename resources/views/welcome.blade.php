@extends('layouts.marine')

@section('content')
    <section class="marine-hero">
        <div class="marine-hero__copy">
            <p class="marine-kicker">MarineLog MVP</p>
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
                    <dd>Registration comes next</dd>
                </div>
                <div>
                    <dt>Theme</dt>
                    <dd>Ocean-first visual system</dd>
                </div>
            </dl>
        </div>

        <aside class="marine-hero__panel" aria-label="MarineLog preview">
            <div class="marine-panel">
                <p class="marine-panel__eyebrow">Coming foundation</p>
                <h2>One shared shell for public and auth screens</h2>
                <p>
                    The app now has a branded starting point, so the next auth screens can inherit the same
                    atmosphere instead of feeling like separate pages.
                </p>

                <ul class="marine-panel__list">
                    <li>English-only copy</li>
                    <li>Blade + session flow</li>
                    <li>Marine color palette</li>
                </ul>
            </div>
        </aside>
    </section>
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#05111f">

        <title>{{ config('app.name', 'MarineLog') }}</title>

        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                :root {
                    --marine-ink: #05111f;
                    --marine-foam: #f6fbfc;
                    --marine-border: rgba(246, 251, 252, 0.12);
                }

                * {
                    box-sizing: border-box;
                }

                html,
                body {
                    min-height: 100%;
                    margin: 0;
                    font-family: ui-sans-serif, system-ui, sans-serif;
                    color: var(--marine-foam);
                    background:
                        radial-gradient(circle at top left, rgba(17, 122, 139, 0.22), transparent 30%),
                        radial-gradient(circle at top right, rgba(77, 196, 255, 0.18), transparent 28%),
                        linear-gradient(180deg, #07131f 0%, var(--marine-ink) 45%, #030b14 100%);
                }

                a {
                    color: inherit;
                    text-decoration: none;
                }

                .marine-shell {
                    position: relative;
                    overflow-x: hidden;
                }

                .marine-shell__frame {
                    position: relative;
                    z-index: 1;
                    min-height: 100vh;
                    padding: 1.25rem;
                }

                .marine-topbar,
                .marine-footer {
                    display: flex;
                    justify-content: space-between;
                    gap: 1rem;
                    flex-wrap: wrap;
                }

                .marine-topbar {
                    align-items: center;
                    padding: 0.75rem 0 1.5rem;
                }

                .marine-brand {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.875rem;
                }

                .marine-brand__mark,
                .marine-button,
                .marine-navlink,
                .marine-hero__copy,
                .marine-hero__panel,
                .marine-panel {
                    border: 1px solid var(--marine-border);
                    border-radius: 1.5rem;
                    background: rgba(6, 18, 31, 0.72);
                }

                .marine-brand__mark {
                    display: grid;
                    height: 2.75rem;
                    width: 2.75rem;
                    place-items: center;
                    border-radius: 9999px;
                    background: linear-gradient(135deg, rgba(17, 122, 139, 0.95), rgba(13, 59, 102, 0.95));
                    font-weight: 800;
                }

                .marine-brand__text {
                    display: grid;
                    gap: 0.125rem;
                }

                .marine-brand__name {
                    font-weight: 700;
                }

                .marine-brand__tagline,
                .marine-footer,
                .marine-lead,
                .marine-panel p,
                .marine-auth__note {
                    color: rgba(246, 251, 252, 0.8);
                }

                .marine-topbar__nav {
                    display: flex;
                    gap: 0.875rem;
                    flex-wrap: wrap;
                }

                .marine-userchip {
                    display: grid;
                    gap: 0.1rem;
                    padding: 0.7rem 1rem;
                    border-radius: 1rem;
                    border: 1px solid rgba(77, 196, 255, 0.24);
                    background: rgba(77, 196, 255, 0.08);
                    min-width: 10rem;
                }

                .marine-userchip__label,
                .marine-userchip__role {
                    font-size: 0.72rem;
                    text-transform: uppercase;
                    letter-spacing: 0.16em;
                    color: rgba(246, 251, 252, 0.68);
                }

                .marine-userchip__name {
                    font-weight: 700;
                    color: var(--marine-foam);
                }

                .marine-inline-form {
                    display: inline-flex;
                }

                .marine-button,
                .marine-navlink {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0.85rem 1.2rem;
                    border-radius: 9999px;
                    color: var(--marine-foam);
                }

                .marine-navbutton {
                    cursor: pointer;
                    font: inherit;
                    appearance: none;
                    -webkit-appearance: none;
                    border: 1px solid rgba(246, 251, 252, 0.16);
                }

                .marine-button {
                    background: linear-gradient(135deg, rgba(17, 122, 139, 0.95), rgba(13, 59, 102, 0.95));
                    font-weight: 700;
                }

                .marine-button--ghost,
                .marine-navlink {
                    background: rgba(246, 251, 252, 0.04);
                }

                .marine-main {
                    padding: 1rem 0 2rem;
                }

                .marine-hero {
                    display: grid;
                    gap: 1.5rem;
                }

                .marine-hero__copy,
                .marine-hero__panel,
                .marine-panel {
                    padding: 1.5rem;
                    box-shadow: 0 30px 80px rgba(3, 11, 20, 0.34);
                    backdrop-filter: blur(18px);
                }

                .marine-kicker {
                    text-transform: uppercase;
                    letter-spacing: 0.24em;
                    font-size: 0.75rem;
                    color: rgba(77, 196, 255, 0.82);
                }

                .marine-hero h1,
                .marine-auth h1,
                .marine-panel h2 {
                    line-height: 1.05;
                    margin: 0;
                }

                .marine-hero h1 {
                    max-width: 12ch;
                    font-size: clamp(2.5rem, 6vw, 5rem);
                }

                .marine-hero__actions {
                    display: flex;
                    gap: 0.875rem;
                    flex-wrap: wrap;
                    margin-top: 1.75rem;
                }

                .marine-metrics,
                .marine-panel__list {
                    display: grid;
                    gap: 0.75rem;
                }

                .marine-metrics {
                    margin-top: 1.75rem;
                }

                .marine-metrics > div {
                    padding-top: 0.75rem;
                    border-top: 1px solid var(--marine-border);
                }

                .marine-auth {
                    display: grid;
                    place-items: center;
                    min-height: calc(100vh - 10rem);
                }

                .marine-auth__note {
                    display: inline-flex;
                    margin-top: 1.25rem;
                    padding: 0.75rem 1rem;
                    border-radius: 9999px;
                    background: rgba(77, 196, 255, 0.12);
                }

                .marine-form {
                    display: grid;
                    gap: 1rem;
                    margin-top: 1.5rem;
                }

                .marine-field {
                    display: grid;
                    gap: 0.45rem;
                }

                .marine-label {
                    font-size: 0.9rem;
                    font-weight: 600;
                    color: rgba(246, 251, 252, 0.88);
                }

                .marine-input {
                    width: 100%;
                    border: 1px solid rgba(246, 251, 252, 0.12);
                    border-radius: 1rem;
                    background: rgba(246, 251, 252, 0.04);
                    padding: 0.95rem 1rem;
                    color: var(--marine-foam);
                    outline: none;
                }

                .marine-form__actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.875rem;
                    margin-top: 0.25rem;
                    align-items: center;
                }

                .marine-form__actions .marine-button,
                .marine-form__actions .marine-navlink {
                    min-width: 10rem;
                }

                .marine-error {
                    border-radius: 1rem;
                    border: 1px solid rgba(248, 113, 113, 0.28);
                    background: rgba(248, 113, 113, 0.08);
                    padding: 0.9rem 1rem;
                    color: #fecaca;
                }

                @media (min-width: 48rem) {
                    .marine-hero {
                        grid-template-columns: minmax(0, 1.6fr) minmax(19rem, 1fr);
                    }
                }
            </style>
        @endif
    </head>
    <body class="marine-shell">
        <div class="marine-shell__glow marine-shell__glow--left" aria-hidden="true"></div>
        <div class="marine-shell__glow marine-shell__glow--right" aria-hidden="true"></div>

        <div class="marine-shell__frame">
            <header class="marine-topbar">
                <a class="marine-brand" href="{{ route('home') }}">
                    <span class="marine-brand__mark">M</span>
                    <span class="marine-brand__text">
                        <span class="marine-brand__name">MarineLog</span>
                        <span class="marine-brand__tagline">Observations from beneath the surface</span>
                    </span>
                </a>

                <nav class="marine-topbar__nav" aria-label="Primary">
                    @guest
                        <a href="{{ route('login') }}" class="marine-navlink">Sign in</a>
                        <a href="{{ route('register') }}" class="marine-button marine-button--small">Create account</a>
                    @endguest

                    @auth
                        @if (auth()->user()?->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="marine-navlink">Admin area</a>
                        @endif

                        <a href="{{ route('observations.create') }}" class="marine-button marine-button--small">New observation</a>
                        <a href="{{ route('home') }}" class="marine-navlink">Home</a>

                        <form action="{{ route('logout') }}" method="post" class="marine-inline-form">
                            @csrf
                            <button type="submit" class="marine-navlink marine-navbutton">Sign out</button>
                        </form>
                    @endauth
                </nav>
            </header>

            <main class="marine-main">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            <footer class="marine-footer">
                <span>Built for marine and freshwater wildlife observations.</span>
                <span>English-only product shell</span>
            </footer>
        </div>
    </body>
</html>

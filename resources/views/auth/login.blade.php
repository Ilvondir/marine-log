@extends('layouts.marine')

@section('content')
    <section class="marine-auth">
        <div class="marine-panel marine-auth__panel">
            <p class="marine-kicker">Sign in</p>
            <h1>Welcome back to MarineLog</h1>
            <p>
                Use your account to continue into the ocean-themed product shell and the admin areas your role
                allows.
            </p>

            @if ($errors->any())
                <div class="marine-error" role="alert">
                    <strong>Please check the form and try again.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="marine-form" method="post" action="{{ route('login.store') }}">
                @csrf

                <label class="marine-field">
                    <span class="marine-label">Email address</span>
                    <input
                        class="marine-input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="name@example.com"
                        required
                        autofocus
                    >
                </label>

                <label class="marine-field">
                    <span class="marine-label">Password</span>
                    <input
                        class="marine-input"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </label>

                <div class="marine-form__actions">
                    <button type="submit" class="marine-button">Sign in</button>
                    <a href="{{ route('register') }}" class="marine-navlink">Create account</a>
                </div>
            </form>
        </div>
    </section>
@endsection

@extends('layouts.marine')

@section('content')
    <section class="marine-auth">
        <div class="marine-panel marine-auth__panel">
            <p class="marine-kicker">Create account</p>
            <h1>Join MarineLog</h1>
            <p>
                Create an English-only account to start using the marine-themed product shell and the default
                User role.
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

            <form class="marine-form" method="post" action="{{ route('register.store') }}">
                @csrf

                <label class="marine-field">
                    <span class="marine-label">Name</span>
                    <input
                        class="marine-input"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Your name"
                        required
                        autofocus
                    >
                </label>

                <label class="marine-field">
                    <span class="marine-label">Email address</span>
                    <input
                        class="marine-input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="name@example.com"
                        required
                    >
                </label>

                <label class="marine-field">
                    <span class="marine-label">Password</span>
                    <input
                        class="marine-input"
                        type="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >
                </label>

                <label class="marine-field">
                    <span class="marine-label">Confirm password</span>
                    <input
                        class="marine-input"
                        type="password"
                        name="password_confirmation"
                        placeholder="Repeat your password"
                        required
                    >
                </label>

                <div class="marine-form__actions">
                    <button type="submit" class="marine-button">Create account</button>
                    <a href="{{ route('login') }}" class="marine-navlink">Sign in</a>
                </div>
            </form>
        </div>
    </section>
@endsection

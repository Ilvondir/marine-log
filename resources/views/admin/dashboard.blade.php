@extends('layouts.marine')

@section('content')
    <section class="marine-auth">
        <div class="marine-panel marine-auth__panel">
            <p class="marine-kicker">Admin area</p>
            <h1>Operations harbor</h1>
            <p>
                This protected surface confirms the role boundary is working. Admin users can reach it, while
                regular users are blocked.
            </p>

            <div class="marine-auth__note">
                Admin-only access is active
            </div>
        </div>
    </section>
@endsection

@extends('layouts.marine')

@section('content')
    <section>
        <p class="marine-kicker">Your collection</p>
        <h1>My Favorites</h1>

        @if ($observations->count())
            <div class="marine-card-grid">
                {{-- Cards will be added in Phase 5 --}}
            </div>
        @else
            <p>You haven't favorited any observations yet.</p>
        @endif
    </section>
@endsection

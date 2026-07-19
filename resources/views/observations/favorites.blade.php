@extends('layouts.marine')

@section('content')
    <section>
        <p class="marine-kicker">Your collection</p>
        <h1 style="font-size: clamp(2rem, 5vw, 3.5rem); margin: 0.25rem 0 0; line-height: 1.1;">My Favorites</h1>

        @if ($observations->count())
            <div class="marine-card-grid">
                @foreach ($observations as $observation)
                    <a href="{{ route('observations.show', $observation) }}" class="marine-card">
                        @if ($observation->photos->first())
                            <img
                                src="{{ asset('storage/' . $observation->photos->first()->path) }}"
                                alt="{{ $observation->species }} observation photo"
                                class="marine-card__thumb"
                                loading="lazy"
                            >
                        @else
                            <div class="marine-card__thumb" style="background: rgba(17,122,139,0.15); display: grid; place-items: center; color: rgba(246,251,252,0.3); font-size: 2rem;">
                                🐠
                            </div>
                        @endif
                        <div class="marine-card__body">
                            <span class="marine-card__species">{{ $observation->species }}</span>
                            <span class="marine-card__meta">{{ $observation->observed_at->format('M j, Y') }}</span>
                            <span class="marine-card__meta">📍 {{ $observation->location_name }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($observations->hasPages())
                <nav class="marine-pagination" aria-label="Pagination">
                    @if ($observations->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $observations->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach ($observations->getUrlRange(1, $observations->lastPage()) as $page => $url)
                        @if ($page == $observations->currentPage())
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($observations->hasMorePages())
                        <a href="{{ $observations->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </nav>
            @endif
        @else
            <p style="margin-top: 2rem; color: rgba(246,251,252,0.6);">You have not favorited any observations yet.</p>
        @endif
    </section>
@endsection

@extends('layouts.marine')

@section('content')
    <section>
        <p class="marine-kicker">Explore the feed</p>
        <h1 style="font-size: clamp(2rem, 5vw, 3.5rem); margin: 0.25rem 0 0; line-height: 1.1;">Observations</h1>

        @if ($observations->count())
            {{-- Map panel --}}
            <div class="marine-map-panel">
                <div id="feed-map" class="marine-map-panel__map"></div>
            </div>

            {{-- Card grid --}}
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
                            <span class="marine-card__favorite">
                                @include('observations.partials.favorite-button', [
                                    'observation' => $observation,
                                    'isFavorited' => in_array($observation->id, $favoritedIds ?? []),
                                    'favoritesCount' => $observation->favorited_by_count ?? 0,
                                ])
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($observations->hasPages())
                <nav class="marine-pagination" aria-label="Pagination">
                    {{-- Previous --}}
                    @if ($observations->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $observations->previousPageUrl() }}">&laquo;</a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach ($observations->getUrlRange(1, $observations->lastPage()) as $page => $url)
                        @if ($page == $observations->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if ($observations->hasMorePages())
                        <a href="{{ $observations->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </nav>
            @endif
        @else
            {{-- Empty state --}}
            <div class="marine-empty">
                <div class="marine-empty__icon">🌊</div>
                <p class="marine-empty__text">No observations yet. The ocean awaits its first entry.</p>
                @auth
                    <a href="{{ route('observations.create') }}" class="marine-button" style="margin-top: 1.25rem;">Publish your first observation</a>
                @endauth
            </div>
        @endif
    </section>

    {{-- Leaflet (only if we have observations) --}}
    @if ($observations->count())
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @php
                    $mapData = $observations->getCollection()->map(fn ($o) => [
                        'id' => $o->id,
                        'species' => $o->species,
                        'lat' => (float) $o->latitude,
                        'lng' => (float) $o->longitude,
                        'url' => route('observations.show', $o),
                    ]);
                @endphp
                const observations = @json($mapData);

                const map = L.map('feed-map').setView([30, 0], 2);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19,
                }).addTo(map);

                const bounds = L.latLngBounds();

                observations.forEach(function (obs) {
                    const marker = L.marker([obs.lat, obs.lng]).addTo(map);
                    const link = document.createElement('a');
                    link.href = obs.url;
                    link.style.fontWeight = '600';
                    link.textContent = obs.species;
                    marker.bindPopup(link);
                    bounds.extend([obs.lat, obs.lng]);
                });

                if (observations.length > 0) {
                    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
                }
            });
        </script>
    @endif
@endsection

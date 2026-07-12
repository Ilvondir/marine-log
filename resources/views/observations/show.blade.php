@extends('layouts.marine')

@section('content')
    <section class="marine-auth">
        <div class="marine-panel" style="max-width: 52rem; width: 100%;">
            @if (session('success'))
                <div style="border-radius: 1rem; border: 1px solid rgba(74, 222, 128, 0.28); background: rgba(74, 222, 128, 0.08); padding: 0.9rem 1rem; color: #bbf7d0; margin-bottom: 1.25rem;" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <p class="marine-kicker">Observation #{{ $observation->id }}</p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2.5rem);">{{ $observation->species }}</h1>

            {{-- Meta --}}
            <div class="marine-metrics">
                <div>
                    <small style="color: rgba(246,251,252,0.6);">Observer</small>
                    <p style="margin: 0;">{{ $observation->user->name }}</p>
                </div>
                <div>
                    <small style="color: rgba(246,251,252,0.6);">Date &amp; time</small>
                    <p style="margin: 0;">{{ $observation->observed_at->format('M j, Y \a\t H:i') }}</p>
                </div>
                <div>
                    <small style="color: rgba(246,251,252,0.6);">Location</small>
                    <p style="margin: 0;">{{ $observation->location_name }} ({{ $observation->latitude }}, {{ $observation->longitude }})</p>
                </div>

                @if ($observation->description)
                    <div>
                        <small style="color: rgba(246,251,252,0.6);">Description</small>
                        <p style="margin: 0;">{{ $observation->description }}</p>
                    </div>
                @endif

                @if ($observation->water_temperature)
                    <div>
                        <small style="color: rgba(246,251,252,0.6);">Water temperature</small>
                        <p style="margin: 0;">{{ $observation->water_temperature }} °C</p>
                    </div>
                @endif

                @if ($observation->depth_meters)
                    <div>
                        <small style="color: rgba(246,251,252,0.6);">Depth</small>
                        <p style="margin: 0;">{{ $observation->depth_meters }} m</p>
                    </div>
                @endif

                @if ($observation->weather)
                    <div>
                        <small style="color: rgba(246,251,252,0.6);">Weather</small>
                        <p style="margin: 0;">{{ $observation->weather }}</p>
                    </div>
                @endif
            </div>

            {{-- Photos --}}
            @if ($observation->photos->count())
                <div style="margin-top: 1.5rem;">
                    <span class="marine-label">Photos</span>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: 0.75rem; margin-top: 0.75rem;">
                        @foreach ($observation->photos as $photo)
                            <img
                                src="{{ asset('storage/' . $photo->path) }}"
                                alt="Observation photo"
                                style="width: 100%; border-radius: 0.75rem; border: 1px solid var(--marine-border); aspect-ratio: 4/3; object-fit: cover;"
                                loading="lazy"
                            >
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Videos --}}
            @if ($observation->videos->count())
                <div style="margin-top: 1.5rem;">
                    <span class="marine-label">Videos</span>
                    <div style="display: grid; gap: 0.75rem; margin-top: 0.75rem;">
                        @foreach ($observation->videos as $video)
                            <video
                                controls
                                style="width: 100%; border-radius: 0.75rem; border: 1px solid var(--marine-border);"
                                preload="metadata"
                            >
                                <source src="{{ asset('storage/' . $video->path) }}" type="{{ $video->mime_type }}">
                                Your browser does not support the video tag.
                            </video>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Map --}}
            <div style="margin-top: 1.5rem;">
                <span class="marine-label">Location on map</span>
                <div id="show-map" style="height: 240px; border-radius: 1rem; border: 1px solid var(--marine-border); margin-top: 0.75rem; z-index: 0;"></div>
            </div>

            <div class="marine-form__actions" style="margin-top: 1.5rem;">
                @can('update', $observation)
                    <a href="{{ route('observations.edit', $observation) }}" class="marine-button">Edit observation</a>
                @endcan
                <a href="{{ route('observations.create') }}" class="marine-button" style="background: transparent; border-color: var(--marine-border);">New observation</a>
                <a href="{{ route('home') }}" class="marine-navlink">Back to home</a>
            </div>

            @can('delete', $observation)
                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--marine-border);">
                    <form method="post" action="{{ route('observations.destroy', $observation) }}" id="delete-form">
                        @csrf
                        @method('DELETE')
                        <button
                            type="button"
                            onclick="if(confirm('Are you sure you want to delete this observation? This action cannot be undone.')){document.getElementById('delete-form').submit();}"
                            class="marine-button"
                            style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5;"
                        >
                            Delete observation
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </section>

    {{-- Leaflet CSS & JS from CDN --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lat = {{ $observation->latitude }};
            const lng = {{ $observation->longitude }};

            const map = L.map('show-map').setView([lat, lng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup(@js($observation->species))
                .openPopup();
        });
    </script>
@endsection

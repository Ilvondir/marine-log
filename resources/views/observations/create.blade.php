@extends('layouts.marine')

@section('content')
    <section class="marine-auth">
        <div class="marine-panel" style="max-width: 52rem; width: 100%;">
            <p class="marine-kicker">New observation</p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2.5rem);">Publish a wildlife observation</h1>
            <p>Fill in the required fields and add at least one photo to share your sighting with the community.</p>

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

            <form class="marine-form" method="post" action="{{ route('observations.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Species --}}
                <label class="marine-field">
                    <span class="marine-label">Species *</span>
                    <input
                        class="marine-input"
                        type="text"
                        name="species"
                        value="{{ old('species') }}"
                        placeholder="e.g. Chelonia mydas (Green sea turtle)"
                        required
                    >
                    @error('species')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Date and time --}}
                <label class="marine-field">
                    <span class="marine-label">Date and time of observation *</span>
                    <input
                        class="marine-input"
                        type="datetime-local"
                        name="observed_at"
                        value="{{ old('observed_at') }}"
                        max="{{ now()->format('Y-m-d\TH:i') }}"
                        required
                    >
                    @error('observed_at')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Location name --}}
                <label class="marine-field">
                    <span class="marine-label">Location name *</span>
                    <input
                        class="marine-input"
                        type="text"
                        name="location_name"
                        value="{{ old('location_name') }}"
                        placeholder="e.g. Great Barrier Reef, Coral Bay"
                        required
                    >
                    @error('location_name')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Map picker --}}
                <div class="marine-field">
                    <span class="marine-label">Location coordinates * <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(click on the map)</small></span>
                    <div id="map" style="height: 320px; border-radius: 1rem; border: 1px solid var(--marine-border); z-index: 0;"></div>
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                    <p id="coords-display" style="font-size: 0.85rem; color: rgba(246,251,252,0.7); margin: 0.25rem 0 0;">
                        @if (old('latitude') && old('longitude'))
                            Selected: {{ old('latitude') }}, {{ old('longitude') }}
                        @else
                            No location selected yet.
                        @endif
                    </p>
                    @error('latitude')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                    @error('longitude')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Photos --}}
                <label class="marine-field">
                    <span class="marine-label">Photos * <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(at least one, max 10MB each)</small></span>
                    <input
                        class="marine-input"
                        type="file"
                        name="photos[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        required
                    >
                    @error('photos')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                    @error('photos.*')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Videos (optional) --}}
                <label class="marine-field">
                    <span class="marine-label">Videos <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(optional, max 100MB each)</small></span>
                    <input
                        class="marine-input"
                        type="file"
                        name="videos[]"
                        accept="video/mp4,video/quicktime"
                        multiple
                    >
                    @error('videos')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                    @error('videos.*')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Description (optional) --}}
                <label class="marine-field">
                    <span class="marine-label">Description <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(optional)</small></span>
                    <textarea
                        class="marine-input"
                        name="description"
                        rows="4"
                        placeholder="Describe your observation — behaviour, conditions, other details..."
                        style="resize: vertical; min-height: 5rem;"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Optional diving data --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem;">
                    <label class="marine-field">
                        <span class="marine-label">Water temp (°C)</span>
                        <input
                            class="marine-input"
                            type="number"
                            name="water_temperature"
                            value="{{ old('water_temperature') }}"
                            step="0.1"
                            min="-5"
                            max="50"
                            placeholder="e.g. 22.5"
                        >
                        @error('water_temperature')
                            <span class="marine-field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="marine-field">
                        <span class="marine-label">Depth (m)</span>
                        <input
                            class="marine-input"
                            type="number"
                            name="depth_meters"
                            value="{{ old('depth_meters') }}"
                            step="0.1"
                            min="0"
                            max="500"
                            placeholder="e.g. 12.0"
                        >
                        @error('depth_meters')
                            <span class="marine-field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="marine-field">
                        <span class="marine-label">Weather</span>
                        <input
                            class="marine-input"
                            type="text"
                            name="weather"
                            value="{{ old('weather') }}"
                            placeholder="e.g. Sunny, calm"
                        >
                        @error('weather')
                            <span class="marine-field-error">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="marine-form__actions">
                    <button type="submit" class="marine-button">Publish observation</button>
                    <a href="{{ route('home') }}" class="marine-navlink">Cancel</a>
                </div>
            </form>
        </div>
    </section>

    {{-- Leaflet CSS & JS from CDN --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        .marine-field-error {
            font-size: 0.82rem;
            color: #fca5a5;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const coordsDisplay = document.getElementById('coords-display');

            const initialLat = parseFloat(latInput.value) || 20;
            const initialLng = parseFloat(lngInput.value) || 0;
            const initialZoom = latInput.value ? 10 : 2;

            const map = L.map('map').setView([initialLat, initialLng], initialZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            let marker = null;

            if (latInput.value && lngInput.value) {
                marker = L.marker([parseFloat(latInput.value), parseFloat(lngInput.value)]).addTo(map);
            }

            map.on('click', function (e) {
                const lat = e.latlng.lat.toFixed(7);
                const lng = e.latlng.lng.toFixed(7);

                latInput.value = lat;
                lngInput.value = lng;
                coordsDisplay.textContent = 'Selected: ' + lat + ', ' + lng;

                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng).addTo(map);
                }
            });
        });
    </script>
@endsection

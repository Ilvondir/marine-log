@extends('layouts.marine')

@section('content')
    <section class="marine-auth">
        <div class="marine-panel" style="max-width: 52rem; width: 100%;">
            <p class="marine-kicker">Edit observation #{{ $observation->id }}</p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2.5rem);">Edit your observation</h1>
            <p>Update the fields below. At least one photo must remain.</p>

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

            <form class="marine-form" method="post" action="{{ route('observations.update', $observation) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Species --}}
                <label class="marine-field">
                    <span class="marine-label">Species *</span>
                    <input
                        class="marine-input"
                        type="text"
                        name="species"
                        value="{{ old('species', $observation->species) }}"
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
                        value="{{ old('observed_at', $observation->observed_at->format('Y-m-d\TH:i')) }}"
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
                        value="{{ old('location_name', $observation->location_name) }}"
                        placeholder="e.g. Great Barrier Reef, Coral Bay"
                        required
                    >
                    @error('location_name')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Map picker --}}
                <div class="marine-field">
                    <span class="marine-label">Location coordinates * <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(click on the map to change)</small></span>
                    <div id="map" style="height: 320px; border-radius: 1rem; border: 1px solid var(--marine-border); z-index: 0;"></div>
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $observation->latitude) }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $observation->longitude) }}">
                    <p id="coords-display" style="font-size: 0.85rem; color: rgba(246,251,252,0.7); margin: 0.25rem 0 0;">
                        Selected: {{ old('latitude', $observation->latitude) }}, {{ old('longitude', $observation->longitude) }}
                    </p>
                    @error('latitude')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                    @error('longitude')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Existing photos --}}
                @if ($observation->photos->count())
                    <div class="marine-field">
                        <span class="marine-label">Current photos <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(check to remove)</small></span>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr)); gap: 0.75rem; margin-top: 0.5rem;">
                            @foreach ($observation->photos as $photo)
                                <label style="position: relative; cursor: pointer;">
                                    <input
                                        type="checkbox"
                                        name="remove_resources[]"
                                        value="{{ $photo->id }}"
                                        style="position: absolute; top: 0.5rem; left: 0.5rem; z-index: 1; width: 1.1rem; height: 1.1rem; accent-color: #ef4444;"
                                        aria-label="Remove this photo"
                                    >
                                    <img
                                        src="{{ asset('storage/' . $photo->path) }}"
                                        alt="Observation photo"
                                        style="width: 100%; border-radius: 0.75rem; border: 1px solid var(--marine-border); aspect-ratio: 4/3; object-fit: cover;"
                                        loading="lazy"
                                    >
                                </label>
                            @endforeach
                        </div>
                        @error('photos')
                            <span class="marine-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                {{-- Existing videos --}}
                @if ($observation->videos->count())
                    <div class="marine-field">
                        <span class="marine-label">Current videos <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(check to remove)</small></span>
                        <div style="display: grid; gap: 0.75rem; margin-top: 0.5rem;">
                            @foreach ($observation->videos as $video)
                                <label style="display: flex; align-items: center; gap: 0.75rem;">
                                    <input
                                        type="checkbox"
                                        name="remove_resources[]"
                                        value="{{ $video->id }}"
                                        style="width: 1.1rem; height: 1.1rem; accent-color: #ef4444;"
                                        aria-label="Remove this video"
                                    >
                                    <span style="color: rgba(246,251,252,0.8);">{{ basename($video->path) }} ({{ number_format($video->size_bytes / 1048576, 1) }} MB)</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Add new photos --}}
                <label class="marine-field">
                    <span class="marine-label">Add photos <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(optional, max 10MB each)</small></span>
                    <input
                        class="marine-input"
                        type="file"
                        name="photos[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    >
                    @error('photos.*')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Add new videos --}}
                <label class="marine-field">
                    <span class="marine-label">Add videos <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(optional, max 100MB each)</small></span>
                    <input
                        class="marine-input"
                        type="file"
                        name="videos[]"
                        accept="video/mp4,video/quicktime"
                        multiple
                    >
                    @error('videos.*')
                        <span class="marine-field-error">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Description --}}
                <label class="marine-field">
                    <span class="marine-label">Description <small style="font-weight: 400; color: rgba(246,251,252,0.6);">(optional)</small></span>
                    <textarea
                        class="marine-input"
                        name="description"
                        rows="4"
                        placeholder="Describe your observation — behaviour, conditions, other details..."
                        style="resize: vertical; min-height: 5rem;"
                    >{{ old('description', $observation->description) }}</textarea>
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
                            value="{{ old('water_temperature', $observation->water_temperature) }}"
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
                            value="{{ old('depth_meters', $observation->depth_meters) }}"
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
                            value="{{ old('weather', $observation->weather) }}"
                            placeholder="e.g. Sunny, calm"
                        >
                        @error('weather')
                            <span class="marine-field-error">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="marine-form__actions">
                    <button type="submit" class="marine-button">Save changes</button>
                    <a href="{{ route('observations.show', $observation) }}" class="marine-navlink">Cancel</a>
                </div>
            </form>

            {{-- Delete observation --}}
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--marine-border);">
                <p style="color: rgba(246,251,252,0.6); margin-bottom: 0.75rem;">Danger zone</p>
                <form method="post" action="{{ route('observations.destroy', $observation) }}" id="delete-form">
                    @csrf
                    @method('DELETE')
                    <button
                        type="button"
                        onclick="confirmDelete()"
                        class="marine-button"
                        style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5;"
                    >
                        Delete observation
                    </button>
                </form>
            </div>
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
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this observation? This action cannot be undone.')) {
                document.getElementById('delete-form').submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const coordsDisplay = document.getElementById('coords-display');

            const initialLat = parseFloat(latInput.value);
            const initialLng = parseFloat(lngInput.value);

            const map = L.map('map').setView([initialLat, initialLng], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            let marker = L.marker([initialLat, initialLng]).addTo(map);

            map.on('click', function (e) {
                const lat = e.latlng.lat.toFixed(7);
                const lng = e.latlng.lng.toFixed(7);

                latInput.value = lat;
                lngInput.value = lng;
                coordsDisplay.textContent = 'Selected: ' + lat + ', ' + lng;

                marker.setLatLng(e.latlng);
            });
        });
    </script>
@endsection

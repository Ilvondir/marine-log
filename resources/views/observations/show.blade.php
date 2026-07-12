@extends('layouts.marine')

@section('content')
    <section>
        @if (session('success'))
            <div style="border-radius: 1rem; border: 1px solid rgba(74, 222, 128, 0.28); background: rgba(74, 222, 128, 0.08); padding: 0.9rem 1rem; color: #bbf7d0; margin-bottom: 1.25rem;" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <p class="marine-kicker">Observation #{{ $observation->id }}</p>
        <h1 style="font-size: clamp(1.8rem, 4vw, 3rem); margin: 0.25rem 0 1.5rem; line-height: 1.1;">{{ $observation->species }}</h1>

        <div class="marine-detail-layout">
            {{-- Left column: media --}}
            <div>
                {{-- Photo gallery with lightbox triggers --}}
                @if ($observation->photos->count())
                    <span class="marine-label">Photos</span>
                    <div class="marine-gallery" style="margin-top: 0.75rem;">
                        @foreach ($observation->photos as $index => $photo)
                            <img
                                src="{{ asset('storage/' . $photo->path) }}"
                                alt="{{ $observation->species }} photo {{ $index + 1 }}"
                                class="marine-gallery__thumb"
                                loading="lazy"
                                data-lightbox-index="{{ $index }}"
                                data-lightbox-src="{{ asset('storage/' . $photo->path) }}"
                                role="button"
                                tabindex="0"
                                aria-label="View photo {{ $index + 1 }} full size"
                            >
                        @endforeach
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
                    <div class="marine-map-panel" style="margin-top: 0.75rem;">
                        <div id="show-map" class="marine-map-panel__map" style="height: 240px;"></div>
                    </div>
                </div>
            </div>

            {{-- Right column: metadata --}}
            <div>
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
                        <p style="margin: 0;">{{ $observation->location_name }}</p>
                        <p style="margin: 0; font-size: 0.85rem; color: rgba(246,251,252,0.5);">{{ $observation->latitude }}, {{ $observation->longitude }}</p>
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

                <div style="display: flex; flex-wrap: wrap; gap: 0.875rem; margin-top: 2rem;">
                    <a href="{{ route('observations.index') }}" class="marine-navlink">← Back to observations</a>
                    @auth
                        <a href="{{ route('observations.create') }}" class="marine-button">New observation</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- Lightbox overlay --}}
    <div class="marine-lightbox" id="lightbox" aria-hidden="true" role="dialog" aria-label="Photo viewer">
        <button class="marine-lightbox__close" id="lightbox-close" aria-label="Close lightbox">&times;</button>
        <button class="marine-lightbox__nav marine-lightbox__nav--prev" id="lightbox-prev" aria-label="Previous photo">&#8249;</button>
        <img class="marine-lightbox__img" id="lightbox-img" src="" alt="Full size photo">
        <button class="marine-lightbox__nav marine-lightbox__nav--next" id="lightbox-next" aria-label="Next photo">&#8250;</button>
    </div>

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Map ---
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

            // --- Lightbox ---
            const thumbs = document.querySelectorAll('[data-lightbox-index]');
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            const closeBtn = document.getElementById('lightbox-close');
            const prevBtn = document.getElementById('lightbox-prev');
            const nextBtn = document.getElementById('lightbox-next');

            let currentIndex = 0;
            const photos = Array.from(thumbs).map(t => t.dataset.lightboxSrc);

            function openLightbox(index) {
                currentIndex = index;
                lightboxImg.src = photos[currentIndex];
                lightboxImg.alt = 'Photo ' + (currentIndex + 1) + ' of ' + photos.length;
                lightbox.classList.add('marine-lightbox--open');
                lightbox.setAttribute('aria-hidden', 'false');
                closeBtn.focus();
            }

            function closeLightbox() {
                lightbox.classList.remove('marine-lightbox--open');
                lightbox.setAttribute('aria-hidden', 'true');
                lightboxImg.src = '';
                if (thumbs[currentIndex]) thumbs[currentIndex].focus();
            }

            function navigate(direction) {
                currentIndex = (currentIndex + direction + photos.length) % photos.length;
                lightboxImg.src = photos[currentIndex];
                lightboxImg.alt = 'Photo ' + (currentIndex + 1) + ' of ' + photos.length;
            }

            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    openLightbox(parseInt(this.dataset.lightboxIndex));
                });
                thumb.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openLightbox(parseInt(this.dataset.lightboxIndex));
                    }
                });
            });

            closeBtn.addEventListener('click', closeLightbox);
            prevBtn.addEventListener('click', function () { navigate(-1); });
            nextBtn.addEventListener('click', function () { navigate(1); });

            lightbox.addEventListener('click', function (e) {
                if (e.target === lightbox) closeLightbox();
            });

            document.addEventListener('keydown', function (e) {
                if (!lightbox.classList.contains('marine-lightbox--open')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') navigate(-1);
                if (e.key === 'ArrowRight') navigate(1);
            });
        });
    </script>
@endsection

@extends('layouts.marine')

@section('content')
    <section class="obs-detail">
        @if (session('success'))
            <div class="obs-detail__alert obs-detail__alert--success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <header class="obs-detail__header">
            <p class="marine-kicker">Observation #{{ $observation->id }}</p>
            <h1 class="obs-detail__title">{{ $observation->species }}</h1>
            <p class="obs-detail__observer">by {{ $observation->user->name }} · {{ $observation->observed_at->format('M j, Y \a\t H:i') }}</p>
        </header>

        {{-- Two-column layout: media left, meta right --}}
        <div class="obs-detail__grid">
            {{-- Media column --}}
            <div class="obs-detail__media">
                {{-- Photos --}}
                @if ($observation->photos->count())
                    <div class="obs-detail__gallery">
                        @foreach ($observation->photos as $index => $photo)
                            <img
                                src="{{ asset('storage/' . $photo->path) }}"
                                alt="{{ $observation->species }} — photo {{ $index + 1 }}"
                                class="obs-detail__thumb"
                                data-lightbox="{{ $index }}"
                                loading="lazy"
                            >
                        @endforeach
                    </div>
                @endif

                {{-- Videos --}}
                @if ($observation->videos->count())
                    <div class="obs-detail__videos">
                        @foreach ($observation->videos as $video)
                            <video
                                controls
                                class="obs-detail__video"
                                preload="metadata"
                            >
                                <source src="{{ asset('storage/' . $video->path) }}" type="{{ $video->mime_type }}">
                                Your browser does not support the video tag.
                            </video>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Meta column --}}
            <aside class="obs-detail__meta">
                <div class="obs-detail__meta-item">
                    <span class="obs-detail__meta-label">Location</span>
                    <span>{{ $observation->location_name }}</span>
                </div>

                @if ($observation->description)
                    <div class="obs-detail__meta-item">
                        <span class="obs-detail__meta-label">Description</span>
                        <span>{{ $observation->description }}</span>
                    </div>
                @endif

                @if ($observation->water_temperature)
                    <div class="obs-detail__meta-item">
                        <span class="obs-detail__meta-label">Water temperature</span>
                        <span>{{ $observation->water_temperature }} °C</span>
                    </div>
                @endif

                @if ($observation->depth_meters)
                    <div class="obs-detail__meta-item">
                        <span class="obs-detail__meta-label">Depth</span>
                        <span>{{ $observation->depth_meters }} m</span>
                    </div>
                @endif

                @if ($observation->weather)
                    <div class="obs-detail__meta-item">
                        <span class="obs-detail__meta-label">Weather</span>
                        <span>{{ $observation->weather }}</span>
                    </div>
                @endif

                {{-- Map --}}
                <div class="obs-detail__meta-item">
                    <span class="obs-detail__meta-label">Map</span>
                    <div id="show-map" class="obs-detail__map"></div>
                </div>

                {{-- Actions --}}
                <div class="obs-detail__actions">
                    @can('update', $observation)
                        <a href="{{ route('observations.edit', $observation) }}" class="marine-button">Edit</a>
                    @endcan
                    <a href="{{ route('observations.index') }}" class="marine-navlink">Back to feed</a>
                    @can('delete', $observation)
                        <form method="post" action="{{ route('observations.destroy', $observation) }}" id="delete-form" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                onclick="if(confirm('Delete this observation? This cannot be undone.')){document.getElementById('delete-form').submit();}"
                                class="marine-table-action marine-table-action--danger"
                            >Delete</button>
                        </form>
                    @endcan
                </div>
            </aside>
        </div>
    </section>

    {{-- Lightbox --}}
    <dialog id="lightbox" class="obs-lightbox">
        <button class="obs-lightbox__close" aria-label="Close">&times;</button>
        <button class="obs-lightbox__nav obs-lightbox__nav--prev" aria-label="Previous">&#8249;</button>
        <img src="" alt="" class="obs-lightbox__img" id="lightbox-img">
        <button class="obs-lightbox__nav obs-lightbox__nav--next" aria-label="Next">&#8250;</button>
    </dialog>

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        .obs-detail {
            max-width: 72rem;
            margin: 0 auto;
        }

        .obs-detail__alert--success {
            border-radius: 1rem;
            border: 1px solid rgba(74, 222, 128, 0.28);
            background: rgba(74, 222, 128, 0.08);
            padding: 0.9rem 1rem;
            color: #bbf7d0;
            margin-bottom: 1.25rem;
        }

        .obs-detail__header {
            margin-bottom: 1.5rem;
        }

        .obs-detail__title {
            font-size: clamp(1.8rem, 4vw, 3rem);
            margin: 0.25rem 0 0;
            line-height: 1.1;
        }

        .obs-detail__observer {
            color: rgba(246, 251, 252, 0.6);
            margin: 0.5rem 0 0;
        }

        .obs-detail__grid {
            display: grid;
            gap: 2rem;
        }

        @media (min-width: 52rem) {
            .obs-detail__grid {
                grid-template-columns: 1.6fr 1fr;
            }
        }

        /* Gallery */
        .obs-detail__gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
            gap: 0.75rem;
        }

        .obs-detail__thumb {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 1px solid var(--marine-border);
            cursor: pointer;
            transition: transform 0.15s, border-color 0.15s;
        }

        .obs-detail__thumb:hover {
            transform: scale(1.03);
            border-color: rgba(77, 196, 255, 0.5);
        }

        /* Videos */
        .obs-detail__videos {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .obs-detail__video {
            width: 100%;
            max-height: 400px;
            border-radius: 0.75rem;
            border: 1px solid var(--marine-border);
            background: #000;
        }

        /* Meta sidebar */
        .obs-detail__meta {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .obs-detail__meta-item {
            display: grid;
            gap: 0.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--marine-border);
        }

        .obs-detail__meta-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(246, 251, 252, 0.55);
            font-weight: 600;
        }

        .obs-detail__map {
            height: 200px;
            border-radius: 0.75rem;
            border: 1px solid var(--marine-border);
            margin-top: 0.25rem;
            z-index: 0;
        }

        .obs-detail__actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 0.5rem;
        }

        /* Lightbox */
        .obs-lightbox {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            border: none;
            background: rgba(3, 11, 20, 0.92);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            z-index: 9999;
        }

        .obs-lightbox:not([open]) {
            display: none;
        }

        .obs-lightbox::backdrop {
            background: transparent;
        }

        .obs-lightbox__img {
            max-width: 90vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 0.5rem;
        }

        .obs-lightbox__close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 2.5rem;
            color: var(--marine-foam);
            background: none;
            border: none;
            cursor: pointer;
            line-height: 1;
            opacity: 0.7;
        }

        .obs-lightbox__close:hover {
            opacity: 1;
        }

        .obs-lightbox__nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            color: var(--marine-foam);
            background: rgba(246, 251, 252, 0.08);
            border: 1px solid var(--marine-border);
            border-radius: 50%;
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0.7;
            line-height: 1;
        }

        .obs-lightbox__nav:hover {
            opacity: 1;
            background: rgba(246, 251, 252, 0.15);
        }

        .obs-lightbox__nav--prev { left: 1.5rem; }
        .obs-lightbox__nav--next { right: 1.5rem; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Map
            const lat = {{ $observation->latitude }};
            const lng = {{ $observation->longitude }};

            const map = L.map('show-map').setView([lat, lng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);
            L.marker([lat, lng]).addTo(map).bindPopup(@js($observation->species)).openPopup();

            // Lightbox
            const dialog = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            const thumbs = document.querySelectorAll('[data-lightbox]');
            const photos = Array.from(thumbs).map(t => t.src);
            let currentIndex = 0;

            function openLightbox(index) {
                currentIndex = index;
                lightboxImg.src = photos[currentIndex];
                lightboxImg.alt = 'Photo ' + (currentIndex + 1);
                dialog.showModal();
            }

            function navigate(direction) {
                currentIndex = (currentIndex + direction + photos.length) % photos.length;
                lightboxImg.src = photos[currentIndex];
                lightboxImg.alt = 'Photo ' + (currentIndex + 1);
            }

            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    openLightbox(parseInt(this.dataset.lightbox));
                });
            });

            dialog.querySelector('.obs-lightbox__close').addEventListener('click', function () {
                dialog.close();
            });

            dialog.querySelector('.obs-lightbox__nav--prev').addEventListener('click', function () {
                navigate(-1);
            });

            dialog.querySelector('.obs-lightbox__nav--next').addEventListener('click', function () {
                navigate(1);
            });

            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) dialog.close();
            });

            document.addEventListener('keydown', function (e) {
                if (!dialog.open) return;
                if (e.key === 'ArrowLeft') navigate(-1);
                if (e.key === 'ArrowRight') navigate(1);
            });
        });
    </script>
@endsection

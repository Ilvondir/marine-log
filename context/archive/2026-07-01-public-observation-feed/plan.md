# Public Observation Feed — Implementation Plan

## Overview

S-01 delivers the public-facing read side of MarineLog: any visitor (guest or logged-in) can browse published observations in a paginated feed and view individual observation details without authentication. This is the simplest value path — it proves the product is useful even before a visitor signs up.

## Current State

- Observation model, migration, factory, repository, and service already exist (delivered by S-02)
- `Observation::scopePublished()` already filters by `published_at IS NOT NULL`
- `published_at` column has a database index
- `ObservationRepositoryInterface` only exposes `create()` and `findById()` — no listing methods
- `ObservationService` only exposes `publishObservation()` and `findById()` — no listing
- `observations.show` route is currently behind `auth` middleware — needs to become public
- No index/listing Blade view exists
- Layout already handles `@guest`/`@auth` states in navigation

## Desired End State

- `GET /observations` — paginated feed of published observations (public, no auth)
- `GET /observations/{observation}` — single observation detail (public, no auth)
- Feed displays observation cards with: first photo thumbnail, species, observed date, location name
- Pagination: 12 observations per page, newest first
- Navigation links to the feed from the main layout
- The show view is the existing one, moved outside the auth middleware group
- NFR met: page loads within 2 seconds (eager-loading, indexed queries)

## Architecture

```
ObservationController::index() → ObservationService::getPublishedFeed(page)
    → ObservationRepositoryInterface::paginatePublished(perPage)
        → Observation::published()->with('photos')->latest('published_at')->paginate()

ObservationController::show() → ObservationService::findPublishedById(id)
    → ObservationRepositoryInterface::findPublishedById(id)
        → Observation::published()->findOrFail(id)
```

## Phases

### Phase 1: Repository & Service Layer

**Goal:** Add paginated listing and public-find methods to the repository contract, implementation, and service.

**Files to edit:**

1. `app/Contracts/Repositories/ObservationRepositoryInterface.php` — add:
   ```php
   use Illuminate\Contracts\Pagination\LengthAwarePaginator;

   public function paginatePublished(int $perPage = 12): LengthAwarePaginator;
   public function findPublishedById(int $id): Observation;
   ```

2. `app/Repositories/EloquentObservationRepository.php` — implement:
   - `paginatePublished()`: uses `Observation::published()` scope, eager-loads `photos` (limit to first photo for thumbnail via subquery or collection), orders by `published_at DESC`, paginates
   - `findPublishedById()`: uses `Observation::published()->with(['user', 'photos', 'videos'])->findOrFail($id)` — throws ModelNotFoundException for unpublished/non-existent. Logs warning on not found (same pattern as `findById`).

3. `app/Services/ObservationService.php` — add:
   ```php
   public function getPublishedFeed(int $perPage = 12): LengthAwarePaginator;
   public function findPublishedById(int $id): Observation;
   ```
   These are thin pass-throughs to the repository (no additional business logic for read operations in MVP).

**Acceptance:** Repository returns paginated published observations with eager-loaded first photo and coordinates for the map; unpublished observations are never returned.

---

### Phase 2: Controller & Routes

**Goal:** Public HTTP endpoints for the observation feed and detail page.

**Files to edit:**

1. `app/Http/Controllers/ObservationController.php` — add `index()` method:
   ```php
   public function index(): View
   {
       $observations = $this->observationService->getPublishedFeed();
       return view('observations.index', compact('observations'));
   }
   ```
   - Modify `show()` to use `findPublishedById()` instead of `findById()` — ensures unpublished observations return 404 for guests.

2. `routes/web.php` — restructure observation routes:
   ```php
   // Public observation routes (no auth required)
   Route::get('/observations', [ObservationController::class, 'index'])->name('observations.index');
   Route::get('/observations/{observation}', [ObservationController::class, 'show'])->name('observations.show');

   // Authenticated observation routes
   Route::middleware('auth')->group(function () {
       Route::get('/observations/create', [ObservationController::class, 'create'])->name('observations.create');
       Route::post('/observations', [ObservationController::class, 'store'])->name('observations.store');
   });
   ```
   - **Critical:** `/observations/create` must be registered BEFORE `/observations/{observation}` to avoid `create` being captured as an `{observation}` parameter. Place the `auth` group with `/observations/create` first, or register the literal route before the wildcard.

3. Update navigation in `resources/views/layouts/marine.blade.php`:
   - Add "Browse observations" link visible to everyone (both guest and auth sections)

**Acceptance:** `GET /observations` returns paginated feed without auth. `GET /observations/{id}` shows published observation without auth. Unpublished observation IDs return 404. `/observations/create` still requires auth.

---

### Phase 3: Blade Views

**Goal:** Feed listing page that feels like a browsable gallery (not a form), with an interactive map, clickable/zoomable photos, and responsive card layout.

**Files to create/edit:**

1. `resources/views/observations/index.blade.php` — new file:
   - Extends `layouts.marine`
   - **Hero section** with page heading "Observations" (kicker "Explore the feed") — visually distinct from auth/form pages
   - **Map panel** (Leaflet, same CDN as show view):
     - Displays markers for all observations on the current page
     - Marker popup shows species name; clicking popup links to observation detail
     - Map sits above the card grid, full-width, ~280px height (collapsible on mobile via a toggle button)
   - **Responsive card grid** (CSS Grid: 1 col mobile, 2 cols tablet, 3 cols desktop):
     - Each card contains:
       - First photo as thumbnail (aspect-ratio 4/3, object-fit cover, `loading="lazy"`)
       - Species name (bold)
       - Date formatted as "M j, Y"
       - Location name
       - Entire card is a clickable link to `observations.show`
     - Cards use `marine-card` class with hover effect (subtle border glow / scale)
   - **Empty state:** friendly message + illustration-style icon encouraging exploration
   - **Pagination:** Laravel pagination links at the bottom, styled to marine theme

2. `resources/views/observations/show.blade.php` — rework to gallery/detail view (not form-like):
   - **Photo lightbox:**
     - Photos are displayed as a clickable thumbnail grid
     - Clicking a photo opens a full-screen overlay (lightbox) with the image at full resolution
     - Lightbox supports: close button, click-outside-to-close, ESC key to close, left/right arrow navigation between photos
     - Implementation: pure CSS + minimal vanilla JS (no external library) — a `<dialog>` element or fixed overlay
   - **Layout restructure:** use a two-column layout on desktop (media gallery left, metadata right) instead of the current top-to-bottom form-style stack
   - **Map** remains (already present), keep as-is
   - **Navigation:** "Back to observations" linking to `observations.index`; "New observation" button only for `@auth` users
   - Videos remain with native `<video>` controls (no lightbox needed for video)

3. `resources/views/layouts/marine.blade.php` — style additions in the inline fallback CSS:
   - `.marine-card-grid` — responsive grid for observation cards
   - `.marine-card` — card styling with hover state (border-color transition, subtle translateY)
   - `.marine-lightbox` — full-screen fixed overlay, backdrop blur, centered image, close button
   - `.marine-lightbox__img` — max-width/max-height constrained, object-fit contain
   - `.marine-map-panel` — map container styling (border-radius, border, margin)
   - `.marine-detail-layout` — two-column grid for show view (collapses to single column on mobile)

4. Lightbox JS (inline `<script>` in show view, no build dependency):
   - Open lightbox on thumbnail click, set `src` to full-size image URL
   - Arrow key / swipe navigation between photos
   - Close on ESC, click-outside, or close button
   - Trap focus inside lightbox for accessibility (dialog element handles this natively)

**Acceptance:** 
- Feed page feels like a gallery/explorer — not a form
- Map shows observation markers with clickable popups
- Card grid is responsive with hover feedback
- Clicking a photo in the detail view opens a zoomable lightbox overlay
- Lightbox navigates between photos and closes via ESC/click-outside
- Pagination works
- Empty state displays when no observations exist

---

### Phase 4: Tests

**Goal:** Feature tests proving the public feed behavior.

**Files to create:**

1. `tests/Feature/PublicObservationFeedTest.php`:
   - `test_guest_can_view_observation_feed(): void`
   - `test_feed_only_shows_published_observations(): void`
   - `test_feed_is_paginated(): void`
   - `test_feed_orders_by_newest_first(): void`
   - `test_guest_can_view_published_observation(): void`
   - `test_guest_cannot_view_unpublished_observation(): void`
   - `test_authenticated_user_can_view_feed(): void`
   - `test_feed_shows_empty_state_when_no_observations(): void`
   - `test_observation_card_displays_species_and_location(): void`

2. `tests/Unit/ObservationServiceTest.php` — add tests (or extend existing file):
   - `test_get_published_feed_returns_paginated_results(): void`
   - `test_find_published_by_id_throws_for_unpublished(): void`

**Acceptance:** All tests pass via `sail artisan test`.

---

## What We're NOT Doing

- No search or filtering (keep it to a simple chronological feed for now)
- No infinite scroll or AJAX pagination (standard Laravel page links)
- No species taxonomy or grouping
- No caching layer (database queries are simple and indexed)
- No "Animal of the Day" (FR-007, nice-to-have, not in scope)
- No SEO meta tags beyond the basics (title)
- No observation count badge or statistics
- No external lightbox library (pure CSS + vanilla JS using `<dialog>`)
- No image resizing/thumbnail generation (use original photos with lazy loading)
- No map clustering (page shows max 12 markers — clustering unnecessary)

## Success Criteria

1. `GET /observations` returns a paginated list of published observations without requiring login
2. Only observations with `published_at` set are shown — drafts/unpublished never leak
3. `GET /observations/{id}` shows a published observation to any visitor
4. Accessing an unpublished observation ID returns 404
5. Feed displays 12 observations per page, ordered newest first
6. Each card shows thumbnail, species, date, and location
7. Feed includes a map with markers for observations on the current page
8. Clicking a photo in the detail view opens a full-screen lightbox
9. Lightbox supports keyboard navigation (ESC to close, arrows to browse)
10. Detail view uses a gallery/content layout — not a form-style stack
11. Empty feed shows a friendly message
12. Navigation includes a link to the feed for all users
13. Existing auth-protected create/store routes remain unchanged
14. All tests pass

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Route ordering conflict (`create` vs `{observation}`) | Register literal `/observations/create` before wildcard `{observation}` |
| N+1 query on photos in feed | Eager-load `photos` relation in repository query |
| Large thumbnail images slow the feed | Use `loading="lazy"` on images; thumbnails will use existing stored photos (no resizing in MVP) |
| Lightbox accessibility | Use native `<dialog>` element — handles focus trapping and ESC natively |
| Map on index with many markers | Page is capped at 12 items — no clustering needed; markers are passed as JSON to JS |
| Breaking existing show route for auth users | Show route moves outside auth — existing `observations.show` named route stays the same |

## Dependencies

- S-02 observation model, migration, and factory (done)
- `scopePublished` scope on Observation model (done)
- `published_at` index in migration (done)
- Layout shell with guest/auth handling (done)

## Execution Order

Phase 1 → Phase 2 → Phase 3 → Phase 4 (sequential, each builds on the previous)

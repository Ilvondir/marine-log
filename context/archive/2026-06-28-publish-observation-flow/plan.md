# Publish Observation Flow — Implementation Plan

## Overview

S-02 delivers the core product promise: a logged-in user can create and publish a wildlife observation with species, date/time, location, and at least one photo. This is the north-star slice that proves the product hypothesis.

## Current State

- Auth scaffold (F-01) is done: login, registration, logout, roles, admin middleware
- No domain models, migrations, services, or repositories exist yet
- File uploads: `public` disk configured (local storage → `storage/app/public`), symlink via `storage:link`
- No Inertia/SPA — pure Blade + Tailwind v4 views
- Architecture conventions from AGENTS.md require: repository interface → implementation, service layer, thin controllers, constructor injection

## Desired End State

A logged-in user navigates to a "New Observation" form, fills required fields (species, date/time, location, ≥1 photo), optionally fills extras (description, videos, water temperature, depth, weather), and publishes. The observation is immediately public. The implementation follows the repository pattern prescribed by AGENTS.md.

## Architecture

```
Controller (thin) → ObservationService → ObservationRepositoryInterface
                                        → MediaRepositoryInterface
```

## Phases

### Phase 1: Data Layer

**Goal:** Create the Observation and ObservationMedia models, migration, factory, and repository contracts.

**Files to create/edit:**

1. `database/migrations/xxxx_xx_xx_create_observations_table.php`
   - `id`, `user_id` (FK → users), `species` (string, required), `observed_at` (datetime, required), `latitude` (decimal 10,7), `longitude` (decimal 10,7), `location_name` (string, required), `description` (text, nullable), `water_temperature` (decimal 4,1, nullable), `depth_meters` (decimal 6,1, nullable), `weather` (string, nullable), `published_at` (timestamp, nullable — for future draft support, set on create for now), `timestamps`

2. `database/migrations/xxxx_xx_xx_create_resources_table.php`
   - `id`, `resourceable_id` (unsigned bigint), `resourceable_type` (string), `type` (enum: photo, video), `path` (string), `mime_type` (string), `size_bytes` (unsigned bigint), `sort_order` (unsigned smallint, default 0), `timestamps`
   - Polymorphic index on (`resourceable_type`, `resourceable_id`)

4. `app/Models/Resource.php`
   - Fillable, casts (type → enum)
   - Relation: `morphTo('resourceable')`

3. `app/Models/Observation.php`
   - Fillable fields, casts (observed_at → datetime, latitude/longitude → decimal, published_at → datetime)
   - Relations: `belongsTo(User)`, `morphMany(Resource::class, 'resourceable')`
   - Convenience: `photos()` → morphMany filtered by type=photo, `videos()` → morphMany filtered by type=video
   - Scope: `scopePublished` (where published_at is not null)

5. `app/Contracts/Repositories/ObservationRepositoryInterface.php`
   - `create(array $data): Observation`
   - `findById(int $id): Observation`

6. `app/Contracts/Repositories/ResourceRepositoryInterface.php`
   - `createForResourceable(Model $resourceable, array $mediaData): Resource`

7. `app/Repositories/EloquentObservationRepository.php`
8. `app/Repositories/EloquentResourceRepository.php`

9. `database/factories/ObservationFactory.php`
10. `database/factories/ResourceFactory.php`

11. `app/Providers/AppServiceProvider.php` — bind contracts to implementations

**Acceptance:** Migration runs, models are query-able, factories produce valid records.

---

### Phase 2: Service Layer & File Upload

**Goal:** Business logic for publishing an observation with media handling.

**Files to create/edit:**

1. `app/Services/ObservationService.php`
   - `publishObservation(int $userId, array $validatedData, array $files): Observation`
   - Orchestrates: create observation record → store files to `public` disk → create Resource records (polymorphic)
   - Sets `published_at` to now (MVP has no draft state)

2. `app/Providers/AppServiceProvider.php` — no additional changes needed (bindings done in Phase 1)

**Validation rules (enforced in controller, documented here):**
- `species`: required, string, max:255
- `observed_at`: required, date, before_or_equal:now
- `latitude`: required, numeric, between:-90,90
- `longitude`: required, numeric, between:-180,180
- `location_name`: required, string, max:255
- `photos`: required, array, min:1
- `photos.*`: file, image (jpeg, png, webp), max:10240 (10MB)
- `videos`: nullable, array
- `videos.*`: file, mimetypes:video/mp4,video/quicktime, max:102400 (100MB)
- `description`: nullable, string, max:5000
- `water_temperature`: nullable, numeric, between:-5,50
- `depth_meters`: nullable, numeric, between:0,500
- `weather`: nullable, string, max:255

**File storage:**
- Disk: `public` (storage/app/public)
- Path pattern: `observations/{observation_id}/{filename}`
- Run `php artisan storage:link` in setup (add to README/setup notes)

**Acceptance:** Service creates observation with media, files stored to public disk.

---

### Phase 3: Controller & Routes

**Goal:** HTTP layer — form display and submission.

**Files to create/edit:**

1. `app/Http/Controllers/ObservationController.php`
   - `create(): View` — show the "new observation" form
   - `store(Request $request): RedirectResponse` — validate, call service, redirect

2. `app/Http/Requests/StoreObservationRequest.php` (Form Request class)
   - Contains all validation rules from Phase 2
   - `authorize()`: returns true (route is already behind `auth` middleware)

3. `routes/web.php` — add:
   ```php
   Route::middleware('auth')->group(function () {
       Route::get('/observations/create', [ObservationController::class, 'create'])->name('observations.create');
       Route::post('/observations', [ObservationController::class, 'store'])->name('observations.store');
   });
   ```

**Acceptance:** Authenticated user can POST to `/observations` with valid data and get redirected. Unauthenticated → redirect to login.

---

### Phase 4: Blade Views

**Goal:** Form UI using existing marine-themed shell + Tailwind v4.

**Files to create/edit:**

1. `resources/views/observations/create.blade.php`
   - Extends the existing shell layout
   - Multi-file upload for photos (required, drag & drop or click)
   - Optional video upload
   - Species text input
   - Date/time picker (native HTML datetime-local)
   - Location: interactive map picker (Leaflet.js + OpenStreetMap tiles) — user clicks map to set lat/lng, hidden inputs store coordinates, text input for location name
   - Optional fields: description textarea, water temperature, depth, weather
   - Submit button "Publish Observation"
   - Client-side validation hints (HTML5 required attributes)
   - Error display using `@error` directives

2. `resources/views/observations/show.blade.php`
   - Simple confirmation/display view after publish
   - Shows observation details + uploaded media

3. Navigation link in shell layout (add "New Observation" for authenticated users)

**Acceptance:** Form renders, submits, and shows validation errors. Successful publish redirects to show view.

---

### Phase 5: Tests

**Goal:** Feature tests proving the full publish flow and edge cases.

**Files to create/edit:**

1. `tests/Feature/PublishObservationTest.php`
   - `test_guest_cannot_access_create_form(): void`
   - `test_guest_cannot_store_observation(): void`
   - `test_authenticated_user_can_see_create_form(): void`
   - `test_user_can_publish_observation_with_required_fields(): void`
   - `test_user_can_publish_observation_with_all_optional_fields(): void`
   - `test_publish_fails_without_species(): void`
   - `test_publish_fails_without_photo(): void`
   - `test_publish_fails_with_future_date(): void`
   - `test_publish_fails_with_invalid_coordinates(): void`
   - `test_publish_fails_with_oversized_photo(): void`
   - `test_published_observation_is_stored_with_media(): void`

2. `tests/Unit/ObservationServiceTest.php`
   - Unit tests with mocked repository contracts
   - `test_publish_observation_creates_record_and_media(): void`
   - `test_publish_observation_stores_files_to_public_disk(): void`

**Acceptance:** All tests pass via `sail artisan test`.

---

## What We're NOT Doing

- No draft/scheduled publish state (published_at is always set on create)
- No image cropping, compression, or thumbnail generation
- No EXIF data extraction
- No observation listing/feed (that's S-01)
- No edit/delete (that's S-03)
- No moderation (that's S-04)
- No video streaming/transcoding
- No S3/cloud storage (local public disk for MVP)
- No AJAX/SPA interactions — standard form POST with full-page redirect
- No reverse geocoding (user types location name manually, picks coordinates on map)

## Success Criteria

1. Authenticated user can navigate to `/observations/create` and see the form
2. Submitting with all required fields + ≥1 photo creates the observation immediately
3. The observation record has `published_at` set (publicly visible)
4. Uploaded photos are stored in `storage/app/public/observations/{id}/`
5. Missing required fields show validation errors and block submission
6. Guest attempting to access the form is redirected to login
7. All tests pass

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Large file uploads may timeout | Set reasonable max sizes (10MB photo, 100MB video), document Nginx/PHP limits |
| Storage symlink not created | Add `storage:link` to setup/deployment commands |
| Concurrent uploads from same user | Not a concern for solo MVP traffic |

## Dependencies

- F-01 auth scaffold (done)
- `storage:link` Artisan command (run during setup)
- PHP `fileinfo` extension (included in Sail by default)
- Leaflet.js (CDN include — no npm package needed, free + open source, uses OpenStreetMap tiles)

## Execution Order

Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 (sequential, each builds on the previous)

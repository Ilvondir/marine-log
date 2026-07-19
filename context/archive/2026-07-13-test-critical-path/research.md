# Research: test-critical-path

> Internal research for Phase 1 of the test plan rollout.
> Covers Risk #1 (publish flow), #2 (IDOR/ownership), #5 (validation edge cases).

## 1. Publish Flow (Risk #1)

**Chain:** `ObservationController::store` → `StoreObservationRequest` (validation) → `ObservationService::publishObservation()` → `ObservationRepositoryInterface::create()` + `storeMedia()`

Key implementation details:
- `publishObservation()` wraps in `DB::transaction()` — atomicity guaranteed
- `published_at` set to `now()` immediately (no draft state)
- Media stored AFTER observation creation (needs observation ID for path: `observations/{id}/`)
- Resource records created via `ResourceRepositoryInterface::createForResourceable()` with type, path, mime_type, size_bytes, sort_order (index-based)
- No explicit file count limit in service layer (only `min:1` photo in validation)

**File references:**
- `app/Http/Controllers/ObservationController.php:53-64` — store action
- `app/Services/ObservationService.php:33-55` — publishObservation
- `app/Services/ObservationService.php:150-163` — storeMedia helper

## 2. Authorization/Ownership (Risk #2)

**ObservationPolicy** has only two methods:
- `update(User, Observation)` → `$user->id === $observation->user_id`
- `delete(User, Observation)` → `$user->id === $observation->user_id`

**Controller authorize() calls:**
- `edit()` line 81: `$this->authorize('update', $observation)`
- `update()` line 91: `$this->authorize('update', $observation)`
- `destroy()` line 108: `$this->authorize('delete', $observation)`

**No authorization needed for:**
- `store()` — any authenticated user can publish (correct)
- `show()` — uses `abort_unless($observation->published_at !== null, 404)` (public, only published)
- `index()` — public feed

**IDOR defense in depth:**
- `remove_resources.*` validation uses global `exists:resources,id` (doesn't scope to observation)
- Service `removeResources()` scopes via `$observation->resources()->whereIn('id', $resourceIds)` — actual IDOR protection lives here

## 3. Validation Rules (Risk #5)

### StoreObservationRequest

| Field | Rules | Boundaries |
|-------|-------|------------|
| species | required, string, max:255 | — |
| observed_at | required, date, before_or_equal:now | No past limit |
| latitude | required, numeric, between:-90,90 | inclusive |
| longitude | required, numeric, between:-180,180 | inclusive |
| location_name | required, string, max:255 | — |
| photos | required, array, min:1 | At least 1 |
| photos.* | file, image, mimes:jpeg,png,webp, max:10240 | 10MB per photo |
| videos | nullable, array | No max count |
| videos.* | file, mimetypes:video/mp4,video/quicktime, max:102400 | 100MB per video |
| description | nullable, string, max:5000 | — |
| water_temperature | nullable, numeric, between:-5,50 | Celsius |
| depth_meters | nullable, numeric, between:0,500 | — |
| weather | nullable, string, max:255 | — |

### UpdateObservationRequest differences

- `photos` → `nullable, array` (existing photos suffice)
- Adds `remove_resources`: `nullable, array`, each `integer, exists:resources,id`
- Custom validator: ensures at least 1 photo remains after removals

### Edge cases identified

- No max photo/video COUNT
- No min image dimensions
- `observed_at` has no lower bound
- Boundary values: ±90 lat, ±180 lng are valid
- `photos.*` uses both `image` (content) AND `mimes` (extension)
- `videos.*` uses `mimetypes` (content-based, not extension)
- `remove_resources` global exists check (IDOR mitigated in service)

## 4. Existing Test Coverage

### PublishObservationTest.php (11 tests)
- Guest blocked: ✅
- Auth user create form: ✅
- Happy path (required + optional fields): ✅
- Missing species: ✅
- Missing photo: ✅
- Future date: ✅
- Invalid latitude (>90): ✅
- Oversized photo (>10MB): ✅
- Media metadata stored correctly: ✅

### ManageObservationTest.php (12 tests)
- Guest blocked: ✅
- Owner CRUD: ✅
- Non-owner 403: ✅
- Add/remove photos: ✅
- Zero-photo protection: ✅
- File cleanup on delete: ✅

### ObservationServiceTest.php (6 unit tests)
- All service methods mocked and tested: ✅

## 5. Identified Gaps (what Phase 1 should add)

### Validation gaps (high priority)
- Invalid longitude (>180, <-180)
- Boundary values: lat ±90 exactly, lng ±180 exactly (should pass)
- Invalid MIME types (e.g., .gif photo, .avi video)
- Oversized video (>100MB)
- Description exceeding 5000 chars
- water_temperature beyond boundaries (-6, 51)
- depth_meters: negative value, >500
- Invalid date format (not just future)

### Authorization gaps (medium priority)
- Attempting to remove a resource belonging to a DIFFERENT observation via remove_resources IDs
- Verify IDOR defense: service ignores resource IDs not owned by the observation

### Publish flow gaps (lower priority — mostly covered)
- sort_order correctness with multiple photos (index 0, 1, 2...)
- webp photo upload (only jpeg/png tested explicitly)
- Transaction behavior (hard to test without DB failure simulation)

### Update flow gaps (medium priority)
- Simultaneously adding AND removing photos in one request
- Update with invalid file types (same validation as store, but worth explicit test)

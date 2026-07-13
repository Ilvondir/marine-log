# Test Critical Path — Implementation Plan

## Overview

Phase 1 of the test plan rollout: add feature and unit tests that defend the publish flow, ownership/authorization boundary, and validation edge cases. Covers Risk #1 (publish regression), #2 (IDOR/ownership bypass), #5 (validation boundary failures) from `context/foundation/test-plan.md`.

## Current State

- 11 publish tests exist (happy path + basic validation failures)
- 12 manage tests exist (CRUD + ownership enforcement)
- 6 unit tests for ObservationService
- Key gaps: validation boundaries (longitude, temperature, depth, mime types, oversized video), IDOR on remove_resources, simultaneous add+remove, webp uploads

## Desired End State

The existing test suite is extended with boundary/edge-case tests that catch regressions the current happy-path-focused suite misses. No new test files — extend existing ones to maintain cohesion.

## What We're NOT Doing

- No e2e/browser tests (Phase 2+ scope)
- No Blade view testing / snapshots (negative space per test plan §7)
- No storage symlink tests (Phase 3 scope)
- No route resolution tests (Phase 2 scope)
- No new test infrastructure or packages
- No refactoring existing tests

## Phase 1: Validation Boundary Tests (Store)

### Overview
Extend `PublishObservationTest` with boundary and invalid-type cases that the current suite misses.

### Tests to add in `tests/Feature/PublishObservationTest.php`:

1. `test_publish_fails_with_invalid_longitude(): void`
   - POST with longitude `181.0` → session error on `longitude`

2. `test_publish_fails_with_negative_out_of_range_latitude(): void`
   - POST with latitude `-91.0` → session error on `latitude`

3. `test_publish_accepts_boundary_coordinates(): void`
   - POST with latitude `90.0`, longitude `-180.0` → success (boundary inclusive)

4. `test_publish_fails_with_invalid_photo_mime_type(): void`
   - POST with `.gif` file → session error on `photos.0`

5. `test_publish_fails_with_oversized_video(): void`
   - POST with video >100MB → session error on `videos.0`

6. `test_publish_fails_with_invalid_video_mime_type(): void`
   - POST with `.avi` file → session error on `videos.0`

7. `test_publish_fails_with_description_exceeding_max_length(): void`
   - POST with 5001 char description → session error on `description`

8. `test_publish_fails_with_water_temperature_below_minimum(): void`
   - POST with water_temperature `-6` → session error

9. `test_publish_fails_with_depth_meters_exceeding_maximum(): void`
   - POST with depth_meters `501` → session error

10. `test_publish_accepts_webp_photo(): void`
    - POST with `.webp` image → success, stored correctly

11. `test_publish_stores_multiple_photos_with_correct_sort_order(): void`
    - POST with 3 photos → sort_order 0, 1, 2

### Success Criteria
- All new tests pass via `sail artisan test --filter=PublishObservationTest`
- Existing 11 tests remain green

---

## Phase 2: Validation Boundary Tests (Update)

### Overview
Extend `ManageObservationTest` with edge cases for the update flow.

### Tests to add in `tests/Feature/ManageObservationTest.php`:

1. `test_update_fails_with_invalid_photo_mime_type(): void`
   - PUT with `.gif` new photo → session error on `photos.0`

2. `test_update_fails_with_oversized_new_photo(): void`
   - PUT with photo >10MB → session error on `photos.0`

3. `test_owner_can_add_and_remove_photos_simultaneously(): void`
   - PUT removing 1 photo + adding 1 new photo → observation still has correct count

4. `test_remove_resources_ignores_ids_not_belonging_to_observation(): void`
   - Create observation A with photo, create observation B with photo
   - PUT on observation A with `remove_resources` containing B's photo ID
   - B's photo remains untouched, A unchanged

5. `test_update_fails_with_water_temperature_above_maximum(): void`
   - PUT with water_temperature `51` → session error

6. `test_update_fails_with_negative_depth(): void`
   - PUT with depth_meters `-1` → session error

### Success Criteria
- All new tests pass via `sail artisan test --filter=ManageObservationTest`
- Existing 12 tests remain green

---

## Phase 3: Unit Test Extensions

### Overview
Extend `ObservationServiceTest` with edge-case unit tests.

### Tests to add in `tests/Unit/ObservationServiceTest.php`:

1. `test_publish_observation_sets_sort_order_sequentially(): void`
   - Mock 3 photos → verify createForResourceable called with sort_order 0, 1, 2

2. `test_remove_resources_only_deletes_observation_owned_resources(): void`
   - Mock observation with resources relation returning only matching IDs
   - Verify deleteById called only for scoped results, not for foreign IDs

### Success Criteria
- All unit tests pass via `sail artisan test --filter=ObservationServiceTest`
- Existing 6 tests remain green

---

## Testing Strategy

### Approach
- Extend existing test files (don't create new ones for this phase)
- Use `Storage::fake('public')` for all file-related tests
- Use `UploadedFile::fake()` for crafting edge-case files
- Use `RefreshDatabase` trait (already in place)
- Match existing naming convention: `test_<behavior>(): void`

### Run command
```bash
./vendor/bin/sail artisan test
```

### Validation oracle
Assertions come from the validation rules in `StoreObservationRequest` and `UpdateObservationRequest` — not from reading the implementation to discover "what does it currently do." Boundary values (±90, ±180, -5/50, 0/500, 10240KB, 102400KB) come from the FormRequest rules documented in research.

## Execution Order

Phase 1 → Phase 2 → Phase 3 (sequential — each extends a different file, can verify independently)

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands.

### Phase 1: Validation Boundary Tests (Store)

- [x] 1.1 Add 11 boundary/edge-case tests to PublishObservationTest
- [x] 1.2 All 22 tests in PublishObservationTest pass (11 existing + 11 new)

### Phase 2: Validation Boundary Tests (Update)

- [x] 2.1 Add 6 edge-case tests to ManageObservationTest
- [x] 2.2 All 18 tests in ManageObservationTest pass (12 existing + 6 new)

### Phase 3: Unit Test Extensions

- [x] 3.1 Add 2 unit tests to ObservationServiceTest
- [x] 3.2 All 8 tests in ObservationServiceTest pass (6 existing + 2 new)

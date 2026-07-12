# Own Observation Management — Implementation Plan

## Overview

S-03 delivers FR-004: a logged-in user can edit or delete their own observations. Another user cannot modify or remove observations that do not belong to them.

## Current State

- S-02 (publish-observation-flow) is implemented: create form, store action, show view, `ObservationService::publishObservation`, `ObservationRepository::create/findById`
- Auth scaffold (F-01) is done: session auth, roles, admin middleware
- No authorization policies exist — only route-level `auth` middleware
- No edit/update/delete routes, controller actions, service methods, or repository methods exist
- Resources (media) are managed via polymorphic `Resource` model; no delete logic yet
- Views use the `marine` layout shell + Tailwind v4, standard Blade forms

## Desired End State

An authenticated user can:
1. Navigate to their observation's edit form (pre-filled with current data)
2. Update any field (species, date, location, description, water temp, depth, weather)
3. Add new photos/videos to the existing set
4. Remove individual existing photos/videos
5. Delete the entire observation (with confirmation)

Only the observation owner may perform these actions. Any other user receives a 403.

## Architecture

```
Controller (thin) → Policy (authorize) → ObservationService → ObservationRepositoryInterface
                                                             → ResourceRepositoryInterface
                                                             → Storage (file deletion)
```

## Phases

### Phase 1: Authorization Layer (Policy)

**Goal:** Create an `ObservationPolicy` that enforces ownership, register it, and apply it in the controller.

**Files to create/edit:**

1. `app/Policies/ObservationPolicy.php`
   - `update(User $user, Observation $observation): bool` → `$user->id === $observation->user_id`
   - `delete(User $user, Observation $observation): bool` → `$user->id === $observation->user_id`

2. `app/Providers/AppServiceProvider.php` — register the policy in `boot()`:
   ```php
   Gate::policy(Observation::class, ObservationPolicy::class);
   ```

**Acceptance:** Policy class exists, is registered, and can be resolved via `Gate::getPolicyFor(Observation::class)`.

**Status: ✅ DONE**

---

### Phase 2: Repository & Service Layer

**Goal:** Add update and delete capabilities to the repository contract, implementation, and service.

**Files to edit/create:**

1. `app/Contracts/Repositories/ObservationRepositoryInterface.php` — add:
   - `update(int $id, array $data): Observation`
   - `delete(int $id): void`

2. `app/Contracts/Repositories/ResourceRepositoryInterface.php` — add:
   - `deleteForResourceable(Model $resourceable): void`
   - `deleteById(int $id): void`

3. `app/Repositories/EloquentObservationRepository.php` — implement:
   - `update()`: `findOrFail($id)` → `update($data)` → return fresh model. Log + rethrow on failure.
   - `delete()`: `findOrFail($id)` → `delete()`. Log + rethrow on failure.

4. `app/Repositories/EloquentResourceRepository.php` — implement:
   - `deleteForResourceable()`: delete all resources for a given morphable parent. Log + rethrow on failure.
   - `deleteById()`: find and delete a single resource by ID. Log + rethrow on failure.

5. `app/Services/ObservationService.php` — add:
   - `updateObservation(Observation $observation, array $validatedData, array $newPhotos = [], array $newVideos = [], array $removeResourceIds = []): Observation`
     - DB::transaction: remove specified resources (file + DB record) → update observation fields → store new media
   - `deleteObservation(Observation $observation): void`
     - DB::transaction: delete all resources (files from disk) → delete all resource records → delete observation

**Validation of ownership is NOT in the service** — it lives in the Policy, called by the controller. The service receives an already-authorized `Observation` instance.

**Acceptance:** Service methods work in isolation with mocked repos; resource files are cleaned up on delete.

**Status: ✅ DONE**

---

### Phase 3: Controller, Routes & Form Request

**Goal:** HTTP layer — edit form, update action, delete action.

**Files to create/edit:**

1. `app/Http/Requests/UpdateObservationRequest.php`
   - `authorize()`: returns `true` (policy check happens via `$this->authorize()` in controller)
   - Validation rules: same as `StoreObservationRequest` EXCEPT:
     - `photos` → `nullable|array` (not required; observation already has photos)
     - `photos.*` → same file rules
     - `remove_resources` → `nullable|array`
     - `remove_resources.*` → `integer|exists:resources,id`
   - Custom validation: if all existing photos would be removed AND no new photos are uploaded → fail. (At least 1 photo must remain.)

2. `app/Http/Controllers/ObservationController.php` — add:
   - `edit(Observation $observation): View` — authorize via policy, load observation with resources, return edit view
   - `update(UpdateObservationRequest $request, Observation $observation): RedirectResponse` — authorize, call service, redirect to show
   - `destroy(Observation $observation): RedirectResponse` — authorize, call service, redirect to home with success flash

3. `routes/web.php` — add inside `auth` middleware group:
   ```php
   Route::get('/observations/{observation}/edit', [ObservationController::class, 'edit'])->name('observations.edit');
   Route::put('/observations/{observation}', [ObservationController::class, 'update'])->name('observations.update');
   Route::delete('/observations/{observation}', [ObservationController::class, 'destroy'])->name('observations.destroy');
   ```

**Controller pattern:** Each action calls `$this->authorize('update', $observation)` or `$this->authorize('delete', $observation)` before proceeding. Thin body — validate, authorize, delegate to service, return response.

**Acceptance:** Routes respond correctly; non-owner gets 403; guest gets redirected to login.

**Status: ✅ DONE**

---

### Phase 4: Blade Views

**Goal:** Edit form + delete button on show page, following existing marine shell pattern.

**Files to create/edit:**

1. `resources/views/observations/edit.blade.php`
   - Extends `layouts.marine`
   - Pre-fills all fields from `$observation`
   - Displays existing photos/videos with a "remove" checkbox for each
   - File inputs for adding new photos/videos (optional)
   - Map picker pre-positioned at current lat/lng
   - PUT method via `@method('PUT')`
   - "Save changes" submit button
   - "Delete observation" button (separate form with `@method('DELETE')` + JS confirm)
   - Validation error display

2. `resources/views/observations/show.blade.php` — add:
   - "Edit" link visible only to the owner: `@can('update', $observation)` → link to edit route
   - "Delete" button visible only to the owner: `@can('delete', $observation)` → form with DELETE + confirmation

**Acceptance:** Owner sees edit/delete controls; non-owner does not. Form submits correctly with method spoofing.

**Status: ✅ DONE**

---

### Phase 5: Tests

**Goal:** Feature tests proving ownership enforcement, edit, and delete flows. Unit tests for service methods.

**Files to create/edit:**

1. `tests/Feature/ManageObservationTest.php`
   - `test_guest_cannot_access_edit_form(): void`
   - `test_owner_can_access_edit_form(): void`
   - `test_non_owner_cannot_access_edit_form(): void`
   - `test_owner_can_update_observation_fields(): void`
   - `test_owner_can_add_new_photos_to_existing_observation(): void`
   - `test_owner_can_remove_a_photo_from_observation(): void`
   - `test_update_fails_if_all_photos_would_be_removed(): void`
   - `test_non_owner_cannot_update_observation(): void`
   - `test_owner_can_delete_observation(): void`
   - `test_non_owner_cannot_delete_observation(): void`
   - `test_delete_removes_files_from_disk(): void`
   - `test_guest_cannot_delete_observation(): void`

2. `tests/Unit/ObservationServiceTest.php` — add:
   - `test_update_observation_modifies_fields(): void`
   - `test_update_observation_adds_new_media(): void`
   - `test_update_observation_removes_specified_resources(): void`
   - `test_delete_observation_removes_record_and_files(): void`

**Acceptance:** All tests pass via `sail artisan test`.

**Status: ✅ DONE**

---

## What We're NOT Doing

- No draft/unpublish toggle (observation stays published after edit)
- No image re-ordering UI (sort_order stays as-is for existing resources; new ones append)
- No image cropping, resizing, or thumbnail regeneration
- No bulk delete (one observation at a time)
- No "undo delete" / soft-delete (hard delete per PRD wording)
- No admin moderation (that's S-04)
- No AJAX/SPA — standard form POST with full-page redirect

## Success Criteria

1. Authenticated owner can navigate to `/observations/{id}/edit` and see pre-filled form
2. Submitting updates modifies the observation and redirects to show
3. Owner can add new photos/videos and remove existing ones
4. At least one photo must remain after edit
5. Owner can delete observation — record + files removed, redirect to home
6. Non-owner attempting edit/update/delete receives 403
7. Guest attempting these actions is redirected to login
8. All existing publish tests remain green (no regression)
9. All new tests pass

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Race condition: concurrent edit + delete | DB transaction + `findOrFail` will throw if record deleted mid-update |
| Orphan files if delete transaction partially fails | Delete files after DB commit, not before; accept small orphan risk on catastrophic failure |
| Resource removal bypasses ownership | Validate `remove_resources.*` IDs belong to the observation being edited (scoped query in service) |
| Breaking existing publish tests | Phase 5 runs full suite; new routes/methods are additive |

## Dependencies

- S-02 publish flow (done)
- F-01 auth scaffold (done)
- No new packages required

## Execution Order

Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 (sequential, each builds on the previous)

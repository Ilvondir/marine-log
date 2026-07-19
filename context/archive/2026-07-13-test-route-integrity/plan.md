# Test Route Integrity — Implementation Plan

## Overview

Phase 2 of the test plan rollout: prove all routes resolve correctly (no shadowing), access levels hold for all user types, and unpublished observations are inaccessible to any visitor. Covers Risk #3 (route collision) and Risk #6 (unpublished observation direct access).

## Current State

- Routes are correctly ordered (literals before wildcard)
- `PublicObservationFeedTest` tests guest access to unpublished (404) — but only for guests
- No test asserts route resolution or non-shadowing
- No test for authenticated user or admin attempting unpublished observation access

## Desired End State

A new test file `tests/Feature/RouteIntegrityTest.php` proves:
- All named routes resolve to correct URIs
- Literal routes (`/observations/create`, `/observations/my`) are not shadowed by the wildcard
- Access levels hold for all combinations (guest, user, admin) × (all routes)
- Unpublished observations return 404 regardless of who accesses them

## What We're NOT Doing

- No e2e/browser tests
- No performance testing
- No testing of external integrations
- No testing of Blade rendering (negative space per test plan §7)

## Phase 1: Route Resolution and Non-Shadowing

### Tests to create in `tests/Feature/RouteIntegrityTest.php`:

1. `test_all_observation_named_routes_resolve_to_expected_uris(): void`
   - Assert `route('observations.index')` = `/observations`
   - Assert `route('observations.create')` = `/observations/create`
   - Assert `route('observations.my')` = `/observations/my`
   - Assert `route('observations.store')` = `/observations`
   - Assert `route('observations.show', 1)` = `/observations/1`
   - Assert `route('observations.edit', 1)` = `/observations/1/edit`
   - Assert `route('observations.update', 1)` = `/observations/1`
   - Assert `route('observations.destroy', 1)` = `/observations/1`

2. `test_create_route_is_not_captured_by_wildcard(): void`
   - GET `/observations/create` as auth user → 200 (form view, not 404/model binding error)

3. `test_my_route_is_not_captured_by_wildcard(): void`
   - GET `/observations/my` as auth user → 200 (my observations view)

4. `test_show_route_resolves_numeric_id_correctly(): void`
   - Create observation, GET `/observations/{id}` → 200 with observation content

## Phase 2: Unpublished Access Hardening

5. `test_authenticated_user_cannot_view_unpublished_observation(): void`
   - Create unpublished observation, actingAs any user → 404

6. `test_owner_cannot_view_own_unpublished_observation(): void`
   - Create unpublished observation owned by user, actingAs that user → 404

7. `test_admin_cannot_view_unpublished_observation(): void`
   - Create unpublished observation, actingAs admin → 404

## Phase 3: Access Level Matrix

8. `test_guest_is_redirected_from_authenticated_routes(): void`
   - GET `/observations/create` → redirect login
   - GET `/observations/my` → redirect login
   - POST `/observations` → redirect login

9. `test_authenticated_user_is_redirected_from_guest_routes(): void`
   - ActingAs user, GET `/login` → redirect home
   - ActingAs user, GET `/register` → redirect home

10. `test_non_admin_cannot_access_admin_dashboard(): void`
    - ActingAs regular user, GET `/admin` → 403

11. `test_admin_can_access_admin_dashboard(): void`
    - ActingAs admin, GET `/admin` → 200

### Success Criteria
- All 11 tests pass
- Full suite remains green

## Progress

> Convention: `- [ ]` pending, `- [x]` done.

### Phase 1: Route Resolution

- [x] 1.1 Create RouteIntegrityTest.php with route resolution tests (4 tests)

### Phase 2: Unpublished Access

- [x] 2.1 Add unpublished access tests (3 tests)

### Phase 3: Access Level Matrix

- [x] 3.1 Add access level tests (4 tests)
- [x] 3.2 Full suite passes

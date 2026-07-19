# Research: test-route-integrity

> Internal research for Phase 2 of the test plan rollout.
> Covers Risk #3 (route ordering/collision) and Risk #6 (unpublished observation access).

## Route Structure (routes/web.php)

Complete route list in registration order:

1. `GET /` → `ObservationController::index` (name: `home`)
2. `GET /login` → `AuthController::createLogin` (guest middleware, name: `login`)
3. `POST /login` → `AuthController::storeLogin` (guest + throttle:5,1, name: `login.store`)
4. `GET /register` → `AuthController::createRegister` (guest, name: `register`)
5. `POST /register` → `AuthController::storeRegister` (guest, name: `register.store`)
6. `POST /logout` → `AuthController::destroy` (auth, name: `logout`)
7. `GET /observations` → `ObservationController::index` (public, name: `observations.index`)
8. `GET /observations/my` → `ObservationController::myObservations` (auth, name: `observations.my`)
9. `GET /observations/create` → `ObservationController::create` (auth, name: `observations.create`)
10. `POST /observations` → `ObservationController::store` (auth, name: `observations.store`)
11. `GET /observations/{observation}/edit` → `ObservationController::edit` (auth, name: `observations.edit`)
12. `PUT /observations/{observation}` → `ObservationController::update` (auth, name: `observations.update`)
13. `DELETE /observations/{observation}` → `ObservationController::destroy` (auth, name: `observations.destroy`)
14. `GET /observations/{observation}` → `ObservationController::show` (public, name: `observations.show`)
15. `GET /admin` → `AdminDashboardController` (auth + admin, name: `admin.dashboard`)

## Route Ordering Critical Points

- Routes 8-9 (`/observations/my`, `/observations/create`) are registered BEFORE the wildcard route 14 (`/observations/{observation}`). This is correct — prevents "my" and "create" being captured as observation IDs.
- If a new literal route is added AFTER the wildcard (line 39), it would be shadowed.
- Route 7 (`/observations` index) is separate from route 1 (`/` home) — both go to same controller method.

## Access Control Matrix

| Route | Guest | Auth (user) | Auth (admin) |
|-------|-------|-------------|--------------|
| GET / | 200 | 200 | 200 |
| GET /login | 200 | redirect(home) | redirect(home) |
| GET /register | 200 | redirect(home) | redirect(home) |
| POST /logout | redirect(login) | redirect(/) | redirect(/) |
| GET /observations | 200 | 200 | 200 |
| GET /observations/my | redirect(login) | 200 | 200 |
| GET /observations/create | redirect(login) | 200 | 200 |
| POST /observations | redirect(login) | 200/302 | 200/302 |
| GET /observations/{id}/edit | redirect(login) | 403 (non-owner) / 200 (owner) | 403 (non-owner) |
| PUT /observations/{id} | redirect(login) | 403 (non-owner) / 302 (owner) | 403 (non-owner) |
| DELETE /observations/{id} | redirect(login) | 403 (non-owner) / 302 (owner) | 403 (non-owner) |
| GET /observations/{id} | 200 (published) / 404 (unpublished) | same | same |
| GET /admin | redirect(login) | 403 | 200 |

## Show Route — Unpublished Access

The show route (`GET /observations/{observation}`) is **public** (no middleware). Controller uses:
```php
abort_unless($observation->published_at !== null, 404);
```
This means:
- Published observation → 200 for everyone
- Unpublished observation → 404 for everyone (including the owner and admins)
- Non-existent ID → 404 (Laravel's route model binding throws ModelNotFoundException)

## Existing Test Coverage for These Risks

### Risk #3 (route collisions) — NO dedicated tests
No test asserts that all named routes resolve to the correct action. Individual tests hit routes, but none prove they don't shadow each other.

### Risk #6 (unpublished access) — PARTIAL coverage
- `PublicObservationFeedTest::test_guest_cannot_view_unpublished_observation` ✅
- No test for authenticated user attempting unpublished access
- No test for observation owner attempting to view their own unpublished observation

## Gaps to Address

1. Route resolution test: all named routes resolve to expected URI and controller action
2. Route non-shadowing: `/observations/create` and `/observations/my` don't get captured by `{observation}` wildcard
3. Authenticated user cannot view unpublished observation (currently only guest tested)
4. Admin cannot view unpublished observation
5. Guest middleware routes redirect authenticated users away

---
date: 2026-07-15T12:00:00+02:00
researcher: kiro
git_commit: 6e31882
branch: master
repository: 10x-project
topic: "Admin moderation: moderate/delete any observation and block user accounts"
tags: [research, codebase, admin, moderation, authorization, policy, user-blocking]
status: complete
last_updated: 2026-07-15
last_updated_by: kiro
---

# Research: Admin Moderation Area

**Date**: 2026-07-15T12:00:00+02:00
**Researcher**: kiro
**Git Commit**: 6e31882
**Branch**: master
**Repository**: 10x-project

## Research Question

What infrastructure exists to support admin moderation (FR-005: admin can moderate/delete any observation; FR-006: admin can view and block user accounts)? What needs to be built, and what patterns should it follow?

## Summary

The project has a solid foundation for admin moderation but needs targeted additions:

1. **Role system exists** — `Role::ADMIN` constant, `User::isAdmin()` helper, `EnsureUserIsAdmin` middleware aliased as `'admin'` — all wired and tested.
2. **Admin route surface exists** — `GET /admin` with dashboard view, protected by `['auth', 'admin']` middleware.
3. **ObservationPolicy only checks ownership** — no admin override. Admin cannot currently edit/delete other users' observations through existing routes.
4. **No user blocking infrastructure** — no `blocked_at` column, no middleware check, no admin UI for users.
5. **ObservationService already has full delete/update logic** — admin moderation can reuse these methods without duplication.

The key architectural decision: admin moderation should use a **separate controller** (`AdminObservationController`, `AdminUserController`) behind the admin middleware group, rather than adding admin logic to the existing `ObservationController`. This keeps the ownership-based authorization (ObservationPolicy) clean and avoids mixing two authorization models in one controller.

## Detailed Findings

### 1. Existing Admin Infrastructure

- **Middleware**: `EnsureUserIsAdmin` (`app/Http/Middleware/EnsureUserIsAdmin.php:17`) — `abort_unless($request->user()?->isAdmin(), 403)`. Registered as `'admin'` alias.
- **Route**: `GET /admin` → `AdminDashboardController` (invokable), protected by `['auth', 'admin']`.
- **View**: `resources/views/admin/dashboard.blade.php` — placeholder confirming role boundary.
- **Role model**: `App\Models\Role` with constants `USER = 'User'`, `ADMIN = 'Admin'`, `HasFactory`.
- **User.isAdmin()**: `$this->role?->name === Role::ADMIN` — simple, clean check.

### 2. Current ObservationPolicy (Ownership Only)

```php
// app/Policies/ObservationPolicy.php
public function update(User $user, Observation $observation): bool
{
    return $user->id === $observation->user_id;
}

public function delete(User $user, Observation $observation): bool
{
    return $user->id === $observation->user_id;
}
```

No `before()` method granting admin override. Two approaches:
- **Option A**: Add `before()` to grant admins all actions — simple but couples admin moderation to user-facing routes.
- **Option B** (recommended): Keep policy ownership-only; admin moderation uses separate routes + middleware. Admin doesn't need policy — the `'admin'` middleware is the gate.

### 3. ObservationService — Reusable for Admin

`ObservationService::deleteObservation(Observation $observation)` handles full cleanup (resources, files, directory, DB records) inside a transaction. Admin delete can call this directly — no duplication needed.

For admin "moderate" (e.g., unpublish), a new method like `unpublishObservation(Observation $observation)` would set `published_at = null`. This is a simple repo update.

### 4. User Blocking — Not Yet Built

**Missing pieces:**
- Migration: `blocked_at` nullable timestamp on `users` table
- Model: `User::isBlocked(): bool` — `return $this->blocked_at !== null`
- Middleware: Check `blocked_at` on every authenticated request → logout + redirect with message
- Admin UI: List users, toggle block
- Service: `AdminService::blockUser(User $user)` and `unblockUser(User $user)`

**Design decision**: `blocked_at` (timestamp) vs. `is_blocked` (boolean). Timestamp is preferred — it records *when* the block happened, useful for audit/support without adding a separate log table.

### 5. Repository Layer — What Needs Adding

Current `ObservationRepositoryInterface` has: `create`, `findById`, `paginatePublished`, `findPublishedById`, `update`, `delete`, `paginateByUser`.

For admin moderation, we need:
- `paginateAll(int $perPage): LengthAwarePaginator` — all observations (published and unpublished), for the admin panel.

For user management, a new `UserRepositoryInterface` with:
- `paginateAll(int $perPage): LengthAwarePaginator`
- `findById(int $id): User`
- `update(int $id, array $data): User`

### 6. Route Structure Plan

```
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard (already exists, move inside group)
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    // Observation moderation
    Route::get('/observations', [AdminObservationController::class, 'index'])->name('observations.index');
    Route::delete('/observations/{observation}', [AdminObservationController::class, 'destroy'])->name('observations.destroy');
    Route::patch('/observations/{observation}/unpublish', [AdminObservationController::class, 'unpublish'])->name('observations.unpublish');

    // User management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
    Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
});
```

### 7. Existing Test Patterns to Follow

From `AuthScaffoldTest` and `ManageObservationTest`:
- Use `RefreshDatabase` trait
- Test triplet: guest (redirect), regular user (403), admin (200/success)
- Use `$this->actingAs($admin)` with factory-created admin user
- Test naming: `test_<actor>_can/cannot_<action>(): void`
- Use route names: `route('admin.observations.index')`
- Assert specific HTTP statuses, session messages, DB state

## Code References

- `app/Http/Middleware/EnsureUserIsAdmin.php:17` — Admin gate middleware
- `app/Models/User.php:49-52` — `isAdmin()` helper
- `app/Models/Role.php:14-16` — Role constants (USER, ADMIN)
- `app/Policies/ObservationPolicy.php:12-25` — Ownership-only policy
- `app/Services/ObservationService.php:131-149` — `deleteObservation()` with full cleanup
- `app/Providers/AppServiceProvider.php:21-22` — Repository bindings
- `routes/web.php:46-48` — Current admin route
- `app/Http/Controllers/AdminDashboardController.php` — Existing admin controller
- `database/migrations/0001_01_01_000000_create_users_table.php:14-28` — Users schema (no blocked_at yet)

## Architecture Insights

1. **Separate admin controllers** — don't pollute `ObservationController` with admin logic. The `'admin'` middleware is the authorization boundary; no policy needed inside admin routes.
2. **Reuse existing service methods** — `deleteObservation()` already handles full cleanup. Add `unpublishObservation()` for moderation without deletion.
3. **New `AdminService`** — for user-management operations (block/unblock). Follows AGENTS.md pattern of services owning workflows.
4. **Repository pattern** — add `paginateAll()` to existing `ObservationRepositoryInterface`; create new `UserRepositoryInterface` + `EloquentUserRepository` for user admin operations.
5. **Blocked user middleware** — must check `blocked_at` on every authenticated request, not just admin routes. Place it in the `auth` middleware group or as global middleware after auth.

## Historical Context (from prior changes)

- `context/changes/auth-scaffold/plan.md` — Established the Role model, admin middleware, and admin dashboard route. Pattern: single role per user, middleware-based admin gate.
- `context/changes/auth-scaffold/reviews/impl-review.md` — Confirmed EnsureUserIsAdmin middleware is correct; admin seeder has production guard.
- `context/changes/own-observation-management/plan.md` — Established the authorization pattern: Policy → Controller → Service. Admin moderation explicitly listed as "NOT doing" (S-04 scope).
- `context/changes/own-observation-management/reviews/impl-review.md` — Confirmed IDOR prevention via service-level scoping (relevant: admin bypasses ownership, so service must accept admin caller without ownership check).

## Open Questions

1. **Should admin be able to edit observations, or only delete/unpublish?** — PRD FR-005 says "moderate or delete." Moderate likely means unpublish (remove from public view) without destroying data. Edit seems out of scope.
2. **Should blocking a user auto-unpublish their observations?** — PRD FR-006 says "view and block accounts." Silent on cascade effect. Recommendation: block only prevents login; existing observations stay published. Admin can separately unpublish if needed.
3. **Admin self-block prevention?** — Should the system prevent an admin from blocking themselves? Yes — add guard in service layer.

---
date: 2026-07-15T14:00:00+02:00
researcher: kiro
git_commit: 23bb485
branch: master
repository: 10x-project
topic: "Where does risk #7 (admin boundary) pass through the code?"
tags: [research, testing, admin, authorization, middleware]
status: complete
last_updated: 2026-07-15
last_updated_by: kiro
---

# Research: Admin Moderation Boundary Testing (Risk #7)

## Research Question

Where does the admin moderation boundary risk pass through the code, what behavior would prove protection, and what is the cheapest test that catches it?

## Summary

Three layers protect admin endpoints. Each is a separate failure point:

1. **Middleware `['auth', 'admin']`** on the route group (`routes/web.php:44`) — gates ALL admin routes. If a route escapes the group, it's unprotected.
2. **`EnsureUserIsAdmin` middleware** (`app/Http/Middleware/EnsureUserIsAdmin.php:17`) — `abort_unless($request->user()?->isAdmin(), 403)`.
3. **`AdminService` guards** (`app/Services/AdminService.php:25-31`) — `blockUser()` throws InvalidArgumentException if target is admin or self.

A fourth layer exists for blocking:
4. **`EnsureUserIsNotBlocked` middleware** (`app/Http/Middleware/EnsureUserIsNotBlocked.php:19-25`) — checks `blocked_at`, forces logout.

## Detailed Findings

### 1. Route Group Structure (where risk enters)

```php
// routes/web.php:44-52
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/observations', ...)->name('observations.index');
    Route::delete('/observations/{observation}', ...)->name('observations.destroy');
    Route::patch('/observations/{observation}/unpublish', ...)->name('observations.unpublish');
    Route::get('/users', ...)->name('users.index');
    Route::patch('/users/{user}/block', ...)->name('users.block');
    Route::patch('/users/{user}/unblock', ...)->name('users.unblock');
});
```

All 7 admin routes are inside one group with `['auth', 'admin']`. The risk: if a developer adds a new admin route OUTSIDE this group, or if the middleware order breaks.

### 2. Admin Middleware Logic

```php
// app/Http/Middleware/EnsureUserIsAdmin.php:17
abort_unless($request->user()?->isAdmin(), 403);
```

Simple — checks `User::isAdmin()` which checks `$this->role?->name === Role::ADMIN`. No edge cases except: what if user has no role? The `?->` handles it (returns null, which !== 'Admin', so 403).

### 3. AdminService Guards (business logic layer)

```php
// app/Services/AdminService.php:25-31
public function blockUser(User $admin, User $target): User
{
    if ($admin->id === $target->id) {
        throw new \InvalidArgumentException('Cannot block your own account.');
    }
    if ($target->isAdmin()) {
        throw new \InvalidArgumentException('Cannot block an admin account.');
    }
    ...
}
```

These are caught in `AdminUserController::block()` and returned as error flash messages. NOT a 403 — a redirect with error.

### 4. Blocked User Middleware

```php
// app/Http/Middleware/EnsureUserIsNotBlocked.php:19-25
if ($request->user()?->isBlocked()) {
    Auth::logout();
    $request->session()->invalidate();
    ...
    return redirect()->route('login')->with('error', 'Your account has been blocked.');
}
```

Registered via `$middleware->appendToGroup('web', ...)` in `bootstrap/app.php:18`.

## What Behavior Would Prove Protection

1. **Access matrix for every admin endpoint**: guest → redirect to login, regular user → 403, admin → 200/302 (success)
2. **AdminService guard: self-block → error flash, not exception bubbling**
3. **AdminService guard: block another admin → error flash**
4. **Blocked user on next request → forced logout + redirect to login with message**
5. **Blocked user cannot re-login** (login attempt with blocked_at set should fail or succeed and immediately redirect — current implementation: user can login but next web request forces logout due to middleware; this is acceptable for MVP)
6. **Admin can delete any observation regardless of ownership** (no policy check in admin controller)
7. **Admin can unpublish any published observation**

## Cheapest Test Layer

**Feature tests** — real HTTP requests through middleware stack with `actingAs()` for different actors. This catches:
- Middleware misconfiguration (route escaping the group)
- Wrong HTTP status codes
- Service guard logic with real controller flow
- Session/flash message behavior

**Unit tests** for `AdminService` — isolated guard logic with mocked repos. Cheaper than feature tests for the pure logic assertions.

No e2e needed — these are all verifiable at the HTTP layer without a browser.

## Code References

- `routes/web.php:44-52` — Admin route group
- `app/Http/Middleware/EnsureUserIsAdmin.php:17` — Admin check
- `app/Http/Middleware/EnsureUserIsNotBlocked.php:19-25` — Block check + logout
- `bootstrap/app.php:18` — Middleware registration
- `app/Services/AdminService.php:25-31` — Self-block and admin-block guards
- `app/Http/Controllers/AdminUserController.php:35-44` — Exception catch → error flash
- `app/Http/Controllers/AdminObservationController.php` — No policy, relies on middleware

## Open Questions

None — the risk surface is fully mapped. Proceed to plan.

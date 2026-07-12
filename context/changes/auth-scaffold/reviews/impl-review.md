<!-- IMPL-REVIEW-REPORT -->
# Implementation Review: Auth Scaffold

- **Plan**: context/changes/auth-scaffold/plan.md
- **Scope**: Phase 1–3 of 3 (full plan review)
- **Date**: 2026-07-12
- **Verdict**: NEEDS ATTENTION
- **Findings**: 0 critical, 4 warnings, 3 observations

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| Plan Adherence | WARNING ⚠️ (1 finding) |
| Scope Discipline | PASS ✅ |
| Safety & Quality | WARNING ⚠️ (2 findings) |
| Architecture | WARNING ⚠️ (1 finding) |
| Pattern Consistency | PASS ✅ |
| Success Criteria | PASS ✅ |

## Findings

### F1 — Conflicting migration strategies for roles

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Plan Adherence
- **Location**: database/migrations/0001_01_01_000000_create_users_table.php + database/migrations/2026_06_21_000001_add_roles_table_and_role_id_to_users.php
- **Detail**: The plan explicitly stated "migration work should be additive: roles and role assignment, not a rewrite of the current auth primitives." Instead, the base migration was rewritten to include `roles` table and non-nullable `role_id` with `restrictOnDelete`. The additive migration also exists but is dead code on fresh installs (its `hasTable`/`hasColumn` guards skip everything). The two define conflicting constraints: base has non-nullable + restrictOnDelete, additive has nullable + nullOnDelete.
- **Fix A ⭐ Recommended**: Remove the additive migration (`2026_06_21_000001_...`) since the base migration already handles roles correctly for fresh installs.
  - Strength: Eliminates dead code and conflicting constraint definitions. Fresh installs are the only scenario (no production DB exists yet).
  - Tradeoff: Loses the "additive" paper trail, but since there's no deployed database this is safe.
  - Confidence: HIGH — project is pre-production, no existing database to migrate.
  - Blind spot: None significant.
- **Fix B**: Revert base migration to stock Laravel and rely solely on the additive migration (make role_id nullable).
  - Strength: Follows the plan's stated principle literally.
  - Tradeoff: nullable role_id means users can exist without a role, adding defensive checks throughout.
  - Confidence: MEDIUM — nullable FK adds complexity downstream.
  - Blind spot: May require changes to seeder and factory logic.
- **Decision**: FIXED via Fix A — removed additive migration

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Services/AuthService.php:26
- **Detail**: `AuthService::register()` calls `Hash::make($password)` before passing to `User::create()`, but the User model has `'password' => 'hashed'` cast which auto-hashes on set. Laravel's cast detects already-hashed values via `Hash::isHashed()` and skips re-hashing, so this doesn't cause a runtime bug — but it's misleading redundant code that could confuse future developers.
- **Fix**: Remove `Hash::make()` and pass plain password: `'password' => $password`. The `hashed` cast handles it.
- **Decision**: FIXED — removed Hash::make(), passing plain password

### F3 — No rate limiting on login endpoint

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: routes/web.php:11
- **Detail**: POST /login has no throttle middleware. Brute-force attacks can try unlimited credentials without delay.
- **Fix**: Add `->middleware('throttle:5,1')` to the login POST route.
- **Decision**: FIXED — throttle:5,1 added to POST /login

### F4 — AuthService uses Auth facade (HTTP coupling in service layer)

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Architecture
- **Location**: app/Services/AuthService.php:36,42,55
- **Detail**: AGENTS.md states "Keep services independent of HTTP objects." The Auth facade internally reads/writes to the session (HTTP layer). `Auth::attempt()` accesses the request, `Auth::login()` writes the session cookie. This couples the service to the HTTP context.
- **Fix A ⭐ Recommended**: Accept as pragmatic deviation for a thin auth scaffold and document it. Auth is inherently session-bound; splitting into "user creation service" + "controller does Auth::login" adds indirection without clear benefit at this scale.
  - Strength: Keeps the code simple and co-located. The controller already manages session regeneration.
  - Tradeoff: Violates the letter of the architecture rule.
  - Confidence: HIGH — standard Laravel auth pattern.
  - Blind spot: If the project later needs API token auth, this service won't be reusable without refactoring.
- **Fix B**: Move Auth::login/attempt/logout into the controller. Service only creates the user and validates credentials (returning bool).
  - Strength: Strict adherence to architecture rule; service becomes testable without HTTP.
  - Tradeoff: Auth logic fragments between controller and service.
  - Confidence: MEDIUM — more code, marginal benefit at current scale.
  - Blind spot: None significant.
- **Decision**: ACCEPTED via Fix A — documented as pragmatic exception in AuthService docblock

### F5 — Trust all proxies without restriction

- **Severity**: ⚠️ OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: bootstrap/app.php:16
- **Detail**: `trustProxies(at: '*')` trusts all upstream proxies, allowing any client to spoof X-Forwarded-For/Proto headers. Acceptable during development but should be locked down for production.
- **Fix**: Document as pre-production assumption. Before production deploy, restrict to actual proxy IPs.
- **Decision**: SKIPPED — pre-production, will address at deploy time

### F6 — Admin seeder uses trivial default password

- **Severity**: ⚠️ OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: database/seeders/DatabaseSeeder.php + database/factories/UserFactory.php
- **Detail**: Admin user (`admin@marinelog.test`) is seeded with factory default password `'password'`. If seeder runs in production, this creates a trivially compromised admin account.
- **Fix**: Add environment guard: `if (app()->environment('production')) { return; }` or use .env-driven password for admin.
- **Decision**: FIXED — production guard added to DatabaseSeeder

### F7 — Additive migration doesn't seed User role before backfill

- **Severity**: ⚠️ OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: database/migrations/2026_06_21_000001_add_roles_table_and_role_id_to_users.php:33-39
- **Detail**: The migration queries `DB::table('roles')->where('name', 'User')->value('id')` to backfill existing users, but never inserts the 'User' role. The query returns null and the backfill is skipped. This is moot if F1 is resolved by removing this migration, but noted for completeness.
- **Fix**: Moot if F1 Fix A is applied (remove the additive migration). Otherwise insert role before query.
- **Decision**: FIXED — moot, additive migration removed in F1

## Success Criteria Verification

### Automated (all pass ✅)

| Criterion | Result |
|-----------|--------|
| Home page route returns 200 | ✅ PASS |
| Shared shell view compiles without Blade errors | ✅ PASS |
| User can register, sign in, and sign out | ✅ PASS (test passes) |
| Regular users denied admin-only access | ✅ PASS (test passes) |
| Seeded admin exists after seeding | ✅ PASS (test passes) |
| Auth feature tests pass | ✅ PASS (5 tests, 34 assertions) |
| Role access tests pass | ✅ PASS |
| Default test suite remains green | ✅ PASS |

### Manual (verified via code inspection)

| Criterion | Result |
|-----------|--------|
| Landing page reads as MarineLog | ✅ Confirmed — custom branding, marine theme |
| Marine theme visible | ✅ Confirmed — full CSS custom properties + ocean palette |
| Auth entry points discoverable | ✅ Confirmed — "Create account" and "Sign in" CTAs |
| English-only UI copy | ✅ Confirmed — all copy in English |

## Extra Changes (not in plan, all justified)

| File | Reason | Assessment |
|------|--------|------------|
| app/Services/AuthService.php | Required by AGENTS.md architecture | Correct |
| app/Http/Controllers/AdminDashboardController.php | Needed for success criteria (admin area) | Correct |
| app/Http/Middleware/EnsureUserIsAdmin.php | Needed to enforce admin boundary | Correct |
| app/Models/Role.php | Implied by roles table + BelongsTo | Correct |
| resources/views/layouts/marine.blade.php | "Shared shell" requires a layout | Correct |
| resources/views/admin/dashboard.blade.php | Admin area destination | Correct |
| .gitignore (.aider*) | Tooling artifact | Harmless |

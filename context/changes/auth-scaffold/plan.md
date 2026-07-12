# Auth Scaffold Implementation Plan

## Overview

We are building the first MarineLog foundation: a web/session-based authentication scaffold with a marine-themed shared shell, role-based access, and coverage that proves the scaffold works. The goal is to turn the current Laravel skeleton into a usable English-only product surface that supports sign-up, sign-in, sign-out, and the admin access needed by the roadmap.

## Current State Analysis

The repository is still a stock Laravel 13 skeleton. `routes/web.php` only serves the welcome page, `bootstrap/app.php` has no auth middleware wiring, and the only real view is the default `welcome.blade.php` with Laravel branding.

The data layer is auth-ready but not scaffolded: the user migration already has `password_reset_tokens` and `sessions`, and the `User` model already hashes passwords and hides sensitive fields. There is no roles table, no admin bootstrap, and no feature coverage for auth flows yet.

The frontend stack is intentionally lean. Tailwind v4 is configured CSS-first, `resources/js/app.js` is empty, and the current visual system is just the stock welcome page. That gives us a clean surface for a single marine-themed shell instead of a fragmented per-page design.

## Desired End State

After this plan is complete, a visitor can register, sign in, and sign out through an English-only Blade experience. The public and auth screens share one marine-themed product shell, so the app feels like MarineLog instead of a default Laravel install.

The application has a proper roles table with `User` and `Admin`, and admin-only access is enforced with the scaffold. A first admin account exists through seeding, so the moderation roadmap can continue without manual database edits.

The auth scaffold is test-backed. Feature tests verify registration, login, logout, and role access, and the main user-facing flows can be exercised without relying on manual setup.

### Key Discoveries:

- `routes/web.php` currently contains only a single welcome route, so the auth scaffold must introduce the full entry-point surface.
- `resources/views/welcome.blade.php` is the entire current UI, which makes a shared marine shell the right place to introduce branding.
- `database/migrations/0001_01_01_000000_create_users_table.php` and `app/Models/User.php` already cover the basic auth primitives, so the scaffold can build on them instead of replacing them.
- The repository has only placeholder tests, so auth coverage needs to be added alongside the scaffold rather than deferred.

## What We'"'"'re NOT Doing

- We are not adding password reset in this change.
- We are not building the observation feature set yet.
- We are not adding a separate API auth surface or mobile-specific auth flow.
- We are not introducing social login, email verification flows, or permission granularity beyond `User` and `Admin`.
- We are not redesigning the entire product, only the shared shell needed for this foundation.

## Implementation Approach

We will introduce the marine look and shared Blade shell first so every screen in scope feels coherent from the start. Then we will wire the session-based auth flow and role model on top of that shell so the account experience and access rules are aligned.

The first admin account will be created through deterministic seeding, not by manual database intervention. Finally, we will lock the scaffold down with feature tests that cover the visible user journeys and the role-based access boundary.

## Critical Implementation Details

The product is fully English-only, so labels, validation messages, and default UI copy should all be written in English from the start. The marine theme should live in the shared shell and global CSS layer, not be duplicated across individual views.

Use a single role per user for this scaffold. The `roles` table should represent the allowed account types, and the rest of the auth flow should treat role checks as a first-class access boundary rather than a one-off conditional.

## Phase 1: Marine Shell and Entry Points

### Overview

Create the shared visual shell for public and auth screens, then replace the stock landing page with MarineLog-branded entry points.

### Changes Required:

#### 1. Shared layout and landing surface

**File**: `resources/views/welcome.blade.php`

**Intent**: Replace the default Laravel landing page with a MarineLog-first entry surface that can support both guest navigation and auth links. The page should feel like the product, not like scaffolding.

**Contract**: The public landing surface must remain accessible at `/`, show MarineLog branding, and present the auth entry points in English.

#### 2. Global visual theme

**File**: `resources/css/app.css`

**Intent**: Move the marine/ocean visual language into the shared stylesheet so the look and feel is consistent across public and auth screens.

**Contract**: The theme layer must define the color and typography tokens that the shared shell and auth screens reuse.

#### 3. Route surface for the scaffold

**File**: `routes/web.php`

**Intent**: Add the public/auth route surface needed for the scaffold so the landing page, sign-in, and sign-up paths have real destinations.

**Contract**: The web routes must expose the guest and authenticated entry points required by the auth flow.

### Success Criteria:

#### Automated Verification:

- The home page route returns `200`.
- The shared shell view compiles without Blade errors.

#### Manual Verification:

- The landing page reads as MarineLog, not default Laravel.
- The marine theme is visible on desktop and mobile.
- Auth entry points are discoverable from the public page.

---

## Phase 2: Registration, Login, Logout, and Roles

### Overview

Add the actual session-based auth flow and the `User`/`Admin` role model that the roadmap depends on.

### Changes Required:

#### 1. Auth flow implementation

**File**: `routes/web.php`

**Intent**: Wire the registration, login, and logout flow into the app so users can actually create and use accounts.

**Contract**: The scaffold must support creating a new account, signing in, and signing out through web routes.

#### 2. Role data model

**File**: `database/migrations/*`

**Intent**: Introduce a roles table and connect users to exactly one role so the app can distinguish between regular users and admins.

**Contract**: The database must store the allowed roles and persist the role assigned to each user.

#### 3. Role-aware access boundary

**File**: `app/Models/User.php`

**Intent**: Teach the user model to participate in role-based access checks so later slices can guard admin-only behavior cleanly.

**Contract**: The auth model must expose the information needed to distinguish `User` from `Admin`.

#### 4. First admin bootstrap

**File**: `database/seeders/DatabaseSeeder.php`

**Intent**: Seed the first admin account so the scaffold can be exercised immediately after setup.

**Contract**: A fresh seeded database must contain at least one admin user that can access admin-only areas.

### Success Criteria:

#### Automated Verification:

- A user can register, sign in, and sign out in feature tests.
- Regular users are denied admin-only access in feature tests.
- The seeded admin exists after database seeding.

#### Manual Verification:

- A new account can be created without touching the database manually.
- A seeded admin can sign in and reach admin-only areas.
- Regular users cannot access admin-only screens.

---

## Phase 3: Auth Coverage and Verification

### Overview

Lock the scaffold down with feature tests so the auth experience stays reliable as the rest of the product grows.

### Changes Required:

#### 1. Feature test coverage

**File**: `tests/Feature/*`

**Intent**: Add database-backed feature tests for the public auth flows and the role boundary.

**Contract**: The test suite must prove registration, login, logout, and access control behavior end to end.

#### 2. Supporting test fixtures

**File**: `database/factories/UserFactory.php`

**Intent**: Reuse and extend the existing user factory as needed so auth tests can create the right account states without brittle setup.

**Contract**: Test data must support both regular and admin account scenarios.

### Success Criteria:

#### Automated Verification:

- Auth feature tests pass.
- Role access tests pass.
- The default application test suite remains green.

#### Manual Verification:

- The auth scaffold behaves consistently after database reset and reseed.
- English-only UI copy remains intact across the main auth screens.

## Testing Strategy

### Unit Tests:

- Keep unit testing minimal for this scaffold unless a small role helper or access rule naturally deserves one.

### Integration Tests:

- Cover registration, login, logout, and admin-only access through feature tests.
- Verify that seeded roles and seeded admin accounts behave correctly in a fresh database.

### Manual Testing Steps:

1. Register a new account through the UI.
2. Sign out and sign back in with the same account.
3. Sign in as the seeded admin and confirm admin-only access is available.
4. Try to access the same admin-only surface as a regular user and confirm it is blocked.

## Performance Considerations

This scaffold should stay lightweight. The only new persistent data in scope is the roles relationship, and the marine shell should be implemented with simple Blade and CSS rather than extra client-side state.

## Migration Notes

The existing `users` table already supports the auth foundation, so the migration work should be additive: roles and role assignment, not a rewrite of the current auth primitives.

## References

- Related roadmap: [context/foundation/roadmap.md](/home/user/10x-project/context/foundation/roadmap.md)
- Current routes: [routes/web.php](/home/user/10x-project/routes/web.php)
- Current user model: [app/Models/User.php](/home/user/10x-project/app/Models/User.php)
- Current user migration: [database/migrations/0001_01_01_000000_create_users_table.php](/home/user/10x-project/database/migrations/0001_01_01_000000_create_users_table.php)
- Current landing page: [resources/views/welcome.blade.php](/home/user/10x-project/resources/views/welcome.blade.php)
- Current global styles: [resources/css/app.css](/home/user/10x-project/resources/css/app.css)
- Current test skeleton: [tests/Feature/ExampleTest.php](/home/user/10x-project/tests/Feature/ExampleTest.php)

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Marine Shell and Entry Points

#### Automated

- [x] 1.1 The home page route returns `200`
- [x] 1.2 The shared shell view compiles without Blade errors

#### Manual

- [x] 1.3 The landing page reads as MarineLog, not default Laravel
- [x] 1.4 The marine theme is visible on desktop and mobile
- [x] 1.5 Auth entry points are discoverable from the public page

### Phase 2: Registration, Login, Logout, and Roles

#### Automated

- [x] 2.1 A user can register, sign in, and sign out in feature tests
- [x] 2.2 Regular users are denied admin-only access in feature tests
- [x] 2.3 The seeded admin exists after database seeding

#### Manual

- [x] 2.4 A new account can be created without touching the database manually
- [x] 2.5 A seeded admin can sign in and reach admin-only areas
- [x] 2.6 Regular users cannot access admin-only screens

### Phase 3: Auth Coverage and Verification

#### Automated

- [x] 3.1 Auth feature tests pass
- [x] 3.2 Role access tests pass
- [x] 3.3 The default application test suite remains green

#### Manual

- [x] 3.4 The auth scaffold behaves consistently after database reset and reseed
- [x] 3.5 English-only UI copy remains intact across the main auth screens

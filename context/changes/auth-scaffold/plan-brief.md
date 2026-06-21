# Auth Scaffold — Plan Brief

> Full plan: `context/changes/auth-scaffold/plan.md`

## What & Why

We are turning the current Laravel skeleton into MarineLog’s first real product foundation: an English-only, web/session-based auth scaffold with a marine-themed shared shell and role-based access. This is the minimum work needed to let users register, sign in, sign out, and prepare the app for admin moderation later.

## Starting Point

Today the app still looks and behaves like a default Laravel install. The only real view is the stock welcome page, there are no auth routes or layouts, and the test suite still contains placeholder examples.

## Desired End State

Users can register, sign in, and sign out through a consistent MarineLog-branded interface. The same shared shell covers public and auth screens, so the product feels coherent from the first visit.

The database has a `roles` structure for `User` and `Admin`, the first admin account is seeded, and admin-only access is enforced. Auth feature tests prove the scaffold works and stays working.

## Key Decisions Made

| Decision | Choice | Why |
| --- | --- | --- |
| Auth scope | Registration, login, and logout only | It matches the roadmap foundation and keeps the scaffold focused. |
| Auth surface | Blade + sessions | It fits the current Laravel web app and keeps the implementation simple. |
| Branding | One shared marine shell for public and auth screens | It gives MarineLog a recognizable identity immediately. |
| Roles | `roles` table with `User` and `Admin` | It is clearer than a boolean flag and fits the future moderation flow. |
| First admin | Seeded admin account | It makes the scaffold usable right after setup. |
| Validation | Standard Laravel form errors | It keeps the UX familiar and reduces unnecessary complexity. |
| Testing | Feature tests for auth and role access | It verifies the user-facing flows that matter most. |

## Scope

**In scope:**
- Marine-themed shared shell for public and auth screens
- Registration, login, and logout
- Role-based access with `User` and `Admin`
- First admin bootstrap through seeding
- Feature tests for the auth scaffold

**Out of scope:**
- Password reset
- Social login
- Email verification flows
- API auth or mobile auth
- Observation features

## Architecture / Approach

The plan keeps the implementation Blade-first and session-based. The marine visual language lives in the shared shell and global CSS, while the auth flow and role checks remain on the server side so later observation work can reuse the same boundaries.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Marine Shell and Entry Points | A branded public/auth surface instead of the stock welcome page | The product can still feel unfinished if the shell is too minimal |
| 2. Registration, Login, Logout, and Roles | Real auth flows plus `User`/`Admin` access control | Role modeling must stay simple and deterministic |
| 3. Auth Coverage and Verification | Feature tests and seeded admin confidence | Test coverage can drift if the scaffold is not exercised end to end |

**Prerequisites:** The existing Laravel skeleton, the current user table, and the current Blade/Tailwind setup.

**Estimated effort:** ~3 implementation sessions across 3 phases.

## Open Risks & Assumptions

- The scaffold will stay English-only across labels, validation, and default messages.
- A single role per user is enough for this MVP foundation.
- The marine theme should stay lightweight and not depend on extra client-side state.

## Success Criteria (Summary)

- A new user can register, sign in, and sign out through the MarineLog UI.
- The app has a seeded admin and enforces admin-only access correctly.
- The auth scaffold is covered by feature tests and feels like MarineLog, not stock Laravel.

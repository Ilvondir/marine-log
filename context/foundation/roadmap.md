---
project: MarineLog
version: 1
status: draft
created: 2026-06-21
updated: 2026-06-21
prd_version: 1
main_goal: low-complexity
top_blocker: capacity
---

# Roadmap: MarineLog

> Derived from `context/foundation/prd.md` (v1) + auto-researched codebase baseline.
> Edit-in-place; archive when superseded.
> Slices below are listed in dependency order. The "At a glance" table is the index.

## Vision recap

MarineLog ma pomóc nurkom-amatorom dokumentować spotkania ze zwierzętami wodnymi wraz ze specjalistycznymi danymi podwodnymi, takimi jak głębokość. Dziś podobne wpisy trafiają do ogólnych platform, które nie są wyspecjalizowane w obserwacjach podwodnych. Produkt skupia się na faunie morskiej i słodkowodnej oraz na publicznym przeglądaniu i publikowaniu obserwacji.

## North star

**S-02: user can create and publish an observation with required fields and at least one photo** — to jest najmniejszy pełny przepływ, który pokazuje główną obietnicę produktu, a więc najlepiej działa jako pierwszy punkt sprawdzający, czy produkt naprawdę rozwiązuje ten problem.

> "North star" means the smallest end-to-end slice that proves the core product hypothesis, placed as early as its prerequisites allow.

## At a glance

| ID | Change ID | Outcome (user can …) | Prerequisites | PRD refs | Status |
|---|---|---|---|---|---|
| F-01 | auth-foundation | (foundation) e-mail/password auth scaffold landed; user/admin access checks are available for later slices | — | FR-002, FR-004, FR-005, FR-006 | done |
| S-01 | public-observation-feed | browse public observations without logging in | — | FR-001 | ready |
| S-02 | publish-observation-flow | create and publish an observation with species, date/time, location, and at least one photo | F-01 | US-01, FR-002, FR-003 | implemented |
| S-03 | own-observation-management | edit or delete their own observation | F-01, S-02 | FR-004 | proposed |
| S-04 | admin-moderation-access | moderate or delete any observation and block user accounts | F-01, S-02 | FR-005, FR-006 | proposed |

## Streams

Navigation aid — groups items that share a Prerequisites chain. Canonical ordering still lives in the dependency graph below; this table is the proposed reading order across parallel tracks.

| Stream | Theme | Chain | Note |
|---|---|---|---|
| A | Public browse | `S-01` | The simplest public-facing value path; it can move without waiting for auth work. |
| B | Account lifecycle | `F-01` → `S-02` → `S-03` → `S-04` | Auth unlocks publishing, then ownership changes, then privileged moderation on the same observation model. |

## Baseline

What's already in place in the codebase as of `2026-06-21` (auto-researched + user-confirmed).
Foundations below assume these are present and do NOT re-scaffold them.

- **Frontend:** partial — default Laravel Vite/Tailwind scaffold is present (`package.json`, `vite.config.js`), but `routes/web.php` still returns the stock welcome view.
- **Backend / API:** partial — Laravel app skeleton only; `routes/web.php` has a single closure route and no domain controllers yet.
- **Data:** partial — only the default `users`, `cache`, and `jobs` migrations exist; no MarineLog domain tables yet.
- **Auth:** partial — `app/Models/User.php` is ready for auth-style password hashing and hidden fields, but login/signup routes and middleware are absent.
- **Deploy / infra:** present — `compose.yaml`, `docker-compose.yml`, `Dockerfile`, and `.github/workflows/ci-deploy.yml` are already wired.
- **Observability:** partial — `laravel/pail` is installed and the dev script runs it, but there is no dedicated tracing/metrics/error-tracking stack.

## Foundations

### F-01: Auth scaffold

- **Outcome:** (foundation) e-mail/password auth scaffold landed; sign-in, sign-out, and role/ownership checks are available for the later slices.
- **Change ID:** auth-foundation
- **PRD refs:** FR-002, FR-004, FR-005, FR-006
- **Unlocks:** S-02, S-03, S-04
- **Prerequisites:** —
- **Parallel with:** S-01
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Keep this to the minimum contract the later slices need so the publish flow does not get buried under a full account-platform detour.
- **Status:** done

## Slices

### S-01: Public browse

- **Outcome:** user can browse public observations without logging in
- **Change ID:** public-observation-feed
- **PRD refs:** FR-001
- **Prerequisites:** —
- **Parallel with:** F-01, S-02
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Start here to prove the public read side with the smallest possible slice; if we postpone it, the app's visible surface shrinks to the private publish flow only.
- **Status:** ready

### S-02: Publish observations

- **Outcome:** user can create and publish an observation with species, date/time, location, and at least one photo
- **Change ID:** publish-observation-flow
- **PRD refs:** US-01, FR-002, FR-003
- **Prerequisites:** F-01
- **Parallel with:** S-01
- **Blockers:** —
- **Unknowns:** —
- **Risk:** This should stay focused on the first end-to-end publish path instead of absorbing edit, moderation, or homepage extras too early.
- **Status:** implemented

### S-03: Own observation management

- **Outcome:** user can edit or delete their own observation
- **Change ID:** own-observation-management
- **PRD refs:** FR-004
- **Prerequisites:** F-01, S-02
- **Parallel with:** S-04
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Do this after the create/publish path so ownership checks and the observation model are already real, which avoids reworking the same persistence and policy code twice.
- **Status:** proposed

### S-04: Admin moderation access

- **Outcome:** admin can moderate or delete any observation and block user accounts
- **Change ID:** admin-moderation-access
- **PRD refs:** FR-005, FR-006
- **Prerequisites:** F-01, S-02
- **Parallel with:** S-03
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Keep privileged controls after the author flow so moderation reuses the same lifecycle and we do not overbuild admin behavior before the core record exists.
- **Status:** proposed

## Backlog Handoff

| Roadmap ID | Change ID | Suggested issue title | Ready for `/10x-plan` | Notes |
|---|---|---|---|---|
| F-01 | auth-foundation | Auth scaffold and role checks | done | Completed 2026-07-12 |
| S-01 | public-observation-feed | Public observation feed | yes | Run `/10x-plan public-observation-feed` |
| S-02 | publish-observation-flow | Publish observation flow | yes | F-01 done; run `/10x-plan publish-observation-flow` |
| S-03 | own-observation-management | Own observation management | no | Depends on publish slice |
| S-04 | admin-moderation-access | Admin moderation and account blocking | no | Depends on publish slice |

## Open Roadmap Questions

1. **Jaka jest jednozdaniowa reguła biznesowa działająca już w MVP?** — Owner: user. Block: roadmap-wide.

## Parked

- **FR-007: odwiedzający może zobaczyć zwierzę dnia na stronie głównej** — Why parked: nice-to-have; the MVP signal comes from public browse plus publish, not from extra homepage content.
- **MVP nie ocenia rzadkości gatunków na podstawie danych IUCN** — Why parked: to jest jawny Non-Goal i pozostaje poza pierwszą wersją.
- **MVP nie generuje alertów migracyjnych ani nie analizuje anomalii pogodowych** — Why parked: to wykracza poza podstawowy przepływ rejestrowania obserwacji.
- **MVP nie agreguje feedów wiadomości z innych stron** — Why parked: nie jest potrzebne do publikowania i przeglądania obserwacji.
- **MVP nie udostępnia użytkownikom mechanizmu zgłaszania obserwacji** — Why parked: moderacja reaktywna zostaje odłożona na późniejszy etap.
- **MVP nie obejmuje osobnej aplikacji mobilnej** — Why parked: pierwszą powierzchnią produktu jest aplikacja webowa.

## Done

| ID | Change ID | Completed |
|---|---|---|
| F-01 | auth-foundation | 2026-07-12 |

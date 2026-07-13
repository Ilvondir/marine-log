# Test Plan

> Phased test rollout for this project. Strategy is frozen at the top
> (§1–§5); cookbook patterns at the bottom (§6) fill in as phases ship.
> Read before writing any new test.
>
> Refresh: re-run `/10x-test-plan --refresh` when stale (see §8).
>
> Last updated: 2026-07-13

## 1. Strategy

Tests follow three non-negotiable principles for this project:

1. **Cost × signal.** The cheapest test that gives a real signal for the
   risk wins. Do not promote to e2e because e2e "feels safer." Do not put a
   vision model on top of a deterministic visual diff that already catches
   the regression.
2. **User concerns are first-class evidence.** Risks anchored in "the
   team is worried about X, and the failure would surface somewhere in
   area Y" carry the same weight as PRD lines or hot-spot data.
3. **Risks are scenarios, not code locations.** This plan documents *what
   could fail* and *why we believe it's likely* — drawn from documents,
   interview, and codebase *signal* (churn, structure, test base). It does
   NOT claim to know which line owns the failure. That knowledge is
   produced by `/10x-research` during each rollout phase. If the plan and
   research disagree about where the failure lives, research is the
   ground truth.

Hot-spot scope used for likelihood weighting: `app/`, `resources/`, `routes/`, `database/`, `tests/`, `config/`.

## 2. Risk Map

The top failure scenarios this project must protect against, ordered by
risk = impact × likelihood. Risks are failure scenarios in user / business
terms, not test names. The Source column cites the *evidence that surfaced
this risk* — never a specific file as "where the failure lives" (that is
research's job, see §1 principle #3).

| # | Risk (failure scenario) | Impact | Likelihood | Source (evidence — not anchor) |
|---|-------------------------|--------|------------|-------------------------------|
| 1 | Zalogowany użytkownik nie może opublikować obserwacji (publish flow regresja — walidacja odrzuca poprawne dane lub service nie tworzy rekordu) | High | High | PRD FR-003, interview Q1, hot-spot dir `app/Services/` (4 commits/30d) |
| 2 | Użytkownik nie-właściciel może edytować/usunąć cudzą obserwację (IDOR — policy nie blokuje) | High | Medium | PRD FR-004, AGENTS.md (ownership enforcement), interview Q3 (policy confidence gap) |
| 3 | Dodanie nowej trasy łamie istniejące endpointy (route ordering/collision — literal captured as wildcard) | Medium | High | Interview Q3, hot-spot `routes/` (7 commits/30d), public-observation-feed plan (explicit risk) |
| 4 | Uploadowane zdjęcia niedostępne publicznie po deploy'u (storage symlink missing lub path mismatch) | High | Medium | Interview Q2 (burned: symlink), publish-observation-flow plan (risk register) |
| 5 | Walidacja formularza przepuszcza nieprawidłowe dane lub blokuje prawidłowe (edge cases: oversized files, invalid coords, future dates, mime type spoofing) | Medium | Medium | Interview Q4, PRD NFR (niedozwolone formaty), publish-observation-flow plan (validation rules) |
| 6 | Gość widzi nieopublikowaną obserwację przez bezpośredni URL (scopePublished nie filtruje w jakimś endpoincie) | High | Low | PRD FR-001 (public = only published), public-observation-feed plan |

### Risk Response Guidance

| Risk | What would prove protection | Must challenge | Context `/10x-research` must ground | Likely cheapest layer | Anti-pattern to avoid |
|------|----------------------------|----------------|--------------------------------------|----------------------|----------------------|
| #1 | Authenticated user POSTs valid data — observation record exists with `published_at` set + media stored + redirect succeeds | "Happy-path POST = publish works" — must also test boundary between valid/invalid to catch over-strict or under-strict rules | Controller-Service-Repo call chain, exact validation rules, file storage path pattern | Feature test (HTTP POST + DB assertion) | Testing only happy path; asserting DB row exists without checking media linkage |
| #2 | User A cannot PUT/DELETE observation owned by User B — 403; User A CAN edit own — 200 | "auth middleware = ownership check" — middleware checks login, not ownership; policy is the guard | ObservationPolicy methods, which controller actions call `authorize()`, route model binding | Feature test (multi-user scenario) | Testing only "guest blocked" without testing "other authenticated user blocked" |
| #3 | After adding new routes, all named routes resolve to correct controller/action and don't shadow each other | "Tests pass = routes are fine" — a new route can pass its own test while breaking an existing one silently | Full route list, registration order in web.php, wildcard vs literal priority | Integration test (route resolution assertions for all observation endpoints) | Testing new route in isolation without asserting existing routes still resolve |
| #4 | Uploaded photo URL returns 200 with image content when accessed via public URL | "File stored = file accessible" — storage and public accessibility are separate steps (store vs symlink vs URL generation) | Storage disk config, symlink presence check, URL generation method | Feature test (upload + HTTP GET on generated URL) | Mocking Storage in the test — hides the real symlink/path problem |
| #5 | Submission with each boundary value (max size, invalid mime, future date, out-of-range coords) is rejected with specific validation error | "Required fields checked = validation works" — required is not boundary; oversized file passes `required` but should fail `max` | Exact validation rules in FormRequest, PHP upload_max_filesize, Laravel file validation behavior | Feature test (parameterized invalid inputs) | Testing only "missing field" without testing "present but invalid" |
| #6 | GET `/observations/{unpublished_id}` returns 404 for guest AND for non-owner auth user | "scopePublished on index = safe" — show route might bypass scope if using plain findOrFail | Show action implementation, whether it uses findPublishedById or plain find | Feature test (access unpublished by ID) | Testing only the index page without testing direct-access-by-ID |

## 3. Phased Rollout

Each row is a discrete rollout phase that will open its own change folder
via `/10x-new`. Status moves left-to-right through the values below; the
orchestrator updates Status as artifacts appear on disk.

| # | Phase name | Goal (one line) | Risks covered | Test types | Status | Change folder |
|---|-----------|-----------------|---------------|------------|--------|---------------|
| 1 | Critical-path coverage | Defend the publish + manage flow and authorization boundary at cheapest layer | #1, #2, #5 | Feature tests (HTTP) + Unit tests (service logic) | complete | context/changes/test-critical-path |
| 2 | Route integrity and access control | Prove all routes resolve correctly and access levels hold after changes | #3, #6 | Integration/Feature tests (route resolution + unauthorized access) | complete | context/changes/test-route-integrity |
| 3 | Storage and media integrity | Prove uploaded media is accessible via public URL end-to-end | #4 | Feature test (real disk, symlink verification) | complete | context/changes/test-storage-integrity |
| 4 | Quality gates wiring | Lock the floor in CI — tests run on every PR, lint + typecheck gate | cross-cutting | CI gates (GitHub Actions) | complete | context/changes/test-quality-gates |

## 4. Stack

The classic test base for this project.

| Layer | Tool | Version | Notes |
|-------|------|---------|-------|
| unit + integration | PHPUnit | 11.x (via Laravel 13) | Configured in phpunit.xml; Unit + Feature suites |
| factories | Laravel model factories | built-in | UserFactory, ObservationFactory, ResourceFactory exist |
| HTTP testing | Laravel HTTP test helpers | built-in | `$this->post()`, `$this->actingAs()`, assertions |
| file upload testing | `UploadedFile::fake()` | built-in | Fake files for upload tests |
| storage testing | `Storage::fake()` | built-in | Use sparingly — real disk needed for Risk #4 |
| e2e (browser) | none yet — see §3 Phase 4 | — | Not required for MVP test plan; CI gates suffice |
| linting | Laravel Pint | latest | `sail pint --test` for style checks |

**Stack grounding tools (current session):**
- Docs: none — no Context7 or framework docs MCP available; checked: 2026-07-13
- Search: web search tool available — used for discovery; checked: 2026-07-13
- Runtime/browser: none — no Playwright MCP; checked: 2026-07-13
- Provider/platform: none — no GitHub/Cloudflare MCP; checked: 2026-07-13

## 5. Quality Gates

The full set of gates that must pass before a change reaches production.

| Gate | Where | Required? | Catches |
|------|-------|-----------|---------|
| lint (Pint) | local + CI | required | code style drift |
| unit + feature tests | local + CI | required after §3 Phase 1 | logic regressions, auth bypass, validation failures |
| route smoke assertions | CI on PR | required after §3 Phase 2 | route shadowing, broken named routes |
| media accessibility check | CI on PR | recommended after §3 Phase 3 | storage path / symlink regressions |
| full suite green gate | CI on PR (GitHub Actions) | required after §3 Phase 4 | any regression reaching main |

## 6. Cookbook Patterns

How to add new tests in this project. Each sub-section is filled in once
the relevant rollout phase ships; before that, the sub-section reads
"TBD — see §3 Phase N."

### 6.1 Adding a unit test

- **Location**: `tests/Unit/` — next to the service name (e.g., `ObservationServiceTest.php`).
- **Naming**: `test_<behavior>(): void` — describe the behavior, not the method name.
- **Pattern**: Mock repository contracts via `Mockery::mock(InterfaceClass::class)`, inject into service constructor. Use `Storage::fake('public')` for file operations.
- **Reference test**: `tests/Unit/ObservationServiceTest.php::test_publish_observation_creates_record_and_media`.
- **Run locally**: `./vendor/bin/sail artisan test --filter=ObservationServiceTest`

### 6.2 Adding a feature test (HTTP flow)

- **Location**: `tests/Feature/` — one file per feature area (e.g., `PublishObservationTest.php`, `ManageObservationTest.php`).
- **Naming**: `test_<behavior>(): void`.
- **Pattern**: Use `$this->actingAs($user)` for auth, `Storage::fake('public')` for uploads, `UploadedFile::fake()` for crafting files, `assertSessionHasErrors()` for validation, `assertDatabaseHas/Missing()` for persistence.
- **Mocking policy**: Do NOT mock — hit the real database, real validation, real policies. Only `Storage::fake` and `UploadedFile::fake` are acceptable fakes.
- **Reference test**: `tests/Feature/PublishObservationTest.php::test_user_can_publish_observation_with_required_fields`.
- **Run locally**: `./vendor/bin/sail artisan test --filter=PublishObservationTest`

### 6.3 Adding a route integrity test

- **Location**: `tests/Feature/RouteIntegrityTest.php`.
- **Naming**: `test_<route_behavior>(): void`.
- **Pattern**: Use `route('name', [], false)` to assert URI without domain. Test non-shadowing by hitting literal paths directly (not via named route) and asserting 200 + expected content. Test access matrix with `actingAs($user)` vs guest and assert redirect/403/200.
- **Reference test**: `tests/Feature/RouteIntegrityTest.php::test_create_route_is_not_captured_by_wildcard`.
- **Run locally**: `./vendor/bin/sail artisan test --filter=RouteIntegrityTest`
- **When to add**: After adding any new route under `/observations/` — add a non-shadowing assertion.

### 6.4 Adding a media/storage test

- **Location**: `tests/Feature/StorageIntegrityTest.php`.
- **Naming**: `test_<storage_behavior>(): void`.
- **Pattern**: Do NOT use `Storage::fake('public')` — the whole point is verifying the real disk + symlink chain. Upload via HTTP POST, then verify the file exists at `public_path('storage') . '/' . $resource->path`. Clean up in `tearDown()` by deleting the observation directory from the real disk.
- **Reference test**: `tests/Feature/StorageIntegrityTest.php::test_uploaded_photo_is_accessible_via_public_url`.
- **Run locally**: `./vendor/bin/sail artisan test --filter=StorageIntegrityTest`
- **Key insight**: Laravel's test HTTP client doesn't serve static files. Verify filesystem accessibility instead of HTTP GET on `/storage/` paths. The symlink test (`test_public_storage_symlink_exists`) catches the deploy failure (Risk #4) at the source.

### 6.5 Per-rollout-phase notes

Phase 1 (Critical-path coverage): Added 19 new tests (11 publish boundary, 6 manage boundary, 2 unit). Key pattern: boundary validation tests use exact limits from FormRequest rules as the oracle. IDOR test proved service-level scoping works even if remove_resources validation is globally scoped. `UploadedFile::fake()->create('file.ext', sizeKB, 'mime/type')` is the way to test invalid MIME types (not `->image()` which always produces valid content).

Phase 2 (Route integrity and access control): Added 11 new tests in `RouteIntegrityTest.php`. Key patterns: `route('name', [], false)` gives relative URI for resolution assertions. Literal path tests (hitting `/observations/create` directly) prove non-shadowing better than named route tests. No `RoleFactory` exists — create Role via `Role::create(['name' => Role::ADMIN])` instead.

Phase 3 (Storage and media integrity): Added 4 tests in `StorageIntegrityTest.php`. Key insight: Laravel's test HTTP client returns 403 for `/storage/*` because it doesn't serve static files (that's the web server's job). Instead, verify the symlink exists + file is physically present at `public_path('storage') . '/' . $path`. This catches the exact deploy failure (missing symlink) at the filesystem level. Always clean up real test files in `tearDown()`.

Phase 4 (Quality gates wiring): CI already had tests + deploy gating. Added `./vendor/bin/pint --test` step. Fixed 5 pre-existing style violations (line_ending, concat_space, phpdoc_types, ordered_imports, fully_qualified_strict_types). Both gates now pass: 80 tests green + 54 files Pint clean.

## 7. What We Deliberately Don't Test

Exclusions agreed during the rollout (Phase 2 interview, Q5). Future
contributors should respect these unless the underlying assumption changes.

- **Blade view snapshots** — layouts change frequently and snapshot tests break constantly without catching real bugs. Re-evaluate if visual regression becomes a real concern. (Source: Phase 2 interview Q5.)
- **Seeders and factories as targets** — these are test infrastructure, not product behavior. Test the behavior they support, not the helpers themselves. Re-evaluate if seed data becomes a production dependency. (Source: Phase 2 interview Q5.)

## 8. Freshness Ledger

- Strategy (§1–§5) last reviewed: 2026-07-13
- Stack versions last verified: 2026-07-13
- AI-native tool references last verified: 2026-07-13

Refresh (`/10x-test-plan --refresh`) when:

- a new top-3 risk surfaces from the roadmap or archive,
- a recommended tool's `checked:` date is older than three months,
- the project's tech stack changes (new framework, new test runner),
- §7 negative-space no longer matches what the team believes.

# Implementation Review: publish-observation-flow (S-02)

**Reviewed:** 2026-07-12
**Plan:** `context/changes/publish-observation-flow/plan.md`
**Status:** PASS with findings

---

## 1. Plan Adherence

All five phases delivered as planned. Architecture matches the contract:
- Thin controller → service → repository contracts (via constructor injection)
- Polymorphic `resources` table as agreed (deviated from original `observation_media` per user request)
- Leaflet.js map picker for lat/lng as agreed
- All success criteria met

**Verdict:** ✅ Fully adherent

---

## 2. Scope Discipline

No scope creep detected. Implementation stays within the "What We're NOT Doing" boundaries:
- No drafts, no thumbnails, no S3, no AJAX, no edit/delete, no feed
- `storage:link` added to `composer setup` (good housekeeping, within scope)

**Verdict:** ✅ Clean

---

## 3. Safety & Quality

### Finding 3.1 — Show route exposes all observations (no published_at check)
- **Severity:** Medium
- **Impact:** Low (MVP has no unpublished observations yet, but S-03 will add them)
- **Detail:** `ObservationController::show()` calls `findById()` which returns any observation regardless of `published_at`. Once drafts exist, unpublished observations would be accessible via direct URL.
- **Recommendation:** Add `->published()` scope to `findById` or add an authorization check. Not urgent for MVP since all observations are published on create.

### Finding 3.2 — Uploaded files not cleaned up on transaction rollback
- **Severity:** Low
- **Impact:** Low (orphan files on disk if DB insert fails after file upload)
- **Detail:** `storeMedia()` writes files to disk then creates DB records. If a later DB insert fails, the transaction rolls back but files remain on disk. MVP traffic is negligible so this is cosmetic.
- **Recommendation:** Accept for now. If needed later, move file storage after all DB inserts succeed, or add a scheduled cleanup job.

### Finding 3.3 — XSS risk in show.blade.php Leaflet popup
- **Severity:** Medium
- **Impact:** Medium
- **Detail:** `show.blade.php` line: `.bindPopup('{{ $observation->species }}')` — if species contains a single quote or HTML, it could break JS or inject content. Blade `{{ }}` escapes HTML but not JS string context.
- **Recommendation:** Use `@js($observation->species)` or `{!! json_encode($observation->species) !!}` for safe JS interpolation.

### Finding 3.4 — `user_id` index is redundant
- **Severity:** Trivial
- **Impact:** None
- **Detail:** Migration adds explicit `$table->index('user_id')` but `foreignId()->constrained()` already creates an index on the FK column in MySQL. Harmless duplicate.
- **Recommendation:** Skip. No runtime impact.

---

## 4. Architecture & Pattern Consistency

### Finding 4.1 — Controller injects concrete service, not interface
- **Severity:** Low
- **Impact:** Low
- **Detail:** `ObservationController` injects `ObservationService` (concrete class) rather than an interface. AGENTS.md says "inject services and repository contracts through constructors." The auth scaffold also injects `AuthService` directly, so this is consistent with existing precedent in the project, just not with the letter of the rule.
- **Recommendation:** Accept as-is. Service interfaces add boilerplate without clear benefit for a solo MVP. Repositories have interfaces because they're explicitly called out in AGENTS.md; services are not.

### Finding 4.2 — `EloquentResourceRepository` assumes morphMany exists on any Model
- **Severity:** Low
- **Impact:** Low
- **Detail:** `createForResourceable(Model $resourceable, ...)` calls `$resourceable->morphMany(...)` without type-checking that the model actually has a `resources()` relation. This works because only Observation uses it today, but could throw a runtime error if misused.
- **Recommendation:** Accept. The interface contract documents intent; misuse would be caught by tests. Adding a trait or interface check would be overengineering for MVP.

---

## 5. Test Coverage

- 11 feature tests cover: auth guard, happy path (required + optional fields), 5 validation edge cases, media persistence
- 2 unit tests verify service orchestration with mocked repos
- Total: 21 tests, 88 assertions — all pass

**Missing but acceptable for MVP:**
- No test for show route (404 on missing observation)
- No test for video-only upload rejection (photos still required)
- No test for concurrent file upload edge cases

**Verdict:** ✅ Good coverage for the slice

---

## 6. Summary

| Category | Verdict |
|----------|---------|
| Plan adherence | ✅ |
| Scope discipline | ✅ |
| Safety & quality | ⚠️ 2 medium findings (3.1, 3.3) |
| Architecture | ✅ |
| Pattern consistency | ✅ |
| Test coverage | ✅ |

**Overall:** PASS. Two medium findings worth triaging:
- **3.1** (show without published check) — safe to skip now, address in S-01 or S-03
- **3.3** (XSS in JS popup) — quick fix, 1 line

---

## Triage Decisions

| # | Finding | Decision | Reason |
|---|---------|----------|--------|
| 3.1 | Show route no published check | Skip | No unpublished observations exist in MVP; S-03 will introduce this concept |
| 3.2 | Orphan files on rollback | Accept as risk | Negligible traffic; cleanup job is overengineering now |
| 3.3 | XSS in Leaflet popup | **Fix now** | 1-line change, real security concern |
| 3.4 | Redundant index | Skip | No impact |
| 4.1 | Concrete service injection | Skip | Matches existing project pattern |
| 4.2 | Loose morphMany typing | Skip | Interface documents intent; tests catch misuse |

# Implementation Review: own-observation-management (S-03)

**Reviewed:** 2026-07-12
**Plan:** `context/changes/own-observation-management/plan.md`
**Status:** PASS with findings

---

## 1. Plan Adherence

All five phases delivered as planned:
- Phase 1 (Authorization): ObservationPolicy with ownership check, registered in AppServiceProvider via `Gate::policy()` ✅
- Phase 2 (Repository & Service): `update()`, `delete()`, `deleteForResourceable()`, `deleteById()`, service orchestration ✅
- Phase 3 (Controller, Routes, FormRequest): edit/update/destroy actions, UpdateObservationRequest with custom "at least 1 photo" validation ✅
- Phase 4 (Blade Views): edit form with media management + show view with conditional edit/delete buttons ✅
- Phase 5 (Tests): 12 feature tests + 4 unit tests ✅

**Verdict:** ✅ Fully adherent

---

## 2. Scope Discipline

Implementation stays within plan boundaries:
- No draft/unpublish toggle
- No soft-delete
- No image reordering
- No admin moderation (S-04)
- No bulk operations

**Verdict:** ✅ Clean

---

## 3. Safety & Quality

### Finding 3.1 — IDOR prevention in resource removal (positive)
- **Severity:** Positive finding
- **Detail:** `removeResources()` in the service scopes the query to `$observation->resources()->whereIn('id', $resourceIds)` — this prevents a user from deleting resources belonging to another observation by guessing IDs. Well done.

### Finding 3.2 — `remove_resources.*` validation uses `exists:resources,id` but no ownership scope
- **Severity:** Medium
- **Impact:** Low (mitigated by service-level scoping)
- **Detail:** `UpdateObservationRequest` validates `remove_resources.*` with `exists:resources,id` — this confirms the resource ID exists globally but doesn't check it belongs to the current observation. However, the service's `removeResources()` scopes to the observation, so a foreign ID would simply be ignored (not deleted). The request passes validation but the foreign resource remains untouched.
- **Recommendation:** Accept. The service provides the real security layer. Adding a custom rule would be defense-in-depth but not functionally necessary.

### Finding 3.3 — File deletion inside DB transaction
- **Severity:** Low
- **Impact:** Low
- **Detail:** `deleteObservation()` deletes files from disk inside `DB::transaction()`. If file deletion fails (disk error), the transaction rolls back — but if DB commit succeeds and a later file delete fails, files may remain. The current order is: collect paths → delete DB records → delete files. This is the correct order (DB first, then files) but all within one transaction closure.
- **Recommendation:** Accept. For MVP traffic this is fine. The plan explicitly acknowledges this as an accepted risk.

### Finding 3.4 — `update()` in repository returns `$observation->fresh()` (extra query)
- **Severity:** Trivial
- **Impact:** Negligible
- **Detail:** After `$observation->update($data)`, the repo calls `->fresh()` which triggers an extra SELECT. The model already has updated attributes in memory after `update()`. Using `fresh()` ensures all casts/defaults are re-applied from DB.
- **Recommendation:** Accept. Correctness over micro-optimization.

### Finding 3.5 — Policy uses strict `===` comparison (positive)
- **Severity:** Positive finding
- **Detail:** `$user->id === $observation->user_id` uses strict comparison. Both are int (from DB), so this is correct and safe against type juggling.

---

## 4. Architecture & Pattern Consistency

- Thin controller → `$this->authorize()` → service → repository ✅
- Policy ownership check separate from business logic (per plan) ✅
- Service handles transactions + file cleanup ✅
- Repository logs errors with correct context keys (per AGENTS.md) ✅
- `AuthorizesRequests` trait added to base Controller ✅
- Policy registered via `Gate::policy()` in AppServiceProvider (modern Laravel pattern) ✅
- FormRequest with custom `withValidator()` for "at least 1 photo remains" logic ✅
- `@can` directives in Blade for conditional UI (not role-based, authorization-based) ✅

**Verdict:** ✅ Consistent with AGENTS.md and project patterns

---

## 5. Test Coverage

**Feature tests (12):**
- Guest/owner/non-owner access to edit form (3)
- Owner update with fields/photos/remove (3)
- Validation: all photos removed (1)
- Non-owner update attempt (1)
- Owner/non-owner/guest delete (3)
- File cleanup on delete (1)

**Unit tests (4):**
- Update modifies fields
- Update adds new media
- Update removes specified resources
- Delete removes record and files

**Missing but acceptable:**
- No test for guest accessing update/PUT endpoint (tested indirectly via middleware redirect)
- No test for editing with map coordinates changed (covered by generic update test)

**Verdict:** ✅ Strong coverage — all success criteria from plan covered

---

## 6. Summary

| Category | Verdict |
|----------|---------|
| Plan adherence | ✅ |
| Scope discipline | ✅ |
| Safety & quality | ✅ (1 medium finding, accepted) |
| Architecture | ✅ |
| Pattern consistency | ✅ |
| Test coverage | ✅ |

**Overall:** PASS. Clean implementation of FR-004. Authorization is well-structured (Policy → controller → service stays auth-agnostic). Media management handles add/remove within transactions with IDOR prevention.

---

## Triage Decisions

| # | Finding | Decision | Reason |
|---|---------|----------|--------|
| 3.1 | IDOR prevention in removeResources | Accept (positive) | Correct pattern |
| 3.2 | exists validation without ownership scope | Accept | Service provides real security; request-level check is convenience |
| 3.3 | File deletion inside transaction | Accept as risk | Plan documented this; MVP traffic |
| 3.4 | Extra fresh() query | Skip | Correctness > micro-optimization |
| 3.5 | Strict === in policy | Accept (positive) | Correct and safe |

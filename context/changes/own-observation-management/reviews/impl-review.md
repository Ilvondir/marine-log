# Implementation Review: own-observation-management (S-03)

**Reviewed:** 2026-07-12
**Plan:** `context/changes/own-observation-management/plan.md`
**Status:** PASS with findings

---

## 1. Plan Adherence

All five phases delivered as planned:
- Phase 1: ObservationPolicy with update/delete, registered via Gate::policy
- Phase 2: Repository contracts extended (update/delete), implementations with logging, service methods
- Phase 3: Controller actions (edit/update/destroy), UpdateObservationRequest, three new routes
- Phase 4: Edit view with pre-filled form + media management, show view with @can-gated controls
- Phase 5: 12 feature tests + 4 unit tests covering ownership, CRUD, and file cleanup

Architecture matches the contract:
- Thin controller → `$this->authorize()` (Policy) → service → repository contracts
- Service receives already-authorized Observation instance (no ownership logic in service)
- IDOR protection via scoped resource query in service

**Verdict:** ✅ Fully adherent

---

## 2. Scope Discipline

No scope creep detected. Implementation stays within the "What We're NOT Doing" boundaries:
- No draft/unpublish toggle
- No image re-ordering, cropping, or thumbnails
- No soft-delete (hard delete per PRD)
- No admin moderation (S-04)
- No AJAX — standard form POST + redirect

One minor addition beyond plan: `show()` signature changed from `show(int $observation)` to `show(Observation $observation)` for route model binding consistency with new actions. This is a quality improvement, not scope creep.

**Verdict:** ✅ Clean

---

## 3. Safety & Quality

### Finding 3.1 — `deleteObservation` deletes files inside transaction (not after commit)
- **Severity:** Low
- **Impact:** Low
- **Detail:** `deleteObservation()` collects paths, deletes DB records, then deletes files from disk — all inside `DB::transaction()`. If the `deleteDirectory()` call or a late file deletion fails, the DB changes roll back but earlier file deletions are already permanent. The plan noted "delete files after DB commit" as a mitigation for orphan risk, but the implementation deletes within the transaction.
- **Recommendation:** Accept for MVP. Failure mid-file-delete is extremely unlikely with local disk, and the consequence (DB rolls back, some files gone) leaves orphan records that `findOrFail` would still resolve. A proper fix would use `DB::afterCommit()` but adds complexity for negligible gain at current traffic.

### Finding 3.2 — `remove_resources.*` validation uses `exists:resources,id` without scoping
- **Severity:** Medium
- **Impact:** Low (mitigated at service layer)
- **Detail:** `UpdateObservationRequest` validates `remove_resources.*` with `exists:resources,id` — this passes validation for ANY resource ID in the database, not just those belonging to the current observation. However, the service's `removeResources()` method scopes the query to `$observation->resources()->whereIn('id', ...)` so an attacker cannot delete another observation's resources.
- **Defense in depth says:** validation should reject IDs that don't belong to this observation. The service layer provides the real protection, so the risk is limited to a misleading validation pass.
- **Recommendation:** Consider adding a custom validation rule scoped to `$observation->resources` for extra defense-in-depth. Low priority since the service already prevents the exploit.

### Finding 3.3 — `storeMedia` sort_order resets to 0 for new uploads
- **Severity:** Low
- **Impact:** Low (cosmetic)
- **Detail:** When adding new photos via `updateObservation()`, the `storeMedia` method assigns `sort_order` starting at 0 based on `$index` in the foreach loop. This means new photos get sort_order 0, 1, 2... which can collide with existing photos' sort_order values. No feature currently depends on sort_order for display ordering.
- **Recommendation:** Accept. The existing create flow has the same pattern. If image ordering becomes a feature, a dedicated sort endpoint would resolve this properly.

### Finding 3.4 — Show route still lacks published_at scope (carried from S-02 review finding 3.1)
- **Severity:** Low (unchanged from S-02 review)
- **Impact:** Low
- **Detail:** S-03 does not introduce drafts — all observations keep `published_at` set. The `show()` action still uses route model binding without a `published()` scope. This was accepted in S-02 review as "address in S-01 or S-03" but remains unchanged because S-03 doesn't create unpublished states.
- **Recommendation:** Skip. Still safe — no unpublished observations exist. Address when S-01 (public feed) introduces public vs private visibility.

### Finding 3.5 — Edit view has `id="delete-form"` conflicting with show view
- **Severity:** Trivial
- **Impact:** None (different pages)
- **Detail:** Both `edit.blade.php` and `show.blade.php` use `id="delete-form"` for the delete form. Since they're separate pages, there's no actual DOM conflict.
- **Recommendation:** Skip. No impact.

---

## 4. Architecture & Pattern Consistency

### Finding 4.1 — Dual authorization: Policy + `$this->authorize()` in update action
- **Severity:** Trivial
- **Impact:** None (defensive, not harmful)
- **Detail:** The `update()` controller action calls `$this->authorize('update', $observation)` AND the `UpdateObservationRequest::authorize()` returns `true` (relying on the controller's policy check). This is the correct pattern — FormRequest handles validation, controller handles authorization. Clean separation.
- **Recommendation:** None. This is the intended design.

### Finding 4.2 — Service accepts Observation model directly (not just ID)
- **Severity:** Trivial
- **Impact:** None
- **Detail:** `updateObservation()` and `deleteObservation()` accept an `Observation` model instance rather than just an ID. This differs from the repository pattern (which uses IDs) but is intentional — the controller already has the model from route binding and policy checks. Passing the model avoids a redundant `findById` call in the service.
- **Recommendation:** None. Good pragmatic choice.

### Finding 4.3 — Controller still injects concrete ObservationService (consistent with S-02)
- **Severity:** Low
- **Impact:** None
- **Detail:** Same pattern as S-02 (accepted in prior review). Consistent with project conventions.
- **Recommendation:** Skip per prior decision.

---

## 5. Test Coverage

### Feature tests (ManageObservationTest): 12 tests
- ✅ Guest guard: edit (redirect), delete (redirect)
- ✅ Owner access: edit form, update fields, add photos, remove photos, delete
- ✅ Non-owner guard: edit (403), update (403), delete (403)
- ✅ Business rule: update fails if all photos would be removed
- ✅ File cleanup: delete removes files from disk

### Unit tests (ObservationServiceTest): 4 new tests (6 total)
- ✅ Update modifies fields via repository
- ✅ Update adds new media to disk
- ✅ Update removes specified resources (scoped)
- ✅ Delete removes records and files

**Missing but acceptable for MVP:**
- No test for updating with invalid `remove_resources` IDs (belonging to another observation) — covered by service scoping
- No test for edit form re-population after validation failure (Blade convention, low risk)
- No test for concurrent edit + delete race condition (documented risk, accepted)
- No test for oversized photo upload on update (covered by `StoreObservationRequest` parity)

**Verdict:** ✅ Good coverage for the slice

---

## 6. NFR Compliance

- **"User cannot edit or delete observation belonging to another person"** (PRD NFR) — enforced by ObservationPolicy, tested with 403 assertions ✅
- **Repository error logging** (AGENTS.md) — all new repo methods log with required context keys before rethrowing ✅
- **Thin controllers** (AGENTS.md) — each action: authorize → delegate to service → return response ✅
- **Constructor injection** (AGENTS.md) — no `app()` calls, all dependencies injected ✅
- **No secrets logged** (AGENTS.md) — logging only uses entity_id, class names, and messages ✅

---

## 7. Summary

| Category | Verdict |
|----------|---------|
| Plan adherence | ✅ |
| Scope discipline | ✅ |
| Safety & quality | ⚠️ 1 medium finding (3.2) |
| Architecture | ✅ |
| Pattern consistency | ✅ |
| Test coverage | ✅ |
| NFR compliance | ✅ |

**Overall:** PASS. One medium finding worth noting:
- **3.2** (remove_resources validation not scoped to observation) — real protection is in the service layer; validation-level scoping is defense-in-depth improvement, not a blocker

---

## Triage Decisions

| # | Finding | Decision | Reason |
|---|---------|----------|--------|
| 3.1 | File delete inside transaction | Accept as risk | Local disk, negligible failure probability, solo MVP traffic |
| 3.2 | Unscoped exists validation | Accept | Service layer prevents IDOR; defense-in-depth is nice-to-have |
| 3.3 | sort_order collision on new uploads | Accept | No feature depends on sort_order; same pattern as create |
| 3.4 | Show route no published scope | Skip | No unpublished observations exist; address in S-01 |
| 3.5 | Duplicate form ID across pages | Skip | No DOM conflict |
| 4.1 | Dual auth pattern | Correct | Intended separation of concerns |
| 4.2 | Model instance in service | Correct | Pragmatic, avoids redundant lookup |
| 4.3 | Concrete service injection | Skip | Matches project precedent |

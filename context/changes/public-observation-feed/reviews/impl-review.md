# Implementation Review: public-observation-feed (S-01)

**Reviewed:** 2026-07-12
**Plan:** `context/changes/public-observation-feed/plan.md`
**Status:** PASS with findings

---

## 1. Plan Adherence

All four phases delivered as planned:
- Phase 1: Repository contract extended with `paginatePublished()` and `findPublishedById()`; service wraps both
- Phase 2: Routes restructured — `/observations/create` (auth) before wildcard `{observation}` (public); `index()` method added; `show()` now uses `findPublishedById()`
- Phase 3: Index view with Leaflet map, responsive card grid, pagination, empty state; show view reworked to two-column gallery layout with photo lightbox
- Phase 4: 9 feature tests + 2 unit tests written (syntax-verified, not runtime-executed due to worktree/Sail separation)

**Previous S-02 finding 3.1 (show route without published check) — resolved here.** The `show()` method now calls `findPublishedById()` which scopes to `published()`, returning 404 for unpublished observations.

**Verdict:** ✅ Fully adherent

---

## 2. Scope Discipline

No scope creep. Implementation stays within "What We're NOT Doing":
- No search/filtering, no infinite scroll, no species taxonomy
- No caching layer, no image resizing, no external lightbox library
- No "Animal of the Day" (FR-007)
- Lightbox is pure vanilla JS + CSS — no added dependencies

**Verdict:** ✅ Clean

---

## 3. Safety & Quality

### Finding 3.1 — XSS risk in index.blade.php Leaflet popup (carried over from S-02 pattern)
- **Severity:** Medium
- **Impact:** Medium
- **Detail:** In `index.blade.php`, the marker popup uses string concatenation: `'<a href="' + obs.url + '">' + obs.species + '</a>'`. The `obs.species` value comes from `@json(...)` which properly escapes for JS context, but when injected as innerHTML in a popup, a species name like `<img onerror=alert(1)>` would execute. The `@json` directive escapes quotes/backslashes but not HTML entities inside JS strings used as innerHTML.
- **Recommendation:** Escape HTML in the popup: use `obs.species.replace(/</g, '&lt;')` or use Leaflet's `bindPopup(document.createTextNode(...))` pattern instead of HTML string.

### Finding 3.2 — show.blade.php uses `@js()` for popup (correctly fixed from S-02)
- **Severity:** None (observation only)
- **Detail:** The show view correctly uses `@js($observation->species)` for the Leaflet popup, which is safe. Good pattern. The index view should follow the same approach.

### Finding 3.3 — Lightbox `img.src` set to empty string on close
- **Severity:** Trivial
- **Impact:** None
- **Detail:** `closeLightbox()` sets `lightboxImg.src = ''` which triggers a (harmless) network request to the page URL in some browsers. Setting to `about:blank` or a data URI would be cleaner.
- **Recommendation:** Accept. No functional impact.

### Finding 3.4 — `photos` relation eager-loaded in full for feed (no limit)
- **Severity:** Low
- **Impact:** Low at MVP scale
- **Detail:** `paginatePublished()` eager-loads all `photos` for each observation but only the first is used for the thumbnail. With many photos per observation, this loads unnecessary data.
- **Recommendation:** Accept for MVP (12 items × small photo count). Optimize with `->with(['photos' => fn($q) => $q->orderBy('sort_order')->limit(1)])` if needed later. Note: Laravel doesn't perfectly limit eager-loaded morphMany per-parent, so a subquery or post-load truncation would be needed.

---

## 4. Architecture & Pattern Consistency

### Positive observations:
- Repository interface properly extended — contract-first design maintained
- Structured logging with context keys (`repository`, `method`, `operation`, `entity_id`, `exception`, `message`) consistent across all repository methods
- Service methods are thin pass-throughs for reads — correct for MVP without business logic on the read path
- Controller remains thin: calls service, returns view
- Constructor injection maintained throughout

### Finding 4.1 — `findById()` method retained but now unreachable
- **Severity:** Trivial
- **Impact:** None
- **Detail:** `ObservationService::findById()` still exists but no controller calls it (show now uses `findPublishedById()`). It's still in the interface and may be used by future slices (S-03 for owner editing).
- **Recommendation:** Keep. S-03 will need it for authorized users editing their own observations.

**Verdict:** ✅ Consistent with established patterns

---

## 5. Frontend & Accessibility

### Positive observations:
- Lightbox uses `role="dialog"`, `aria-hidden`, `aria-label` attributes
- Photo thumbnails have `role="button"`, `tabindex="0"`, and keyboard event handlers (Enter/Space)
- Focus management: focus moves to close button on open, returns to trigger thumbnail on close
- Keyboard navigation: ESC closes, arrows navigate between photos
- Cards have descriptive alt text on images
- Pagination has `aria-label="Pagination"` on nav element
- Empty state is semantic and friendly

### Finding 5.1 — Lightbox nav buttons visible even with single photo
- **Severity:** Trivial
- **Impact:** Cosmetic
- **Detail:** If an observation has exactly one photo, the prev/next buttons still render and navigate (wrapping back to the same photo). Functionally harmless but slightly confusing.
- **Recommendation:** Accept. Could hide buttons with `if (photos.length <= 1)` but it's cosmetic.

### Finding 5.2 — Body scroll not locked when lightbox is open
- **Severity:** Low
- **Impact:** Minor UX
- **Detail:** When lightbox opens, the page body can still scroll behind the overlay. On mobile, touch scrolling might accidentally scroll the background.
- **Recommendation:** Add `document.body.style.overflow = 'hidden'` on open and restore on close. Not blocking for MVP.

**Verdict:** ✅ Good accessibility baseline

---

## 6. Test Coverage

### Feature tests (9):
- Guest access to feed ✓
- Published-only filtering ✓
- Pagination verification ✓
- Newest-first ordering ✓
- Guest views published observation ✓
- Guest blocked from unpublished (404) ✓
- Authenticated user sees feed ✓
- Empty state rendering ✓
- Card content (species + location) ✓

### Unit tests (2 new):
- `getPublishedFeed` delegates to repository with correct args ✓
- `findPublishedById` propagates ModelNotFoundException ✓

**Note:** Tests are syntax-verified but not runtime-executed. The worktree (`10x-project-s1`) doesn't have a running Sail instance. Tests should be run after merge to main worktree.

**Missing but acceptable:**
- No test for map marker rendering (JS behavior, requires browser test)
- No test for lightbox behavior (requires browser test)
- No test for pagination page=2 content (only asserts link exists)

**Verdict:** ⚠️ Adequate coverage; runtime verification pending

---

## 7. Summary

| Category | Verdict |
|----------|---------|
| Plan adherence | ✅ |
| Scope discipline | ✅ |
| Safety & quality | ⚠️ 1 medium finding (3.1) |
| Architecture | ✅ |
| Pattern consistency | ✅ |
| Frontend & accessibility | ✅ |
| Test coverage | ⚠️ Syntax-only (pending runtime) |

**Overall:** PASS. One medium finding worth fixing:
- **3.1** (XSS in index map popup) — quick fix, same class of issue as the S-02 review flagged

---

## Triage Decisions

| # | Finding | Decision | Reason |
|---|---------|----------|--------|
| 3.1 | XSS in index popup innerHTML | **Fix now** | Same pattern as S-02 finding 3.3; simple escaping fix |
| 3.3 | Empty src on lightbox close | Skip | No functional impact |
| 3.4 | Full photos eager-load | Accept as risk | MVP scale; optimize if perf degrades |
| 4.1 | Unreachable `findById()` | Keep | Needed by S-03 |
| 5.1 | Nav buttons on single photo | Skip | Cosmetic |
| 5.2 | Body scroll not locked | Skip | Minor UX; not blocking |

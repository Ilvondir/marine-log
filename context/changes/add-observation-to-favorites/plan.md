# Add Observation to Favorites — Implementation Plan

## Overview

Logged-in users can favorite/unfavorite published observations via a star icon (AJAX toggle). A count of how many users favorited the observation is shown in parentheses next to the star when ≥1. Guests see the count but cannot interact. Users have a dedicated "My Favorites" page listing their favorited observations.

## Current State

- `User` model has `hasMany` to observations and `belongsTo` role — no favorites infrastructure
- `Observation` model has polymorphic `resources` — no favorites relationship
- All user interactions (publish, edit, delete) use standard form POSTs with full page reload
- No custom JavaScript beyond Leaflet map and inline lightbox
- Repository pattern with interface → Eloquent implementation with error logging
- Service layer orchestrates business logic; controllers are thin

### Key findings:

- `app/Providers/AppServiceProvider.php:21-23` — binding pattern for repository interfaces
- `app/Models/User.php` — uses `#[Fillable([...])]` attribute, typed relationships
- `database/migrations/2026_07_12_000001_create_observations_table.php` — migration naming pattern
- `resources/views/observations/index.blade.php:17-36` — card structure where star will be placed
- `resources/views/observations/show.blade.php:13-17` — header area where star will be placed

## Desired End State

- Pivot table `favorites` with `user_id`, `observation_id`, timestamps, and unique constraint
- `User::favorites()` and `Observation::favoritedBy()` as `belongsToMany` relationships
- `FavoriteService` with `toggle()`, `isFavorited()`, `getFavoritesCount()`, `getUserFavorites()`
- `FavoriteController` with JSON endpoint for AJAX toggle + Blade page for list
- Star icon (★/☆) with count on observation cards (feed) and detail page
- Vanilla JS fetching POST endpoint, toggling star state and count without reload
- `/observations/favorites` page showing paginated list of user's favorited observations
- Guest sees count but star is non-interactive (shows tooltip "Log in to favorite")

## What We're NOT Doing

- No notifications when someone favorites your observation
- No "most favorited" ranking or sorting by favorites count
- No optimistic UI (wait for server response before updating star)
- No favorite for unpublished observations (only published)
- No limit on number of favorites per user
- No favorite counter cache column on observations (query-time count for MVP)

## Implementation Approach

Lean many-to-many with a pivot table. The toggle is idempotent (POST same endpoint = add or remove). Count is computed via `withCount('favoritedBy')` eager-loaded on feed/show queries. JavaScript is minimal vanilla JS — no build step changes needed, inline `<script>` similar to the existing lightbox pattern.

## Critical Implementation Details

- **Route ordering**: `/observations/favorites` must be registered BEFORE the `{observation}` wildcard to avoid route capture (same pattern as `/observations/my` and `/observations/create`).

---

## Phase 1: Data Layer

### Overview

Create the pivot table migration and add `belongsToMany` relationships to User and Observation models.

### Required Changes:

#### 1. Migration: create favorites table

**File**: `database/migrations/2026_07_19_000001_create_favorites_table.php`

**Purpose**: Pivot table linking users to observations they favorited.

**Contract**:
```php
Schema::create('favorites', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('observation_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['user_id', 'observation_id']);
    $table->index('observation_id');
});
```

#### 2. User model: favorites relationship

**File**: `app/Models/User.php`

**Purpose**: Add `favorites(): BelongsToMany` returning favorited observations.

**Contract**: `public function favorites(): BelongsToMany` — returns `$this->belongsToMany(Observation::class, 'favorites')->withTimestamps()`.

#### 3. Observation model: favoritedBy relationship

**File**: `app/Models/Observation.php`

**Purpose**: Add `favoritedBy(): BelongsToMany` returning users who favorited this observation.

**Contract**: `public function favoritedBy(): BelongsToMany` — returns `$this->belongsToMany(User::class, 'favorites')->withTimestamps()`.

### Success Criteria:

#### Automated:

- Migration applies cleanly: `sail artisan migrate`
- Migration rollback works: `sail artisan migrate:rollback`
- Relationships resolve correctly (tinker smoke check)
- Pint passes: `sail pint --test`

#### Manual:

- Confirm table exists with correct columns in database

---

## Phase 2: Repository + Service Layer

### Overview

Create `FavoriteRepositoryInterface` + `EloquentFavoriteRepository` and `FavoriteService` with toggle/query logic.

### Required Changes:

#### 1. FavoriteRepositoryInterface

**File**: `app/Contracts/Repositories/FavoriteRepositoryInterface.php`

**Purpose**: Contract for favorite persistence operations.

**Contract**:
```php
interface FavoriteRepositoryInterface
{
    public function toggle(int $userId, int $observationId): bool; // returns true if added, false if removed
    public function isFavorited(int $userId, int $observationId): bool;
    public function countForObservation(int $observationId): int;
    public function paginateByUser(int $userId, int $perPage = 12): LengthAwarePaginator;
}
```

#### 2. EloquentFavoriteRepository

**File**: `app/Repositories/EloquentFavoriteRepository.php`

**Purpose**: Eloquent implementation with error logging following existing pattern.

**Contract**: Implements `FavoriteRepositoryInterface`. `toggle()` checks existence and either attaches or detaches. Uses `User::find($userId)->favorites()` for queries. Logs errors with context keys: repository, method, operation, entity_id, exception, message.

#### 3. FavoriteService

**File**: `app/Services/FavoriteService.php`

**Purpose**: Business logic layer for favorites — validates observation is published before toggling.

**Contract**:
```php
class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly ObservationRepositoryInterface $observationRepository,
    ) {}

    public function toggleFavorite(int $userId, int $observationId): array; // ['favorited' => bool, 'count' => int]
    public function isFavorited(int $userId, int $observationId): bool;
    public function getFavoritesCount(int $observationId): int;
    public function getUserFavorites(int $userId, int $perPage = 12): LengthAwarePaginator;
}
```

Guard: `toggleFavorite` throws `\InvalidArgumentException` if observation is not published.

#### 4. Register binding in AppServiceProvider

**File**: `app/Providers/AppServiceProvider.php`

**Purpose**: Bind `FavoriteRepositoryInterface` → `EloquentFavoriteRepository`.

**Contract**: `$this->app->bind(FavoriteRepositoryInterface::class, EloquentFavoriteRepository::class);`

### Success Criteria:

#### Automated:

- FavoriteService::toggleFavorite adds and removes correctly
- FavoriteService::toggleFavorite throws for unpublished observation
- Container resolves FavoriteRepositoryInterface without error
- Pint passes

#### Manual:

- None for this phase

---

## Phase 3: Controller + Routes

### Overview

Create `FavoriteController` with JSON toggle endpoint and Blade favorites list, register routes.

### Required Changes:

#### 1. FavoriteController

**File**: `app/Http/Controllers/FavoriteController.php`

**Purpose**: Thin controller — toggle returns JSON, index returns Blade view.

**Contract**:
```php
class FavoriteController extends Controller
{
    public function toggle(Request $request, Observation $observation): JsonResponse;  // POST, auth
    public function index(Request $request): View;  // GET, auth — user's favorites page
}
```

`toggle()` calls `FavoriteService::toggleFavorite()`, returns `{ "favorited": true/false, "count": N }`.
`index()` calls `FavoriteService::getUserFavorites()`, returns Blade view.

#### 2. Routes

**File**: `routes/web.php`

**Purpose**: Add favorite routes inside the auth middleware group, before wildcard.

**Contract**:
```php
// Inside auth middleware group, BEFORE {observation} wildcard:
Route::get('/observations/favorites', [FavoriteController::class, 'index'])->name('observations.favorites');
Route::post('/observations/{observation}/favorite', [FavoriteController::class, 'toggle'])->name('observations.favorite.toggle');
```

### Success Criteria:

#### Automated:

- POST /observations/{id}/favorite returns 401 for guest
- POST /observations/{id}/favorite returns JSON with `favorited` and `count` for auth user
- GET /observations/favorites returns 200 for auth user, redirect for guest
- Route does not conflict with existing observation routes
- Pint passes

#### Manual:

- Confirm routes resolve correctly: `sail artisan route:list --name=favorite`

---

## Phase 4: Frontend — Star Component + AJAX

### Overview

Add star icon with count to observation cards and show view. Vanilla JS handles the AJAX toggle.

### Required Changes:

#### 1. Blade partial for star button

**File**: `resources/views/observations/partials/favorite-button.blade.php`

**Purpose**: Reusable partial rendering the star icon with count and data attributes for JS.

**Contract**: Accepts `$observation`, `$isFavorited` (bool), `$favoritesCount` (int). Renders: button with star (★ if favorited, ☆ if not), count in parentheses if ≥1, `data-observation-id`, `data-url` for JS. Guest: renders as non-interactive span with `title="Log in to favorite"`.

#### 2. Update ObservationController::index and show

**File**: `app/Http/Controllers/ObservationController.php`

**Purpose**: Pass favorites data to views — eager-load `favoritedBy` count, and for auth users check which are favorited.

**Contract**: 
- `index()`: load `withCount('favoritedBy')` on observations query; if auth, also load which are favorited by current user.
- `show()`: load `favoritedBy_count` and `isFavorited` flag for the observation.

#### 3. Update ObservationService/Repository for withCount

**File**: `app/Repositories/EloquentObservationRepository.php`

**Purpose**: Add `withCount('favoritedBy')` to `paginatePublished()` and `findPublishedById()` queries.

#### 4. Include partial in index and show views

**File**: `resources/views/observations/index.blade.php`, `resources/views/observations/show.blade.php`

**Purpose**: Render the favorite button partial on each card and in the detail header.

#### 5. Inline JavaScript for AJAX toggle

**File**: `resources/views/observations/partials/favorite-button.blade.php` (or a `@push('scripts')` block)

**Purpose**: Event listener on star buttons — POST to toggle URL, update star icon and count on success.

**Contract**: Vanilla JS, no dependencies. Uses `fetch()` with CSRF token from `<meta>` tag. On success, toggles `★`/`☆` and updates count text. On 401, redirects to login.

### Success Criteria:

#### Automated:

- Views compile without Blade errors
- Pint passes

#### Manual:

- Click star on observation card → star toggles, count updates without reload
- Click star on show page → same behavior
- Guest sees count but cannot click (cursor: default, title tooltip)
- After toggle, refreshing page shows correct persisted state

---

## Phase 5: Favorites List Page

### Overview

Blade view showing paginated grid of user's favorited observations.

### Required Changes:

#### 1. Favorites index view

**File**: `resources/views/observations/favorites.blade.php`

**Purpose**: Paginated card grid of favorited observations, reusing the same card pattern as the main feed.

**Contract**: Extends `layouts.marine`. Shows card grid with observation cards (same style as feed). Pagination at bottom. Empty state: "You haven't favorited any observations yet." Navigation link in header.

#### 2. Navigation link

**File**: `resources/views/layouts/marine.blade.php` (or navigation partial)

**Purpose**: Add "My Favorites" link for authenticated users (next to "My Observations").

### Success Criteria:

#### Automated:

- GET /observations/favorites renders without error for auth user
- Pint passes

#### Manual:

- Favorites page shows correct favorited observations
- Empty state displays when no favorites
- Navigation link is visible and active

---

## Phase 6: Tests

### Overview

Feature tests for the favorite toggle, favorites list, auth guards, and count visibility. Unit test for FavoriteService guard logic.

### Required Changes:

#### 1. Feature test: FavoriteToggleTest

**File**: `tests/Feature/FavoriteToggleTest.php`

**Purpose**: Test toggle endpoint behavior.

**Contract**: Methods:
- `test_guest_cannot_toggle_favorite`
- `test_user_can_favorite_published_observation`
- `test_user_can_unfavorite_observation`
- `test_toggle_returns_correct_count`
- `test_cannot_favorite_unpublished_observation`
- `test_favorite_is_idempotent` (double-toggle returns to original state)

#### 2. Feature test: FavoriteListTest

**File**: `tests/Feature/FavoriteListTest.php`

**Purpose**: Test favorites page.

**Contract**: Methods:
- `test_guest_cannot_access_favorites_page`
- `test_user_can_view_their_favorites`
- `test_favorites_page_shows_empty_state`
- `test_favorites_count_visible_to_guest_on_feed`

#### 3. Unit test: FavoriteServiceTest

**File**: `tests/Unit/FavoriteServiceTest.php`

**Purpose**: Isolated test for the unpublished-observation guard.

**Contract**: Methods:
- `test_toggle_throws_for_unpublished_observation`
- `test_toggle_returns_favorited_true_when_adding`

### Success Criteria:

#### Automated:

- All new tests pass: `sail artisan test --filter=Favorite`
- Full suite passes (no regression): `sail artisan test`
- Pint passes

#### Manual:

- None

---

## Testing Strategy

### Feature tests:

- FavoriteToggleTest: auth guard, toggle behavior, count correctness, unpublished guard
- FavoriteListTest: page access, content, empty state, guest visibility of count

### Unit tests:

- FavoriteServiceTest: business logic guards with mocked repository

### Run command:

```bash
./vendor/bin/sail artisan test --filter=Favorite
```

## References

- Architecture conventions: `AGENTS.md`
- Existing service pattern: `app/Services/ObservationService.php`
- Existing repository pattern: `app/Repositories/EloquentObservationRepository.php`
- Feed view structure: `resources/views/observations/index.blade.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles.

### Phase 1: Data Layer

#### Automated

- [x] 1.1 Create favorites migration with unique constraint — 6883f17
- [x] 1.2 Add favorites() relationship to User model — 6883f17
- [x] 1.3 Add favoritedBy() relationship to Observation model — 6883f17
- [x] 1.4 Migration applies and rollback works — 6883f17
- [x] 1.5 Pint passes — 6883f17

#### Manual

- [ ] 1.6 Confirm table exists with correct columns

### Phase 2: Repository + Service Layer

#### Automated

- [x] 2.1 Create FavoriteRepositoryInterface — d7ebf7a
- [x] 2.2 Create EloquentFavoriteRepository — d7ebf7a
- [x] 2.3 Create FavoriteService with toggle/query methods — d7ebf7a
- [x] 2.4 Register binding in AppServiceProvider — d7ebf7a
- [x] 2.5 Pint passes — d7ebf7a

#### Manual

- [ ] 2.6 Container resolves FavoriteRepositoryInterface

### Phase 3: Controller + Routes

#### Automated

- [x] 3.1 Create FavoriteController with toggle and index
- [x] 3.2 Add routes (before wildcard)
- [x] 3.3 Toggle endpoint returns correct JSON
- [x] 3.4 Pint passes

#### Manual

- [ ] 3.5 Routes resolve correctly via artisan route:list

### Phase 4: Frontend — Star Component + AJAX

#### Automated

- [ ] 4.1 Create favorite-button partial
- [ ] 4.2 Update ObservationController to pass favorites data
- [ ] 4.3 Add withCount to repository queries
- [ ] 4.4 Include partial in index and show views
- [ ] 4.5 Add inline JS for AJAX toggle
- [ ] 4.6 Pint passes

#### Manual

- [ ] 4.7 Star toggles on card without reload
- [ ] 4.8 Star toggles on show page without reload
- [ ] 4.9 Guest sees count but cannot interact
- [ ] 4.10 Refresh shows persisted state

### Phase 5: Favorites List Page

#### Automated

- [ ] 5.1 Create favorites Blade view
- [ ] 5.2 Add navigation link for auth users
- [ ] 5.3 Pint passes

#### Manual

- [ ] 5.4 Page shows correct favorited observations
- [ ] 5.5 Empty state displays correctly
- [ ] 5.6 Navigation link visible and working

### Phase 6: Tests

#### Automated

- [ ] 6.1 Create FavoriteToggleTest (6 tests)
- [ ] 6.2 Create FavoriteListTest (4 tests)
- [ ] 6.3 Create FavoriteServiceTest (2 unit tests)
- [ ] 6.4 All new tests pass
- [ ] 6.5 Full suite passes (no regression)
- [ ] 6.6 Pint passes

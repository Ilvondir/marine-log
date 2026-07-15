# Plan implementacji: Admin Moderation Area

## Przegląd

Implementacja S-04 z roadmapy: administrator może moderować (unpublish) lub usuwać dowolne obserwacje oraz przeglądać i blokować konta użytkowników. Wykorzystuje istniejącą infrastrukturę admin (Role::ADMIN, EnsureUserIsAdmin middleware, admin dashboard route) i ustalone wzorce architektoniczne (thin controller → service → repository).

## Analiza stanu obecnego

- Role system działa: `Role::ADMIN`, `User::isAdmin()`, middleware `EnsureUserIsAdmin` aliased jako `'admin'`.
- Admin route surface istnieje: `GET /admin` → `AdminDashboardController`.
- `ObservationService::deleteObservation()` obsługuje pełne usuwanie (pliki + DB) — reusable.
- `ObservationPolicy` sprawdza tylko ownership — admin nie ma override'a (celowo; admin ma osobne routes).
- Brak kolumny `blocked_at` na users.
- Brak middleware sprawdzającego blokadę użytkownika.
- Brak admin UI do zarządzania obserwacjami/użytkownikami.

### Kluczowe odkrycia:

- `app/Http/Middleware/EnsureUserIsAdmin.php:17` — gate admina, reusable dla nowych routes
- `app/Services/ObservationService.php:131-149` — `deleteObservation()` z full cleanup
- `app/Repositories/EloquentObservationRepository.php` — brak `paginateAll()`, potrzebne
- `database/migrations/0001_01_01_000000_create_users_table.php` — users schema bez `blocked_at`
- `routes/web.php:46-48` — obecny admin route do przeniesienia do grupy

## Pożądany stan końcowy

Administrator (zalogowany z rolą Admin) może:
1. Zobaczyć listę wszystkich obserwacji (published + unpublished) w panelu admina
2. Usunąć dowolną obserwację (z pełnym cleanup plików/media)
3. Unpublishować obserwację (ustawić `published_at = null`, usunąć z public feed)
4. Zobaczyć listę wszystkich użytkowników z ich statusem
5. Zablokować konto użytkownika (ustawia `blocked_at`, wymusza logout)
6. Odblokować konto użytkownika

Zablokowany użytkownik:
- Przy następnym requeście jest wylogowany i przekierowany na login z komunikatem
- Nie może się zalogować dopóki nie zostanie odblokowany

Weryfikacja: testy feature pokrywają guest → 302, user → 403, admin → 200 dla każdego endpointu + blokada wymusza logout.

## Czego NIE robimy

- Admin nie edytuje obserwacji (tylko delete/unpublish) — PRD mówi "moderate or delete"
- Blokada usera nie auto-unpublishuje jego obserwacji — admin może to zrobić osobno
- Brak soft-delete obserwacji (hard delete per PRD)
- Brak paginacji po stronie klienta (server-side pagination)
- Brak wyszukiwania/filtrowania w panelu admina (MVP)
- Admin nie może zablokować siebie (guard w service)
- Brak logów audytu (poza standardowym Laravel logging)

## Podejście do implementacji

Osobne admin controllers (`AdminObservationController`, `AdminUserController`) za middleware `['auth', 'admin']`, z dedykowanym `AdminService` dla logiki blokowania. Reuse `ObservationService::deleteObservation()` dla usuwania. Nowy `UserRepositoryInterface` + `EloquentUserRepository` dla operacji na użytkownikach. Middleware `EnsureUserIsNotBlocked` dodany do grupy `auth` — sprawdza `blocked_at` i wymusza logout.

## Krytyczne szczegóły implementacji

- **Sekwencjonowanie middleware**: `EnsureUserIsNotBlocked` musi działać PO `auth` middleware (user musi być załadowany), ale PRZED jakimikolwiek innymi middleware (żeby zablokowany user nie mógł nic zrobić). Middleware admin (`EnsureUserIsAdmin`) jest niezależny — admin nie może być zablokowany (guard w service), ale gdyby ktoś ręcznie ustawił `blocked_at` adminowi, middleware blokady zadziała wcześniej.

---

## Faza 1: Migration + User blocking infrastructure

### Przegląd

Dodanie kolumny `blocked_at` do users, helper methods na modelu, i middleware wymuszającego logout zablokowanego użytkownika.

### Wymagane zmiany:

#### 1. Migration: add blocked_at to users

**Plik**: `database/migrations/2026_07_15_000001_add_blocked_at_to_users_table.php`

**Cel**: Dodaje nullable timestamp `blocked_at` do tabeli users.

**Kontrakt**: `$table->timestamp('blocked_at')->nullable()->after('remember_token')` w `up()`, `dropColumn` w `down()`.

#### 2. User model: isBlocked helper

**Plik**: `app/Models/User.php`

**Cel**: Dodaje method `isBlocked(): bool` i cast `blocked_at` jako datetime. Dodaje `blocked_at` do fillable.

**Kontrakt**: `public function isBlocked(): bool` — returns `$this->blocked_at !== null`.

#### 3. Middleware: EnsureUserIsNotBlocked

**Plik**: `app/Http/Middleware/EnsureUserIsNotBlocked.php`

**Cel**: Sprawdza czy zalogowany user jest zablokowany. Jeśli tak — wylogowuje, invaliduje sesję, przekierowuje na login z flash message.

**Kontrakt**: 
```php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user()?->isBlocked()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('error', 'Your account has been blocked.');
    }
    return $next($request);
}
```

#### 4. Register middleware in bootstrap/app.php

**Plik**: `bootstrap/app.php`

**Cel**: Dodaje `EnsureUserIsNotBlocked` do middleware stack — po `auth`, przed route-level middleware.

**Kontrakt**: Middleware aliased lub dodany do grupy `web` po `auth` middleware, albo appendowany do `auth` middleware group.

### Kryteria sukcesu:

#### Weryfikacja automatyczna:

- Migration applies cleanly: `sail artisan migrate`
- Migration rollback works: `sail artisan migrate:rollback`
- `User::isBlocked()` returns correct values
- Middleware forces logout for blocked user
- Pint passes: `sail pint --test`

#### Weryfikacja ręczna:

- Zablokowany user (ręcznie ustawiony `blocked_at` w DB) jest wylogowany przy next request

---

## Faza 2: Repository + Service layer

### Przegląd

Tworzenie `UserRepositoryInterface` + implementacji, rozszerzenie `ObservationRepositoryInterface` o `paginateAll()`, i `AdminService` z logiką block/unblock.

### Wymagane zmiany:

#### 1. UserRepositoryInterface

**Plik**: `app/Contracts/Repositories/UserRepositoryInterface.php`

**Cel**: Kontrakt dla operacji na użytkownikach w panelu admina.

**Kontrakt**:
```php
interface UserRepositoryInterface
{
    public function paginateAll(int $perPage = 20): LengthAwarePaginator;
    public function findById(int $id): User;
    public function update(int $id, array $data): User;
}
```

#### 2. EloquentUserRepository

**Plik**: `app/Repositories/EloquentUserRepository.php`

**Cel**: Implementacja z logowaniem błędów (wzorzec z EloquentObservationRepository).

**Kontrakt**: Implementuje `UserRepositoryInterface`. Eager-loads `role` w `paginateAll`. Loguje błędy z context keys: repository, method, operation, entity_id, exception, message.

#### 3. Rozszerzenie ObservationRepositoryInterface

**Plik**: `app/Contracts/Repositories/ObservationRepositoryInterface.php`

**Cel**: Dodanie metody `paginateAll()` do listy WSZYSTKICH obserwacji (nie tylko published).

**Kontrakt**: `public function paginateAll(int $perPage = 20): LengthAwarePaginator;`

#### 4. Implementacja paginateAll w EloquentObservationRepository

**Plik**: `app/Repositories/EloquentObservationRepository.php`

**Cel**: Query all observations (published + unpublished) z eager-load user + photos.

**Kontrakt**: `Observation::query()->with(['user', 'photos'])->latest('created_at')->paginate($perPage)`

#### 5. AdminService

**Plik**: `app/Services/AdminService.php`

**Cel**: Logika biznesowa admin operations — block/unblock users, unpublish observations.

**Kontrakt**:
```php
class AdminService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ObservationRepositoryInterface $observationRepository,
    ) {}

    public function blockUser(User $admin, User $target): User  // throws if target is admin or self
    public function unblockUser(User $target): User
    public function unpublishObservation(int $observationId): Observation  // sets published_at = null
}
```

Guard: `blockUser` throws `\InvalidArgumentException` if `$target->isAdmin()` or `$admin->id === $target->id`.

#### 6. Register bindings in AppServiceProvider

**Plik**: `app/Providers/AppServiceProvider.php`

**Cel**: Bind `UserRepositoryInterface` → `EloquentUserRepository`.

**Kontrakt**: `$this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);`

### Kryteria sukcesu:

#### Weryfikacja automatyczna:

- `AdminService::blockUser()` sets `blocked_at` timestamp
- `AdminService::blockUser()` throws when target is admin
- `AdminService::blockUser()` throws when target is self
- `AdminService::unblockUser()` clears `blocked_at`
- `AdminService::unpublishObservation()` sets `published_at = null`
- Pint passes: `sail pint --test`

#### Weryfikacja ręczna:

- Container resolves `UserRepositoryInterface` without error

---

## Faza 3: Admin Observation Controller + Routes

### Przegląd

Controller z akcjami: index (lista wszystkich), destroy (delete), unpublish. Routes pod `['auth', 'admin']` prefix.

### Wymagane zmiany:

#### 1. AdminObservationController

**Plik**: `app/Http/Controllers/AdminObservationController.php`

**Cel**: Thin controller — deleguje do ObservationService (delete) i AdminService (unpublish).

**Kontrakt**:
```php
class AdminObservationController extends Controller
{
    public function index(): View  // all observations paginated
    public function destroy(Observation $observation): RedirectResponse  // delete via ObservationService
    public function unpublish(Observation $observation): RedirectResponse  // unpublish via AdminService
}
```

#### 2. Routes update

**Plik**: `routes/web.php`

**Cel**: Przeniesienie istniejącej admin route do grupy i dodanie observation moderation routes.

**Kontrakt**:
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/observations', [AdminObservationController::class, 'index'])->name('observations.index');
    Route::delete('/observations/{observation}', [AdminObservationController::class, 'destroy'])->name('observations.destroy');
    Route::patch('/observations/{observation}/unpublish', [AdminObservationController::class, 'unpublish'])->name('observations.unpublish');
});
```

### Kryteria sukcesu:

#### Weryfikacja automatyczna:

- Guest accessing admin observation routes → redirect to login
- Regular user accessing admin observation routes → 403
- Admin can list all observations (published + unpublished)
- Admin can delete any observation
- Admin can unpublish a published observation
- Existing admin.dashboard route still works
- Pint passes: `sail pint --test`

#### Weryfikacja ręczna:

- Admin navigates to `/admin/observations` and sees list

---

## Faza 4: Admin User Controller + Routes

### Przegląd

Controller z akcjami: index (lista userów), block, unblock. Routes w tej samej admin grupie.

### Wymagane zmiany:

#### 1. AdminUserController

**Plik**: `app/Http/Controllers/AdminUserController.php`

**Cel**: Thin controller dla user management — deleguje do AdminService.

**Kontrakt**:
```php
class AdminUserController extends Controller
{
    public function index(): View  // all users paginated with role + blocked status
    public function block(User $user): RedirectResponse  // block via AdminService
    public function unblock(User $user): RedirectResponse  // unblock via AdminService
}
```

`block()` catches `\InvalidArgumentException` from service (self-block/admin-block) i zwraca redirect z error flash.

#### 2. Routes update (add to admin group)

**Plik**: `routes/web.php`

**Cel**: Dodanie user management routes do istniejącej admin grupy.

**Kontrakt**:
```php
Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
```

### Kryteria sukcesu:

#### Weryfikacja automatyczna:

- Guest accessing admin user routes → redirect to login
- Regular user accessing admin user routes → 403
- Admin can list all users
- Admin can block a regular user
- Admin cannot block another admin → error flash
- Admin cannot block themselves → error flash
- Admin can unblock a blocked user
- Blocked user is forced to logout on next request
- Pint passes: `sail pint --test`

#### Weryfikacja ręczna:

- Admin navigates to `/admin/users` and sees user list with block/unblock buttons

---

## Faza 5: Blade Views

### Przegląd

Admin panel views — dashboard z navigation, lista obserwacji, lista użytkowników. Follows existing marine layout.

### Wymagane zmiany:

#### 1. Admin layout partial (navigation)

**Plik**: `resources/views/admin/partials/navigation.blade.php`

**Cel**: Shared navigation bar dla admin panelu (links: Dashboard, Observations, Users).

**Kontrakt**: Partial z linkami do `admin.dashboard`, `admin.observations.index`, `admin.users.index`. Active state based on current route.

#### 2. Update admin dashboard view

**Plik**: `resources/views/admin/dashboard.blade.php`

**Cel**: Dodanie navigation partial i basic stats (total observations, total users, blocked users count).

**Kontrakt**: Extends `layouts.marine`, includes admin navigation, shows summary cards.

#### 3. Admin observations index view

**Plik**: `resources/views/admin/observations/index.blade.php`

**Cel**: Tabela wszystkich obserwacji z kolumnami: species, author, status (published/draft), date, actions (unpublish/delete).

**Kontrakt**: Extends `layouts.marine`, includes admin navigation. Delete form z `@method('DELETE')` + JS confirm. Unpublish form z `@method('PATCH')`. Pagination links.

#### 4. Admin users index view

**Plik**: `resources/views/admin/users/index.blade.php`

**Cel**: Tabela użytkowników z kolumnami: name, email, role, status (active/blocked), registered date, actions (block/unblock).

**Kontrakt**: Extends `layouts.marine`, includes admin navigation. Block/unblock forms z `@method('PATCH')`. Self-block disabled (admin's own row has no block button). Pagination links.

### Kryteria sukcesu:

#### Weryfikacja automatyczna:

- Views compile without Blade errors
- Admin dashboard loads with navigation
- Pint passes: `sail pint --test`

#### Weryfikacja ręczna:

- Admin panel navigation works across all 3 pages
- Observations table shows published/unpublished status correctly
- Delete confirmation dialog appears before deletion
- Users table shows blocked/active status
- Block/unblock buttons toggle correctly
- Admin's own row has no block button

---

## Strategia testowania

### Testy feature (HTTP flow):

- `AdminObservationTest`: guest/user/admin triplet for index, destroy, unpublish
- `AdminUserTest`: guest/user/admin triplet for index, block, unblock + self-block prevention + admin-block prevention
- `BlockedUserTest`: blocked user forced logout, blocked user cannot login

### Testy unit (service logic):

- `AdminServiceTest`: blockUser, unblockUser, unpublishObservation + guard exceptions

### Istniejące testy:

- Existing `AuthScaffoldTest::test_seeded_admin_exists_and_can_access_admin_only_area` — must still pass (route name unchanged)
- Existing observation tests — must still pass (no regression)

## Referencje

- Powiązane badania: `context/changes/admin-moderation-area/research.md`
- Wzorzec ownership policy: `context/changes/own-observation-management/plan.md`
- Auth scaffold patterns: `context/changes/auth-scaffold/plan.md`
- PRD requirements: FR-005, FR-006

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles.

### Phase 1: Migration + User blocking infrastructure

#### Automated

- [x] 1.1 Create migration adding blocked_at to users — 2aaacbd
- [x] 1.2 Add isBlocked method and blocked_at cast to User model — 2aaacbd
- [x] 1.3 Create EnsureUserIsNotBlocked middleware — 2aaacbd
- [x] 1.4 Register middleware in bootstrap/app.php — 2aaacbd
- [x] 1.5 Migration applies and rollback works — 2aaacbd
- [x] 1.6 Pint passes — 2aaacbd

#### Manual

- [x] 1.7 Blocked user is forced to logout on next request — 2aaacbd

### Phase 2: Repository + Service layer

#### Automated

- [ ] 2.1 Create UserRepositoryInterface
- [ ] 2.2 Create EloquentUserRepository
- [ ] 2.3 Add paginateAll to ObservationRepositoryInterface and implementation
- [ ] 2.4 Create AdminService with block/unblock/unpublish
- [ ] 2.5 Register UserRepository binding in AppServiceProvider
- [ ] 2.6 Pint passes

#### Manual

- [ ] 2.7 Container resolves UserRepositoryInterface without error

### Phase 3: Admin Observation Controller + Routes

#### Automated

- [ ] 3.1 Create AdminObservationController with index/destroy/unpublish
- [ ] 3.2 Update routes with admin observation routes
- [ ] 3.3 Feature tests pass for admin observation endpoints
- [ ] 3.4 Existing admin.dashboard route still works
- [ ] 3.5 Pint passes

#### Manual

- [ ] 3.6 Admin can navigate to /admin/observations and see list

### Phase 4: Admin User Controller + Routes

#### Automated

- [ ] 4.1 Create AdminUserController with index/block/unblock
- [ ] 4.2 Add user management routes to admin group
- [ ] 4.3 Feature tests pass for admin user endpoints
- [ ] 4.4 Blocked user forced logout test passes
- [ ] 4.5 Pint passes

#### Manual

- [ ] 4.6 Admin can navigate to /admin/users and manage accounts

### Phase 5: Blade Views

#### Automated

- [ ] 5.1 Create admin navigation partial
- [ ] 5.2 Update admin dashboard with navigation and stats
- [ ] 5.3 Create admin observations index view
- [ ] 5.4 Create admin users index view
- [ ] 5.5 Views compile without Blade errors
- [ ] 5.6 Full test suite passes (no regression)
- [ ] 5.7 Pint passes

#### Manual

- [ ] 5.8 Admin panel navigation works across all pages
- [ ] 5.9 Delete confirmation dialog works
- [ ] 5.10 Block/unblock toggles correctly
- [ ] 5.11 Admin's own row has no block button

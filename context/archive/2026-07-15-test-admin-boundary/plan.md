# Plan implementacji: Admin Moderation Boundary Tests

## Przegląd

Phase 5 z test-plan.md: prove admin endpoints reject unauthorized access and service guards prevent privilege abuse (Risk #7). Testy feature dla matrycy dostępu (guest/user/admin) na każdym admin endpoint + unit tests dla AdminService guards + blocked user middleware behavior.

## Analiza stanu obecnego

- Admin moderation (S-04) jest zaimplementowana: 7 admin routes za middleware `['auth', 'admin']`
- `EnsureUserIsNotBlocked` middleware wymusza logout zablokowanego usera
- `AdminService::blockUser()` ma guardy (self-block, admin-block)
- Istniejące testy: `AuthScaffoldTest` ma 2 testy admin boundary (dashboard access), ale BRAK testów dla moderation endpoints
- Brak testów na blokowanie użytkowników, unpublish, admin delete

### Kluczowe odkrycia:

- `routes/web.php:44-52` — Wszystkie 7 admin routes w jednej grupie z middleware
- `app/Services/AdminService.php:25-31` — Guards throw InvalidArgumentException
- `app/Http/Controllers/AdminUserController.php:35-44` — Controller łapie exception i zwraca error flash
- `app/Http/Middleware/EnsureUserIsNotBlocked.php:19-25` — Forces logout + invalidates session

## Pożądany stan końcowy

Pełna matryca testów pokrywająca:
- Każdy admin endpoint (7 routes) × 3 aktorów (guest, user, admin) = 21 asercji dostępu
- AdminService guards (self-block, admin-block) = 2 unit testy
- Blocked user middleware behavior (forced logout, flash message) = 2 feature testy
- Admin moderation actions work correctly (delete observation, unpublish) = 2 feature testy

## Czego NIE robimy

- Nie testujemy UI (Blade views) — to jest sprawdzanie HTTP boundary
- Nie testujemy login flow blocked usera (middleware sprawdza na każdym requeście post-login)
- Nie testujemy admin edycji obserwacji (nie istnieje w S-04)
- Nie konfigurujemy hooków ani CI (inne lekcje)

## Fazy

### Faza 1: Setup infrastruktury testowej

**Cel**: Helper trait/methods do tworzenia admin usera w testach.

**Wymagane zmiany:**

#### 1. AdminTestHelpers

**Plik**: `tests/Feature/AdminModerationTest.php` (nowy plik)

**Cel**: Feature test class z helper methods do tworzenia admin/user/observation fixtures.

**Kontrakt**: Helper methods: `createAdmin(): User`, `createRegularUser(): User`, `createBlockedUser(): User`, `createObservation(User $user): Observation`. Wszystkie używają factories + Role::create/find.

### Faza 2: Feature tests — access matrix

**Cel**: Prove every admin endpoint rejects guest (redirect) and regular user (403), accepts admin (200/302).

**Wymagane zmiany:**

#### 1. Access matrix tests

**Plik**: `tests/Feature/AdminModerationTest.php`

**Cel**: Testy guest/user/admin triplet dla: admin.dashboard, admin.observations.index, admin.observations.destroy, admin.observations.unpublish, admin.users.index, admin.users.block, admin.users.unblock.

**Kontrakt**: Methods:
- `test_guest_cannot_access_admin_observations(): void`
- `test_regular_user_cannot_access_admin_observations(): void`
- `test_admin_can_access_admin_observations(): void`
- `test_guest_cannot_access_admin_users(): void`
- `test_regular_user_cannot_access_admin_users(): void`
- `test_admin_can_access_admin_users(): void`
- `test_admin_can_delete_any_observation(): void`
- `test_admin_can_unpublish_observation(): void`
- `test_regular_user_cannot_delete_observation_via_admin(): void`
- `test_regular_user_cannot_unpublish_observation(): void`
- `test_regular_user_cannot_block_user(): void`

### Faza 3: Feature tests — service guards and blocked user

**Cel**: Prove AdminService guards work through controller + blocked user is forced out.

**Wymagane zmiany:**

#### 1. Guard and blocking tests

**Plik**: `tests/Feature/AdminModerationTest.php`

**Cel**: Test self-block prevention, admin-block prevention, and blocked user middleware.

**Kontrakt**: Methods:
- `test_admin_cannot_block_themselves(): void`
- `test_admin_cannot_block_another_admin(): void`
- `test_admin_can_block_regular_user(): void`
- `test_admin_can_unblock_user(): void`
- `test_blocked_user_is_forced_to_logout(): void`
- `test_blocked_user_sees_error_message(): void`

### Faza 4: Unit tests — AdminService isolation

**Cel**: Test AdminService guard logic in isolation with mocked repos.

**Wymagane zmiany:**

#### 1. AdminService unit tests

**Plik**: `tests/Unit/AdminServiceTest.php` (nowy plik)

**Cel**: Isolated unit tests for block/unblock/unpublish logic.

**Kontrakt**: Methods:
- `test_block_user_sets_blocked_at(): void`
- `test_block_user_throws_when_target_is_admin(): void`
- `test_block_user_throws_when_target_is_self(): void`
- `test_unblock_user_clears_blocked_at(): void`
- `test_unpublish_observation_clears_published_at(): void`

### Faza 5: Cookbook update (§6 test-plan.md)

**Cel**: Dodanie sekcji 6.5 do cookbook z wzorcem dodawania testów admin boundary.

**Wymagane zmiany:**

#### 1. Update test-plan.md §6

**Plik**: `context/foundation/test-plan.md`

**Cel**: Dodanie entry do Phase 5 notes w §6.5 i nowej sekcji 6.6 "Adding an admin boundary test".

## Kryteria sukcesu:

- All new tests pass: `sail artisan test --filter=AdminModerationTest`
- All new unit tests pass: `sail artisan test --filter=AdminServiceTest`
- Full suite still passes (no regression): `sail artisan test`
- Pint passes: `sail pint --test`

## Referencje

- Research: `context/changes/test-admin-boundary/research.md`
- Test plan: `context/foundation/test-plan.md` (Risk #7, Phase 5)
- Implemented feature: `context/changes/admin-moderation-area/plan.md`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles.

### Phase 1: Setup infrastruktury testowej

#### Automated

- [ ] 1.1 Create AdminModerationTest with helper methods
- [ ] 1.2 Pint passes

### Phase 2: Feature tests — access matrix

#### Automated

- [ ] 2.1 Add access matrix tests (guest/user/admin × endpoints)
- [ ] 2.2 All access matrix tests pass
- [ ] 2.3 Pint passes

### Phase 3: Feature tests — service guards and blocked user

#### Automated

- [ ] 3.1 Add guard and blocking tests
- [ ] 3.2 All guard tests pass
- [ ] 3.3 Pint passes

### Phase 4: Unit tests — AdminService isolation

#### Automated

- [ ] 4.1 Create AdminServiceTest with isolated tests
- [ ] 4.2 All unit tests pass
- [ ] 4.3 Pint passes

### Phase 5: Cookbook update

#### Automated

- [ ] 5.1 Update test-plan.md with phase 5 notes and cookbook entry
- [ ] 5.2 Full test suite passes (no regression)
- [ ] 5.3 Pint passes

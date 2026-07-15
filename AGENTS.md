# Repository Guidelines

MarineLog is a Laravel 13 application for wildlife observations. Product scope is in `@context/foundation/prd.md`; stack decisions are in `@context/foundation/tech-stack.md`.

## Critical Rules

- Keep work inside the PRD's MVP; its `Non-Goals` are binding.
- Run PHP, Artisan, Composer, and tests through Laravel Sail once Docker is available. Do not rely on host PHP extensions or versions.
- Never commit `.env`, credentials, uploaded media, `vendor/`, or `node_modules/`. Add reusable defaults without secrets to `@.env.example`.
- Never edit `vendor/`; change application code or Composer constraints.
- Preserve `context/`; it contains the product decisions and bootstrap audit trail.
- Keep controllers thin: validate the request, call one service method, and return the response. Do not place persistence or business workflows in controllers.

## Common Commands

- `./vendor/bin/sail up -d` starts Laravel, MySQL, and Redis.
- `./vendor/bin/sail artisan migrate` applies migrations.
- `./vendor/bin/sail npm run dev` starts Vite.
- `./vendor/bin/sail composer test` runs tests.
- `./vendor/bin/sail pint --test` checks formatting; `./vendor/bin/sail pint` fixes it.

## Project Structure

Keep schema changes in `database/migrations` and Sail services in `@compose.yaml`. Avoid new top-level directories unless required by the architecture below.

## Architecture

- Put business workflows in `app/Services`; expose intention-revealing methods such as `publishObservation(...)`, not generic CRUD wrappers.
- Define repository interfaces in `app/Contracts/Repositories` and implementations in `app/Repositories`. Repositories own Eloquent queries and persistence; services depend only on their contracts.
- Bind each repository contract to its implementation in `@app/Providers/AppServiceProvider.php`.
- Inject services and repository contracts through constructors. Do not resolve application dependencies with `app()` or instantiate repositories inside controllers and services.
- Keep services independent of HTTP objects. Pass validated values or typed data objects.
- Repository exceptions must be logged before rethrowing with context keys `repository`, `method`, `operation`, `entity_id`, `exception`, and `message`. Never log secrets or payloads, swallow failures, or return `null` as an error signal.
- Add unit tests for service behavior using mocked repository contracts, and feature tests for complete HTTP and database flows.

## Code Style

Follow `@.editorconfig`, use typed signatures and return types, and match the model metadata pattern in `@app/Models/User.php`. Format PHP with Pint.

## Testing

Use the suites and source scope defined in `@phpunit.xml`. Name methods `test_<behavior>(): void`. Add or update tests for every behavioral change; run a focused test with `./vendor/bin/sail artisan test --filter=<name>`.

<!-- BEGIN @przeprogramowani/10x-cli -->

## 10xDevs AI Toolkit — Moduł 3, Lekcja 2

Lekcja 2 dotyczy **pisania testów, które faktycznie chronią kod** — a nie tylko maksymalizują pokrycie. Problem wyroczni i antywzorce testowania na wyczucie wyjaśniają, dlaczego testy generowane przez LLM zawodzą na prawdziwym kodzie; kontrakt jakości oparty na ryzyku z Lekcji 1 jest rozwiązaniem.

```
context/foundation/test-plan.md (§3 Wdrażanie fazowe)
        │
        ▼  (jedna faza wdrażania na raz)
   /10x-research  ──►  research.md  (źródło wyroczni: co kod powinien robić, a nie co robi)
        │
        ▼
   /10x-plan  ──►  plan.md  (koszt × sygnał, dwuwarstwowa strategia, uporządkowane fazy)
        │
        ▼
   /10x-implement  or  /10x-tdd   ──►  działające testy + aktualizacja podręcznika §6
```

`/10x-tdd` to **opcjonalny tryb test-first**, a nie zamiennik dla łańcucha. Odczytuje ten sam `plan.md`, zapisuje do tej samej sekcji `## Progress` i obejmuje te same fazy co `/10x-implement`. Używaj go tylko wtedy, gdy potrafisz nazwać pierwsze nieudane twierdzenie przed napisaniem jakiegokolwiek kodu.

### Router zadań — Od czego zacząć

| Umiejętność / Prompt | Kiedy używać |
| --- | --- |
| `/10x-research` | Przed napisaniem jakiegokolwiek testu dla ryzyka. Badania tworzą wyrocznię — jakie zachowanie test musi udowodnić — ze źródeł (PRD, tech-stack, dokumentacja), a nie z kształtu implementacji. Ujawnia również, czy ryzyko jest już pokryte, czy ma dwie oddzielne strony (jedna bezpieczna, jedna prawdziwa). |
| `/10x-plan` | Badania zakończone. Plan rozkłada ryzyko na uporządkowane fazy: najpierw konfiguracja środowiska, potem reguły od niej zależne, następnie hermetyczne stuby dla błędów, których prawdziwa infrastruktura nie może wywołać, a na końcu aktualizacja podręcznika. Każda faza nazywa zachowanie, które potwierdza, i regresję, którą wyłapuje. |
| `/10x-implement` | Domyślny wykonawca faz planu. Używaj do konfiguracji środowiska, istniejącego kodu, tworzenia szkieletu i każdej fazy, w której nie możesz zdefiniować czerwonego testu przed napisaniem kodu. |
| `/10x-tdd` | Opcjonalne. Używaj zamiast `/10x-implement` dla fazy, w której możesz nazwać pierwszy czerwony test w jednym zdaniu. Agent najpierw pisze nieudany test, potem minimalny kod, aby go zazielenić, a następnie refaktoryzuje. Zatrzymuje się na twierdzeniu przed dotknięciem implementacji — ta pauza jest kluczowa. |
| `m3l2-ad-hoc-testing` prompt | Masz jeden plik i chcesz testów teraz, bez pełnego cyklu research→plan→implement. Prompt wymusza wyrocznię ze źródeł (czyta PRD + TECH_STACK przed twierdzeniem), asercje behawioralne, przypadki brzegowe z ryzyka i tabelę regresji. Używaj go, wiedząc, że wymieniasz głębię na szybkość. |

### Kiedy używać `/10x-tdd` vs `/10x-implement`

Decydujące pytanie: *Czy potrafisz nazwać pierwszy czerwony test w jednym zdaniu?*

Dobre warunki dla `/10x-tdd`:
- "promuje wyłącznie drafty w stanie `accepted`, a `pending`/`rejected` nigdy nie trafiają do talii"
- "zwraca `ok: true` i loguje `orphan_review_state`, gdy upsert stanu powtórek padnie w trakcie zapisu"
- "zwraca 401, gdy użytkownik nie ma dostępu do kursu"
- "resetuje interwał powtórki do jednego dnia, gdy ocena wynosi 0"

Każde z nich nazywa obserwowalny wynik, a nie wewnętrzny szczegół. Jeśli nie potrafisz stworzyć takiego zdania, pozostań przy `/10x-implement` lub wróć do `/10x-research`.

`/10x-tdd` **nie nadaje się** do: konfiguracji środowiska, konfiguracji CI/CD, dokumentacji, cienkiego okablowania, gdzie test po prostu przepisałby implementację, lub do eksploracji, gdzie nadal odkrywasz kontrakt.

Możesz mieszać oba tryby w jednym planie:

```
/10x-implement <change-id> phase 1   # environment
/10x-tdd       <change-id> phase 2   # contract (new code)
/10x-tdd       <change-id> phase 3   # contract (API endpoint)
/10x-implement <change-id> phase 4   # cookbook + plan sync
```

Oba zapisują postęp do tej samej sekcji `## Progress` w `plan.md`.

### Dwuwarstwowa strategia testowania (koszt × sygnał)

Dla każdego ryzyka wybierz **najtańszy test, który daje prawdziwy sygnał**. Nie domyślnie do e2e "ponieważ jest najbezpieczniejszy" i nie gonić za procentem pokrycia.

| Warstwa | Kiedy używać | Kiedy NIE używać |
| --- | --- | --- |
| Integracja (prawdziwa baza danych / prawdziwa infrastruktura) | Reguła obejmuje ograniczenia DB, kaskady, prawdziwy SQL lub unikalne ograniczenia, o których mock by skłamał. | Przepływy uwierzytelniania zabezpieczone przez RLS, które należą do oddzielnej fazy; wszystko, gdzie koszt konfiguracji przekracza wartość sygnału. |
| Hermetyczny (stub klienta) | Częściowe awarie, których prawdziwa infrastruktura nie może łatwo wywołać (np. druga operacja w sekwencji zawodzi). | Reguły, które zależą od rzeczywistego stanu DB — stub skłamie na temat naruszeń ograniczeń i kaskad. |

Nieatomowa sekwencja zapisu (wiele niezależnych operacji bez transakcji) oznacza: pisz testy hermetyczne dla gałęzi częściowych awarii, a nie testy integracyjne, które wymuszają błąd w środku sekwencji.

### Reguły wyroczni

- Wyrocznia — co kod *powinien* robić — musi pochodzić ze źródeł: PRD, dokumentacji, ograniczeń tech-stack, wiedzy dziedzinowej. **Nie** może pochodzić z czytania implementacji.
- Jeśli implementacja ma błąd, skopiowanie jej wyniku jako oczekiwanej wartości tworzy test lustrzany, który przechodzi z błędem.
- Gdy źródła nie rozwiązują jednoznacznie oczekiwanego zachowania, **zatrzymaj się i zapytaj**, zamiast zgadywać.
- Zadaniem badań jest ujawnienie wyroczni przed napisaniem jakiegokolwiek testu.

### Antywzorce testowania na wyczucie, których należy unikać

| Antywzorzec | Jak wygląda | Co robić zamiast |
| --- | --- | --- |
| Implementacja lustrzana | Asercja oblicza oczekiwaną wartość tą samą logiką co testowany kod. | Asercja względem wartości pochodzącej z wyroczni (PRD / reguła dziedzinowa), a nie z implementacji. |
| Tylko szczęśliwe ścieżki | Testy tylko dla prawidłowych danych wejściowych; brak przypadków brzegowych. | Dodaj co najmniej jeden przypadek brzegowy na ryzyko: `null`, pusty, błąd zależności, nieprawidłowe dane wejściowe. |
| Redundantne kopie | Sześć niemal identycznych testów sprawdzających tę samą nieobecność strażnika. | Jeden sparametryzowany test (`it.each`) na właściwość; każdy test wyłapuje inną regresję. |

### Testowanie mutacyjne (Stryker) — selektywna brama jakości

Pokrycie mówi: "ta linia została wykonana". Wynik mutacji mówi: "czy test by zawiódł, gdybym zepsuł tę linię?". Używaj Strykera jako **selektywnej bramy** po fazie ryzyka, a nie jako bramy CI przy każdym commicie.

Przebieg pracy:
1. Testy przechodzą dla fazy ryzyka.
2. Uruchom `npx stryker run --mutate "path/to/file.ts"` (zawęź zakres do zmienionego modułu).
3. Otwórz raport HTML; znajdź ocalałe mutanty.
4. Dla każdego ocalałego mutanta zadaj pytanie: "Czy ta zmiana zaszkodziłaby użytkownikowi lub biznesowi?"
   - Tak → dodaj asercję, która zabija mutanta.
   - Nie (mutant równoważny lub zmiana kosmetyczna) → świadomie zignoruj.
5. Nie dąż do 100% wyniku mutacji. Test, który przypina szczegóły implementacji, aby zabić kosmetycznego mutanta, sam w sobie jest testem na wyczucie.

Brama integracyjna może pozostać **ad hoc** (nie przy każdym commicie), gdy uruchamianie lokalnej infrastruktury jest kosztowne. Odpowiednio oznacz to w `test-plan.md §4`.

### Granice lekcji

- Nie konfiguruj hooków, cyklu życia hooków ani hooków debugowania. To jest Lekcja 3.
- Nie konfiguruj serwerów MCP, API Playwright, kodu e2e ani kodu scenariuszy multimodalnych. To jest Lekcja 4.
- Nie uruchamiaj przepływu pracy od błędu do poprawki do testu regresji. To jest Lekcja 5.
- Nie twórz potoków CI/CD od podstaw. To jest Moduł 1 Lekcja 5 / Moduł 2 Lekcja 5.
- Nie uruchamiaj `/10x-test-plan`, aby zmienić strategię ryzyka. To jest Lekcja 1. Użyj `/10x-test-plan --status`, aby odczytać bieżący stan.
- Nie pisz testów bez etapu badań, chyba że używasz promptu ad-hoc z pełną świadomością jego kompromisów.

### Ścieżki używane w tej lekcji

- `context/foundation/test-plan.md` — stan wdrożenia §3; podręcznik §6 (uzupełniany w miarę realizacji faz)
- `context/changes/<change-id>/research.md` — źródło wyroczni dla każdej fazy wdrożenia
- `context/changes/<change-id>/plan.md` — uporządkowane fazy ze stanem wykonania `## Progress`
- `.claude/prompts/m3l2-ad-hoc-testing.md` — prompt do testowania ad-hoc na poziomie pliku

<!-- END @przeprogramowani/10x-cli -->

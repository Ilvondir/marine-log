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

---
name: 10xDevs AI Toolkit - Module 3, Lesson 4 (E2E Tests)
description: End-to-end testing with AI tools
license: CC BY-NC-ND 4.0
metadata:
  tags: AI, E2E, testing, Playwright
  version: 1.0.0
  module: 3
  lesson: 4
---

## 10xDevs AI Toolkit - Moduł 3, Lekcja 4 (Testy E2E)

**Do testów E2E użyj umiejętności `/10x-e2e`.** Jest to jedyne źródło prawdy
dla przepływu pracy — ryzyko → test początkowy + reguły → generowanie → przegląd pod kątem pięciu
antywzorców → ponowne zapytanie → weryfikacja. `references/` tej umiejętności
zawierają pełne reguły, antywzorce, wzorzec początkowy i szablon promptu.

Kilka twardych reguł, które obowiązują jeszcze przed wywołaniem umiejętności:

- **Lokatory:** Najpierw `getByRole` / `getByLabel` / `getByText`; `getByTestId`
  tylko wtedy, gdy atrybuty dostępności są niejednoznaczne. Nigdy selektory CSS, XPath
  ani struktura DOM.
- **Nigdy `page.waitForTimeout()`.** Czekaj na stan: `toBeVisible()`,
  `waitForURL()`, `waitForResponse()`.
- **Niezależność testów + czyszczenie.** Każdy test działa samodzielnie — własna konfiguracja,
  akcja, asercja i czyszczenie; unikalne identyfikatory (sufiks znacznika czasu), aby równoległe uruchomienia
  i ponowne uruchomienia nie kolidowały.

Dwie granice, które należy rozróżnić:

- **DOM (migawka) jest domyślny.** Wizja (`--caps=vision`) jest uzupełnieniem dla
  ryzyk wizualnych (układ, z-index, animacja); dla regresji pikseli preferuj
  narzędzia deterministyczne (`toMatchSnapshot`, Argos, Lost Pixel). Wybór/koszt modelu VLM
  to temat debugowania (Lekcja 5), a nie testowania.
- **Healer pomaga w selektorach, szkodzi w logice.** Zmieniony selektor → healer
  odnajduje go ponownie (trasa przez przegląd PR). Zmienione zachowanie biznesowe → healer
  maskuje błąd; ten przypadek nieudanego testu do naprawy to Lekcja 5.

<!-- END @przeprogramowani/10x-cli -->

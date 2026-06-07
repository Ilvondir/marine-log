# Repository Guidelines

MarineLog is a Laravel 13 web application for publishing public marine and freshwater wildlife observations. Product scope is defined in `@context/foundation/prd.md`; stack and deployment decisions live in `@context/foundation/tech-stack.md`.

## Critical Rules

- Keep work inside the MVP described by the PRD. IUCN rarity scoring, migration alerts, news feeds, user reporting, and a separate mobile app are explicitly out of scope.
- Run PHP, Artisan, Composer, and tests through Laravel Sail once Docker is available. Do not rely on host PHP extensions or versions.
- Never commit `.env`, credentials, uploaded media, `vendor/`, or `node_modules/`. Add reusable defaults without secrets to `@.env.example`.
- Do not edit generated dependencies under `vendor/`. Change application code or Composer constraints instead.
- Preserve `context/`; it contains the product decisions and bootstrap audit trail.

## Common Commands

- `./vendor/bin/sail up -d` starts Laravel, MySQL 8.4, and Redis.
- `./vendor/bin/sail artisan migrate` applies database migrations.
- `./vendor/bin/sail npm run dev` starts the Vite development server.
- `./vendor/bin/sail composer test` clears configuration and runs the test suite.
- `./vendor/bin/sail pint --test` checks PHP formatting; run `./vendor/bin/sail pint` to fix it.
- `./vendor/bin/sail down` stops the containers without deleting named volumes.

## Project Structure

Place HTTP controllers in `app/Http/Controllers`, Eloquent models in `app/Models`, web routes in `routes/web.php`, and Blade/CSS/JavaScript assets under `resources/`. Database schema changes belong in `database/migrations`; factories and seed data belong in their adjacent directories. Sail services are declared in `@compose.yaml`.

## Code Style

Follow `@.editorconfig`: four-space indentation, LF endings, final newlines, and two spaces for YAML except Compose files. Use typed method signatures and return types. Follow the attribute-based model metadata pattern in `@app/Models/User.php`. Format PHP with Pint rather than manual style changes.

## Testing

Use PHPUnit 12. Put framework-integrated HTTP/database behavior in `tests/Feature` and isolated logic in `tests/Unit`. Name methods `test_<behavior>(): void`. Add or update tests for every behavioral change; run a focused test with `./vendor/bin/sail artisan test --filter=<name>`.

There is no CI workflow or commit-message convention yet. Do not claim either exists until the repository adds one.

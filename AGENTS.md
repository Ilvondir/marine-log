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

## 10xDevs AI Toolkit - Module 2, Lesson 3

Review AI-generated code before merge with the **implementation review chain**:

```
/10x-implement -> /10x-impl-review -> triage -> (/10x-lesson | fix | skip | disagree)
```

`/10x-impl-review` is the lesson focus. Review is a quality gate, not an instruction to fix every finding.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Code review (lesson focus)** | |
| `/10x-impl-review <change-id>` | You have implemented code and want a structured review before merge. The skill checks plan adherence, scope discipline, safety and quality, architecture, pattern consistency, and success criteria, then presents findings for triage. |
| **Recurring lesson outcome** | |
| `/10x-lesson` | A finding reveals a recurring project rule or agent failure pattern. Record it in `context/foundation/lessons.md` instead of treating it as a one-off note. |

### Triage discipline

- Severity says how bad the finding is. Impact says how much the decision matters now.
- Valid outcomes: fix now, fix differently, skip, accept as risk, record as recurring rule (`/10x-lesson`), disagree.
- Fix critical findings. Do not burn hours on low-impact observations just because the agent found them.
- Conscious skipping of low-impact findings is a valid review outcome, not negligence.
- If you disagree with a finding, record why. Wrong agent reasoning is also signal.

### Review boundaries

- This lesson reviews implemented code. It does not create the plan, execute new phases, or teach CI review.
- Testing strategy and quality gates are introduced in Module 3.
- Do not use `/10x-contract` as a triage outcome in this lesson.

### Paths used by this lesson

- `context/changes/<change-id>/plan.md` - expected implementation contract
- `context/changes/<change-id>/reviews/` - review output
- `context/foundation/lessons.md` - recurring lessons

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->

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

## 10xDevs AI Toolkit - Module 2, Lesson 2

Turn one roadmap item into the first implementation cycle with the **change planning chain**:

```
/10x-roadmap -> /10x-new -> /10x-plan -> /10x-plan-review -> /10x-implement
```

`/10x-new`, `/10x-plan`, `/10x-plan-review`, and `/10x-implement` are the lesson focus. `/10x-frame` and `/10x-research` are not required rituals here; they are escalation paths introduced in the next lesson.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Change setup (lesson focus)** | |
| `/10x-new <change-id>` | You selected a roadmap item and need a stable change folder. Creates `context/changes/<change-id>/change.md` so planning, implementation, progress, commits, and later review all share one identity. Use AFTER roadmap selection, BEFORE `/10x-plan`. |
| **Planning (lesson focus)** | |
| `/10x-plan <change-id>` | You have a change folder and need a reviewable implementation plan. Reads roadmap context, foundation docs, codebase evidence, and any existing change notes; writes `plan.md` and `plan-brief.md` with phases, file contracts, success criteria, and `## Progress`. |
| **Plan readiness (lesson focus)** | |
| `/10x-plan-review <change-id>` | You have `plan.md` and need a light pre-code readiness check. Use it to catch missing end state, weak contracts, malformed progress, scope drift, or blind spots before code changes begin. |
| **Implementation (lesson focus)** | |
| `/10x-implement <change-id> phase <n>` | You have an approved plan and want to execute one phase with verification, manual gate, commit ritual, and SHA write-back to `## Progress`. |
| **Lifecycle closure** | |
| `/10x-archive <change-id>` | A change is merged or intentionally closed. Move it out of active `context/changes/` into archive state. |

### How the chain hands off

- `/10x-new` creates the durable change identity.
- `/10x-plan` turns that identity into an implementation contract.
- `/10x-plan-review` checks the plan before the agent mutates code.
- `/10x-implement` executes one planned phase, verifies, asks for manual confirmation when needed, commits, and records progress.

### Lesson boundaries

- Plan is the default router after roadmap selection. Start with `/10x-plan` unless the problem is unclear or external evidence is blocking.
- Do not run `/10x-frame + /10x-research` as ceremony for every change.
- Do not turn this lesson into a full end-to-end product build. A checkpoint with a planned and partially or fully implemented stream is valid.
- Code review of the implemented diff belongs to Lesson 3 via `/10x-impl-review`.
- Lifecycle closure via `/10x-archive` after a change is merged or intentionally closed.

### Paths used by this lesson

- `context/foundation/roadmap.md` - upstream roadmap
- `context/changes/<change-id>/change.md` - change identity
- `context/changes/<change-id>/plan.md` - implementation contract
- `context/changes/<change-id>/plan-brief.md` - compressed handoff
- `context/foundation/lessons.md` - recurring rules and pitfalls
- `docs/reference/contract-surfaces.md` - load-bearing names registry

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->

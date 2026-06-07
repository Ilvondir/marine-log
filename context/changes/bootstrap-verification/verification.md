---
bootstrapped_at: 2026-06-07T16:09:42Z
starter_id: laravel
starter_name: Laravel
project_name: marine-log
language_family: php
package_manager: composer
cwd_strategy: subdir-then-move
bootstrapper_confidence: verified
phase_3_status: ok
audit_command: "null"
---

## Hand-off

```yaml
starter_id: laravel
package_manager: composer
project_name: marine-log
hints:
  language_family: php
  team_size: solo
  deployment_target: self-host
  ci_provider: github-actions
  ci_default_flow: auto-deploy-on-merge
  bootstrapper_confidence: verified
  path_taken: standard
  quality_override: true
  self_check_answers: null
  has_auth: true
  has_payments: false
  has_realtime: false
  has_ai: false
  has_background_jobs: false
```

Laravel is the recommended PHP starter for a full-stack web application and fits MarineLog's three-week, after-hours MVP with authentication, user and administrator roles, and media-backed observations. Its strong conventions, mature ecosystem, current documentation, and verified bootstrapper support reduce setup risk for a solo project. Deployment targets a self-hosted Hetzner environment managed through Coolify, with GitHub Actions automatically deploying changes merged into `main`. Laravel does not fully satisfy the explicit-typing quality criterion, so the hand-off records a quality override and the project instructions should compensate with strict typing and validation conventions.

## Pre-scaffold verification

| Signal | Value | Severity | Notes |
| --- | --- | --- | --- |
| npm package | not run | n/a | Non-JavaScript starter. |
| GitHub repo | not run | unavailable | The registry card points to `https://laravel.com/docs`, not a GitHub repository. |

## Scaffold log

**Resolved invocation**: `composer create-project laravel/laravel .bootstrap-scaffold --no-interaction --prefer-dist`
**Strategy**: subdir-then-move
**Exit code**: 0
**Files moved**: 8762
**Conflicts (.scaffold siblings)**: none
**.gitignore handling**: moved silently
**.bootstrap-scaffold cleanup**: deleted

Composer installed `laravel/laravel` v13.8.0 and resolved `laravel/framework` v13.14.0 with 109 packages. Application key generation succeeded. The starter's automatic SQLite migration warned that the PHP runtime does not provide the SQLite driver; the scaffold command still completed successfully.

Post-scaffold smoke checks:

- `php artisan about --only=environment`: passed.
- `php artisan test`: passed, 2 tests and 2 assertions.
- `context/foundation/prd.md` and `context/foundation/tech-stack.md`: preserved.

## Post-scaffold audit

**Tool**: skipped — no built-in audit tool configured for php
**Recommended external tool**: Roave Security Advisories or `local-php-security-checker`.

Composer's install-time audit reported no known security vulnerability advisories. A separate sandboxed `composer audit` retry could not reach Packagist because DNS access was unavailable, so it is not treated as an independent completed audit.

## Hints recorded but not acted on

| Hint | Value |
| --- | --- |
| bootstrapper_confidence | verified |
| quality_override | true |
| path_taken | standard |
| self_check_answers | null |
| team_size | solo |
| deployment_target | self-host |
| ci_provider | github-actions |
| ci_default_flow | auto-deploy-on-merge |
| has_auth | true |
| has_payments | false |
| has_realtime | false |
| has_ai | false |
| has_background_jobs | false |

## Next steps

Next: a future skill will set up agent context (`CLAUDE.md`, `AGENTS.md`). For now, the project is scaffolded and verified.

Useful manual steps in the meantime:

- Repair or replace the existing `.git` marker before using Git; it is present but is not recognized as a valid repository.
- Install or enable `pdo_sqlite` if SQLite will be used locally, then run `php artisan migrate`.
- Review the generated `.env` before committing or deployment.
- Configure a PHP dependency security tool appropriate for the project.

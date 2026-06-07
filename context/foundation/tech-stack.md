---
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
---

## Why this stack

Laravel is the recommended PHP starter for a full-stack web application and fits MarineLog's three-week, after-hours MVP with authentication, user and administrator roles, and media-backed observations. Its strong conventions, mature ecosystem, current documentation, and verified bootstrapper support reduce setup risk for a solo project. Deployment targets a self-hosted Hetzner environment managed through Coolify, with GitHub Actions automatically deploying changes merged into `main`. Laravel does not fully satisfy the explicit-typing quality criterion, so the hand-off records a quality override and the project instructions should compensate with strict typing and validation conventions.

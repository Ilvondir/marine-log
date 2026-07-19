# Test Quality Gates — Implementation Plan

## Overview

Phase 4 of the test plan rollout: lock the quality floor in CI so no regression reaches main.

## Current State

- `.github/workflows/ci-deploy.yml` already runs tests on PRs and push to main
- Deploy is gated by tests (`needs: tests`)
- `storage:link` already runs in CI (addresses Risk #4 in production)
- Missing: Pint lint check as a CI gate

## Changes Made

1. Added `Check code style (Pint)` step to CI workflow after tests
2. Fixed 5 style issues found by Pint across test files and one repository file

## What We're NOT Doing

- No separate workflow file (keep one CI job for simplicity)
- No coverage reporting (overkill for solo MVP)
- No pre-commit hooks (keep developer experience lightweight)

## Progress

- [x] 1.1 Add Pint --test step to CI workflow
- [x] 1.2 Fix all existing Pint violations
- [x] 1.3 Full test suite passes (80 tests)
- [x] 1.4 Pint --test passes (54 files)

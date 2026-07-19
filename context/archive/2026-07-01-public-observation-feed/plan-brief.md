---
change_id: public-observation-feed
title: Public observation feed
status: implemented
created: 2026-07-12
updated: 2026-07-12
archived_at: null
---

## Scope

S-01 from roadmap. Guest (unauthenticated user) can browse published observations in a paginated feed. Each observation card shows species, thumbnail, date, and location. Clicking a card opens the full observation detail (public, no auth required).

## PRD refs

- FR-001: Gość może przeglądać publiczne obserwacje

## Progress

- [x] Phase 1: Repository & Service — add paginated published listing to the repository contract and service
- [x] Phase 2: Controller & Routes — public index and show routes (no auth middleware)
- [x] Phase 3: Blade Views — feed index with observation cards and pagination, make show view public
- [x] Phase 4: Tests — feature tests covering the public feed behavior

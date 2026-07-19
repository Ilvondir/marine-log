---
change_id: publish-observation-flow
title: Publish observation flow
status: implemented
created: 2026-07-12
updated: 2026-07-12
archived_at: null
---

## Scope

S-02 from roadmap. Logged-in user can create and publish an observation with species, date/time, location, and at least one photo. Optional fields: description, videos, water temperature, approximate depth, weather.

## PRD refs

- US-01: Publish observation user story
- FR-002: Account creation (prerequisite, already done)
- FR-003: Observation publication requirements

## Progress

- [x] Phase 1: Data Layer — migrations, models, enum, repositories, factories, bindings
- [x] Phase 2: Service Layer & File Upload — ObservationService with transactional publish + media storage
- [x] Phase 3: Controller & Routes — ObservationController, StoreObservationRequest, routes behind auth
- [x] Phase 4: Blade Views — create form with Leaflet map picker, show view with media gallery, nav link
- [x] Phase 5: Tests — 11 feature tests + 2 unit tests, all passing (21 tests, 88 assertions)

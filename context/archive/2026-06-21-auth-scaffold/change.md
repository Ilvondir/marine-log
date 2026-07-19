---
change_id: auth-scaffold
title: Auth scaffold
status: archived
created: 2026-06-21
updated: 2026-07-19
archived_at: 2026-07-19T00:00:00Z
---

## Notes

F-01 z [roadmap.md](context/foundation/roadmap.md)

## Summary

All three phases completed:
- Phase 1: Marine shell, branded landing page, route surface
- Phase 2: Registration, login, logout, Role model (User/Admin), admin middleware, seeder
- Phase 3: 5 feature tests in AuthScaffoldTest covering full auth flow and role boundary

Architecture follows AGENTS.md conventions (thin controller → AuthService, typed signatures, no business logic in controllers).

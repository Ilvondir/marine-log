---
change_id: test-storage-integrity
title: Prove uploaded media is accessible via public URL end-to-end
status: implemented
created: 2026-07-13
updated: 2026-07-19
archived_at: null
---

## Notes

Phase 3 of the test plan rollout. Covers Risk #4 (uploaded photos inaccessible after deploy due to missing symlink or path mismatch). Tests use real disk (not Storage::fake) to verify the full chain: upload → store → public URL → HTTP 200 with image content.

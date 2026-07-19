# Test Storage Integrity — Implementation Plan

## Overview

Phase 3 of the test plan rollout: prove uploaded media is accessible via public URL. Covers Risk #4 (storage symlink/path mismatch makes photos return 404 after deploy).

## Current State

- All existing upload tests use `Storage::fake('public')` — proves file stored, but NOT publicly accessible
- Views render via `asset('storage/' . $photo->path)` — requires symlink
- Symlink should exist from `artisan storage:link` during setup
- No test currently verifies the URL→file chain end-to-end

## Desired End State

Tests that verify:
1. The storage symlink exists (environment smoke check)
2. After upload, the generated public URL is accessible via HTTP GET
3. The path stored in DB matches what the view would render

## What We're NOT Doing

- No cloud storage testing (S3/R2 — MVP uses local disk)
- No image processing/thumbnail tests
- No video streaming tests
- No load/performance testing of media serving

## Phase 1: Storage Smoke and URL Chain Tests

### Tests to create in `tests/Feature/StorageIntegrityTest.php`:

1. `test_public_storage_symlink_exists(): void`
   - Assert `public_path('storage')` is a symbolic link
   - Assert it points to `storage_path('app/public')`

2. `test_uploaded_photo_is_accessible_via_public_url(): void`
   - ActingAs user, POST a valid observation with a photo (real disk, no Storage::fake)
   - Get the stored Resource record's path
   - HTTP GET to `/storage/{path}` → 200
   - Verify response content-type starts with `image/`
   - Clean up uploaded files in tearDown

3. `test_stored_path_matches_expected_pattern(): void`
   - Upload observation with photo
   - Assert Resource path matches pattern `observations/{id}/{hash}.{ext}`
   - Assert `asset('storage/' . $path)` generates expected URL format

4. `test_deleted_observation_media_is_no_longer_accessible(): void`
   - Upload observation, note URL
   - Delete observation
   - HTTP GET to same URL → 404

### Success Criteria
- All 4 tests pass
- Full suite remains green
- Tests clean up after themselves (no test artifacts left)

## Progress

> Convention: `- [ ]` pending, `- [x]` done.

- [x] 1.1 Create StorageIntegrityTest.php with 4 tests
- [x] 1.2 Full suite passes

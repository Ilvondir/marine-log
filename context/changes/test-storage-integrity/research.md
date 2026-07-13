# Research: test-storage-integrity

> Internal research for Phase 3 of the test plan rollout.
> Covers Risk #4 (uploaded media inaccessible after deploy).

## Storage Configuration

- Disk: `public` (local driver)
- Root: `storage/app/public`
- URL: `{APP_URL}/storage`
- Symlink: `public/storage → storage/app/public` (created by `artisan storage:link`)

## URL Generation Chain

1. File stored by service at: `observations/{observation_id}/{hash}.{ext}` (relative to disk root)
2. DB stores: `path = "observations/{id}/{hash}.{ext}"`
3. View renders: `asset('storage/' . $photo->path)` → `http://localhost/storage/observations/{id}/{hash}.{ext}`
4. Web server resolves: `/storage/` → symlink → `storage/app/public/observations/{id}/{hash}.{ext}`

## Failure Points (Risk #4)

1. **Symlink missing** — `public/storage` doesn't exist → 404 on all stored media
2. **Path mismatch** — service stores at different path than what DB records → 404
3. **URL generation** — `asset()` vs `Storage::url()` mismatch (currently using `asset('storage/' . path)`)
4. **Disk misconfiguration** — file stored to wrong disk or path prefix

## Testing Approach

- **Cannot use `Storage::fake('public')`** — that replaces the real disk and breaks the symlink chain
- Instead: use real disk in test, store a real file, verify it's accessible via HTTP GET
- Clean up test files in `tearDown()`
- The symlink must exist in the test environment (it should from `sail artisan storage:link` during setup)

## Alternative: Test with Fake Disk but Verify URL Logic

Since the actual symlink test depends on environment setup, a more reliable approach:
1. Use `Storage::fake('public')` for the upload part
2. Separately assert that the URL pattern matches what the view expects
3. Add a dedicated "smoke" test that verifies the symlink exists (filesystem check, not HTTP)

## Existing Test Gap

Current tests use `Storage::fake('public')` → `Storage::disk('public')->assertExists($path)`. This proves the file is stored but does NOT prove it's publicly accessible. The symlink/URL chain is untested.

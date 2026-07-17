/**
 * E2E test — Risk #2 from context/foundation/test-plan.md
 *
 * "Użytkownik nie-właściciel może edytować/usunąć cudzą obserwację
 *  (IDOR — policy nie blokuje)"
 *
 * Business scenario: User A creates an observation. User B (different
 * authenticated user) attempts to access User A's observation edit page
 * via direct URL — must be blocked (403), not shown the edit form.
 *
 * Real boundaries: auth, routing, policy, DB
 * Mocked boundaries: none
 *
 * Regression caught: If ObservationPolicy stops checking ownership,
 * this test will fail because User B would see the edit form instead of 403.
 */
import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Override project-level storageState — this test manages its own contexts
test.use({ storageState: { cookies: [], origins: [] } });

test('non-owner cannot access edit page of another user observation (risk #2 — IDOR)', async ({ browser }) => {
  // --- User A: create an observation (using stored auth) ---
  const contextA = await browser.newContext({ storageState: 'playwright/.auth/user.json' });
  const pageA = await contextA.newPage();

  const uniqueId = Date.now();
  const speciesName = `IDOR Owner ${uniqueId}`;

  await pageA.goto('/observations/create');
  await expect(pageA.getByRole('heading', { name: 'Publish a wildlife observation' })).toBeVisible();

  await pageA.getByLabel('Species *').fill(speciesName);
  await pageA.getByLabel('Date and time of observation *').fill('2025-06-15T10:30');
  await pageA.getByLabel('Location name *').fill('Owner Location');

  const map = pageA.locator('.leaflet-container');
  if (await map.isVisible()) {
    await map.click({ position: { x: 200, y: 150 } });
  }

  await pageA.getByLabel('Photos * (at least one, max 10MB each)')
    .setInputFiles(path.resolve(__dirname, 'fixtures/test-photo.jpg'));

  await pageA.getByRole('button', { name: 'Publish observation' }).click();
  await pageA.waitForURL(/\/observations\/(\d+)$/);

  const observationId = pageA.url().match(/\/observations\/(\d+)$/)?.[1];
  expect(observationId).toBeTruthy();

  // --- User B: login as a different user ---
  // Use a second browser context, login via the login form
  const contextB = await browser.newContext();
  const pageB = await contextB.newPage();

  await pageB.goto('/login');
  await expect(pageB.getByRole('heading', { name: /welcome back/i })).toBeVisible();
  await pageB.getByLabel(/email/i).fill('idor-attacker@example.com');
  await pageB.getByLabel(/password/i).fill('password');
  await pageB.getByRole('button', { name: 'Sign in' }).click();

  // Wait for login to succeed — redirect to home
  await expect(pageB.getByRole('button', { name: 'Sign out' })).toBeVisible();

  // Attempt to access User A's observation edit page via direct URL
  const response = await pageB.goto(`/observations/${observationId}/edit`);

  // The app should return 403 Forbidden (policy blocks non-owner)
  expect(response?.status()).toBe(403);

  // Verify the edit form is NOT rendered
  await expect(pageB.getByRole('heading', { name: 'Edit your observation' })).not.toBeVisible();

  // --- Cleanup ---
  // User A deletes their observation
  await pageA.goto(`/observations/${observationId}/edit`);
  pageA.once('dialog', (dialog) => dialog.accept());
  await pageA.getByRole('button', { name: 'Delete observation' }).click();
  await pageA.waitForURL('/');

  await contextA.close();
  await contextB.close();
});

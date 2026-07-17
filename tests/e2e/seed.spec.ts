/**
 * Seed test — the exemplar every generated E2E test must follow.
 *
 * Risk: #1 from context/foundation/test-plan.md
 *   "Zalogowany użytkownik nie może opublikować obserwacji
 *    (publish flow regresja — walidacja odrzuca poprawne dane
 *    lub service nie tworzy rekordu)"
 *
 * Conventions demonstrated:
 *   - getByRole / getByLabel as primary locators (no CSS/XPath)
 *   - Wait for state, never waitForTimeout
 *   - Unique test data via Date.now() suffix
 *   - Full isolation: setup → action → assertion → cleanup
 *   - storageState for auth (no UI login per test)
 *   - Test name tied to the risk it protects
 */
import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

test.use({ storageState: 'playwright/.auth/user.json' });

test('publish observation creates visible record (risk #1 — publish flow)', async ({ page }) => {
  const uniqueId = Date.now();
  const speciesName = `Test Species ${uniqueId}`;
  const description = `E2E observation notes ${uniqueId}`;

  // Navigate to the create form
  await page.goto('/observations/create');
  await expect(page.getByRole('heading', { name: 'Publish a wildlife observation' })).toBeVisible();

  // Fill the observation form using accessible locators
  await page.getByLabel('Species *').fill(speciesName);
  await page.getByLabel('Date and time of observation *').fill('2025-06-15T10:30');
  await page.getByLabel('Location name *').fill('Great Barrier Reef');
  await page.getByLabel('Description (optional)').fill(description);

  // Click on the map to set coordinates (the map requires a click)
  const map = page.locator('.leaflet-container');
  if (await map.isVisible()) {
    await map.click({ position: { x: 200, y: 150 } });
  }

  // Upload a photo (required: at least one)
  const fileInput = page.getByLabel('Photos * (at least one, max 10MB each)');
  await fileInput.setInputFiles(path.resolve(__dirname, 'fixtures/test-photo.jpg'));

  // Submit
  await page.getByRole('button', { name: 'Publish observation' }).click();

  // Wait for redirect to the observation detail page — state-based wait
  await page.waitForURL(/\/observations\/\d+$/);

  // Assert the business outcome: observation is visible with correct data
  await expect(page.getByRole('heading', { name: speciesName })).toBeVisible();
  await expect(page.getByText('Great Barrier Reef').first()).toBeVisible();

  // Cleanup: delete the observation we just created
  await page.getByRole('link', { name: /edit/i }).click();
  await page.waitForURL(/\/observations\/\d+\/edit/);

  // The delete button triggers a browser confirm() dialog
  page.once('dialog', (dialog) => dialog.accept());
  await page.getByRole('button', { name: 'Delete observation' }).click();

  // Verify cleanup succeeded — redirected to home after deletion
  await page.waitForURL('/');
  await expect(page.getByRole('heading', { name: speciesName })).not.toBeVisible();
});

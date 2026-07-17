/**
 * Auth setup — runs once before all tests to create storageState.
 * Logs in via the login form and saves session to playwright/.auth/user.json.
 */
import { test as setup, expect } from '@playwright/test';

const authFile = 'playwright/.auth/user.json';

setup('authenticate as test user', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: /welcome back/i })).toBeVisible();

  await page.getByLabel(/email/i).fill(process.env.E2E_USER_EMAIL || 'test@example.com');
  await page.getByLabel(/password|hasło/i).fill(process.env.E2E_USER_PASSWORD || 'password');
  await page.getByRole('button', { name: /sign in/i }).click();

  // Wait for redirect after successful login
  await page.waitForURL(/\/(observations|dashboard)?$/);

  // Save the authenticated state
  await page.context().storageState({ path: authFile });
});

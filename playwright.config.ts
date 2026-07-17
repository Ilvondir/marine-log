import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for MarineLog E2E tests.
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI ? 'html' : 'list',
  use: {
    baseURL: process.env.APP_URL || 'http://localhost:80',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    /* Auth setup — runs first, saves storageState for other projects */
    {
      name: 'setup',
      testMatch: /auth\.setup\.ts/,
    },
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'playwright/.auth/user.json',
      },
      dependencies: ['setup'],
    },
  ],
  /* Start the Laravel dev server before running tests (local dev only) */
  ...(process.env.CI
    ? {}
    : {
        webServer: {
          command: 'php artisan serve --port=80',
          url: 'http://localhost:80',
          reuseExistingServer: true,
        },
      }),
});

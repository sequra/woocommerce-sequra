// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config({ path: path.resolve(__dirname, '.env') });

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: './tests-e2e',
  timeout: 5 * 60 * 1000, // 5 minutes
  /* Run tests in files in parallel */
  // fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  // retries: process.env.CI ? 1 : 0,
  retries: 0,
  /* Opt out of parallel tests on CI. */
  // workers: process.env.CI ? 1 : undefined,
  workers: 4,
  // workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: process.env.CI ? 'dot' : 'list',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    // baseURL: 'http://127.0.0.1:3000',
    baseURL: process.env.WP_URL || 'https://sq.wp.michel.ngrok.dev',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'configuration-onboarding',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '001-configuration-onboarding.spec.js',
    },
    {
      name: 'configuration',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '002-configuration.spec.js',
      dependencies: ['configuration-onboarding'],
    },
    {
      name: 'checkout-product',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '003-checkout-product.spec.js',
      dependencies: ['configuration'],
    },
    {
      name: 'checkout-service',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '004-checkout-service.spec.js',
      dependencies: ['checkout-product'],
    }
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://127.0.0.1:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});


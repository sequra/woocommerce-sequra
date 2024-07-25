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
  testDir: './tests-e2e/specs',
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
    baseURL: process.env.WP_URL,

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
      name: 'configuration-payment-methods',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '002-configuration-payment-methods.spec.js',
      dependencies: ['configuration-onboarding'],
    },
    {
      name: 'configuration-advanced',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '003-configuration-advanced.spec.js',
      dependencies: ['configuration-payment-methods'],
    },
    {
      name: 'configuration-general',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '004-configuration-general.spec.js',
      dependencies: ['configuration-advanced'],
    },
    {
      name: 'checkout-product',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '099-checkout-product.spec.js',
      dependencies: ['configuration-general'],
    },
    {
      name: 'checkout-service',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '100-checkout-service.spec.js',
      dependencies: ['checkout-product'],
    }
  ],
});


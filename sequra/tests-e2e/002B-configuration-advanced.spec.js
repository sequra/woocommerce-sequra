import { test, expect } from './fixtures';

test.beforeAll('Setup', async ({ request }) => {
  const response = await request.post('./?sq-webhook=dummy_config');
  expect(response.status()).toBe(200);
  const json = await response.json();
  expect(json.success).toBe(true);
});

test.describe.configure({ mode: 'serial' });
test.describe('Configuration', () => {
  // TODO: Implement tests for each configuration page.
  test('Enable logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
    await page.waitForTimeout(5000);
  });
  test('Reload logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
    await page.waitForTimeout(5000);
  });
  test('Remove logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
    await page.waitForTimeout(5000);
  });
  test('Change minimum severity level', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
    await page.waitForTimeout(5000);
  });
});
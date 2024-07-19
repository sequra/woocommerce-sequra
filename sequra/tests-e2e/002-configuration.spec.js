import { test, expect } from './fixtures';

test.beforeAll('Setup', async ({ request }) => {
  const response = await request.post('./?sq-webhook=dummy_config');
  expect(response.status()).toBe(200);
  const json = await response.json();
  expect(json.success).toBe(true);
});

test.describe.configure({ mode: 'parallel' });
test.describe('Configuration', () => {
  // TODO: Implement tests for each configuration page.
});
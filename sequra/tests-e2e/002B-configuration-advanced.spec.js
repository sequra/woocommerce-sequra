import { test, expect } from './fixtures';

test.beforeAll('Setup', async ({ request }) => {
  // 1. Clear configuration to disable logs.
  // 2. Configure the plugin with dummy merchant.
  // 3. Remove Log file.
  const webhooks = [
    'clear_config',
    'dummy_config',
    'remove_log',
  ];

  for (const webhook of webhooks) {
    const response = await request.post(`./?sq-webhook=${webhook}`);
    expect(response.status(), 'Webhook response has HTTP 200 code').toBe(200);
    const json = await response.json();
    expect(json.success, 'Webhook was processed successfully').toBe(true);
  }

});

test.describe.configure({ mode: 'serial' });
test.describe('Configuration', () => {
  test('Enable logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    await page.getByRole('cell', { name: 'No entries found' }).waitFor({ state: 'visible', timeout: 5000 });
    const enableLogsCheckbox = page.locator('input.sqp-toggle-input');
    await expect(enableLogsCheckbox, '"Enable logs" toggle is OFF').toBeChecked({ checked: false });
    await page.locator('.sq-toggle').click();
    await configuration.expectLoadingShowAndHide({ page });
    await page.reload();
    await expect(page.locator('.sqm--log').first(), 'Log datatable has content').toBeVisible();
    await expect(page.locator('.datatable-pagination-list-item.sq-datatable__active'), 'Logs pagination is visible').toBeVisible();
  });

  test('Reload logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
  });

  test('Remove logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
  });

  test('Change minimum severity level', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    // TODO:
  });
});
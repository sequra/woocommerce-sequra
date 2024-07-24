import { test, expect } from './fixtures';

test.beforeEach('Setup', async ({ request }) => {
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
    await configuration.expectLogIsEmpty({ page });
    await configuration.enableLogs({ page });
    await page.reload();
    await configuration.expectLogHasContent({ page });
  });

  test('Reload logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    await configuration.expectLogIsEmpty({ page });
    await configuration.enableLogs({ page });
    await configuration.reloadLogs({ page });
    await configuration.expectLogHasContent({ page });
  });

  test('Remove logs', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    await configuration.expectLogIsEmpty({ page });
    await configuration.enableLogs({ page });
    await page.reload({ waitUntil: 'domcontentloaded' });
    await configuration.expectLogHasContent({ page });
    await configuration.enableLogs({ page, enable: false });
    await configuration.removeLogs({ page });
    await configuration.expectLogIsEmpty({ page });
    await page.reload({ waitUntil: 'domcontentloaded' });
    await configuration.expectLogIsEmpty({ page });
  });

  test('Change minimum severity level', async ({ page, request, configuration }) => {

    const logs = [
      {
        level: 'DEBUG',
        message: 'Log with severity level of DEBUG',
      },
      {
        level: 'INFO',
        message: 'Log with severity level of INFO',
      },
      {
        level: 'WARNING',
        message: 'Log with severity level of WARNING',
      },
      {
        level: 'ERROR',
        message: 'Log with severity level of ERROR',
      },
    ];
    const severityLevels = [
      {
        name: 'DEBUG',
        expectedLogs: logs,
        nonExpectedLogs: [],
      },
      {
        name: 'INFO',
        expectedLogs: logs.slice(1),
        nonExpectedLogs: [logs[0]],
      },
      {
        name: 'WARNING',
        expectedLogs: logs.slice(2),
        nonExpectedLogs: logs.slice(0, 2),
      },
      {
        name: 'ERROR',
        expectedLogs: logs.slice(3),
        nonExpectedLogs: logs.slice(0, 3),
      },
    ]

    await configuration.goto({ page, configurationPage: 'advanced-debug' });
    await configuration.expectLogIsEmpty({ page });

    for (const { name, expectedLogs, nonExpectedLogs } of severityLevels) {
      await configuration.setSeverityLevel({ page, severityLevel: name });
      await configuration.enableLogs({ page });
      await configuration.printLogs({ request });
      await configuration.enableLogs({ page, enable: false });
      await page.reload();
      await configuration.expectLoadingShowAndHide({ page });
      await configuration.expectLogHasContent({ page, expectedLogs, nonExpectedLogs });
    }
  });
});
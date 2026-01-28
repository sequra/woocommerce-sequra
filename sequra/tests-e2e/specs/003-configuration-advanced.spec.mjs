import { test } from '../fixtures/test.mjs';

test.describe('Configuration', () => {
  test('Enable logs', async ({ helper, page, advancedSettingsPage }) => {
    // Setup
    const { dummy_config, clear_config, remove_log } = helper.webhooks;
    await helper.executeWebhooksSequentially([{ webhook: clear_config }, { webhook: dummy_config }, { webhook: remove_log }]);
    // Execution
    await advancedSettingsPage.goto();
    await advancedSettingsPage.expectLogIsEmpty();
    await advancedSettingsPage.enableLogs();
    await page.reload();
    await advancedSettingsPage.expectLoadingShowAndHide();
    await advancedSettingsPage.expectLogHasContent();
  });

  test('Reload logs', async ({ helper, advancedSettingsPage }) => {
    // Setup
    const { dummy_config, clear_config, remove_log } = helper.webhooks;
    await helper.executeWebhooksSequentially([{ webhook: clear_config }, { webhook: dummy_config }, { webhook: remove_log }]);
    // Execution
    await advancedSettingsPage.goto();
    await advancedSettingsPage.expectLogIsEmpty();
    await advancedSettingsPage.enableLogs();
    await advancedSettingsPage.reloadLogs();
    await advancedSettingsPage.expectLogHasContent();
  });

  test('Remove logs', async ({ helper, page, advancedSettingsPage }) => {
    // Setup
    const { dummy_config, clear_config, remove_log } = helper.webhooks;
    await helper.executeWebhooksSequentially([{ webhook: clear_config }, { webhook: dummy_config }, { webhook: remove_log }]);
    // Execution
    await advancedSettingsPage.goto();
    await advancedSettingsPage.expectLogIsEmpty();
    await advancedSettingsPage.enableLogs();
    await page.reload();
    await advancedSettingsPage.expectLoadingShowAndHide();
    await advancedSettingsPage.expectLogHasContent();
    await advancedSettingsPage.enableLogs(false);
    await advancedSettingsPage.removeLogs();
    await advancedSettingsPage.expectLogIsEmpty();
    await page.reload();
    await advancedSettingsPage.expectLoadingShowAndHide();
    await advancedSettingsPage.expectLogIsEmpty();
  });

  test('Change minimum severity level', async ({ helper, page, advancedSettingsPage }) => {

    // Setup
    const { dummy_config, clear_config, remove_log, print_logs } = helper.webhooks;
    await helper.executeWebhooksSequentially([{ webhook: clear_config }, { webhook: dummy_config }, { webhook: remove_log }]);
    
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

    // Execution
    await advancedSettingsPage.goto();
    await advancedSettingsPage.expectLogIsEmpty();
    for (const { name, expectedLogs, nonExpectedLogs } of severityLevels) {
      await advancedSettingsPage.setSeverityLevel(name);
      await advancedSettingsPage.enableLogs();
      await helper.executeWebhook({ webhook: print_logs });
      await advancedSettingsPage.enableLogs(false);
      await page.reload();
      await advancedSettingsPage.expectLoadingShowAndHide();
      await advancedSettingsPage.expectLogHasContent({ expectedLogs, nonExpectedLogs });
    }
  });
});
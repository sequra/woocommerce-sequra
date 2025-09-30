import { test } from '../../fixtures/test';

test.describe('Configuration', () => {
  test('Enable logs', async ({ page, advancedSettingsPage }) => {
    await advancedSettingsPage.expectLogIsEmpty();
    await advancedSettingsPage.enableLogs({});
    await page.reload();
    await advancedSettingsPage.expectLogHasContent({});
  });

  test('Reload logs', async ({ advancedSettingsPage }) => {
    await advancedSettingsPage.expectLogIsEmpty();
    await advancedSettingsPage.enableLogs({});
    await advancedSettingsPage.reloadLogs();
    await advancedSettingsPage.expectLogHasContent({});
  });

  test('Remove logs', async ({ page, advancedSettingsPage }) => {
    await advancedSettingsPage.expectLogIsEmpty();
    await advancedSettingsPage.enableLogs({});
    await page.reload();
    await advancedSettingsPage.expectLogHasContent({});
    await advancedSettingsPage.enableLogs({ enable: false });
    await advancedSettingsPage.removeLogs();
    await advancedSettingsPage.expectLogIsEmpty();
    await page.reload();
    await advancedSettingsPage.expectLogIsEmpty();
  });

  test('Change minimum severity level', async ({ page, advancedSettingsPage }) => {

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

    await advancedSettingsPage.expectLogIsEmpty();

    for (const { name, expectedLogs, nonExpectedLogs } of severityLevels) {
      await advancedSettingsPage.setSeverityLevel({ severityLevel: name });
      await advancedSettingsPage.enableLogs({});
      await advancedSettingsPage.printLogs();
      await advancedSettingsPage.enableLogs({ enable: false });
      await page.reload();
      await advancedSettingsPage.expectLoadingShowAndHide();
      await advancedSettingsPage.expectLogHasContent({ expectedLogs, nonExpectedLogs });
    }
  });
});
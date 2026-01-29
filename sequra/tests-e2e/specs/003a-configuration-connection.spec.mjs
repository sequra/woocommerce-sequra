import { DataProvider } from 'playwright-fixture-for-plugins';
import { test } from '../fixtures/test';

test.describe('Connection settings', () => {

  test('Disconnect', async ({ helper, connectionSettingsPage }) => {
    // Setup
    const { dummy_config, clear_config } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config }); // Setup for physical products.
    const credential = {
      username: DataProvider.DEFAULT_USERNAME,
      password: process.env.DUMMY_PASSWORD,
      name: 'SVEA'
    };

    // Execution
    await connectionSettingsPage.goto();
    await connectionSettingsPage.expectLoadingShowAndHide();
    await connectionSettingsPage.disconnect({ credential }); // Disconnect the SVEA credential.
    await connectionSettingsPage.fillManageDeploymentTargetsForm({ credential, save: false }); // Test the cancel button in the manage deployment targets form.
    await connectionSettingsPage.fillManageDeploymentTargetsForm({ credential, save: true }); // Test the save button in the manage deployment targets form.
    await connectionSettingsPage.disconnectAll(); // Disconnect all credentials.
  });
});
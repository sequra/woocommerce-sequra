import { DataProvider } from 'playwright-fixture-for-plugins';
import { test } from '../fixtures/test.mjs';

test.describe('Configuration Onboarding', () => {
  test('Configure using dummy', async ({ helper, dataProvider, onboardingSettingsPage }) => {
    // Setup
    const { clear_config } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration
    const username = DataProvider.DEFAULT_USERNAME;
    const credential = { name: 'seQura', username: username, password: process.env.DUMMY_PASSWORD };
    const connect = {
      env: 'sandbox', 
      credentials: [
        credential,
        { ...credential, name: 'SVEA' }
      ]
    };
    const countriesForm = { countries: dataProvider.countriesMerchantRefs(username) };
    const deploymentTargets = { deploymentTargets: dataProvider.deploymentTargetsOptions() };
    // Execution
    await onboardingSettingsPage.goto();
    await onboardingSettingsPage.expectLoadingShowAndHide();
    await onboardingSettingsPage.fillDeploymentTargetsForm(deploymentTargets);
    await onboardingSettingsPage.fillConnectForm(connect);
    await onboardingSettingsPage.fillCountriesForm(countriesForm);
  });
});
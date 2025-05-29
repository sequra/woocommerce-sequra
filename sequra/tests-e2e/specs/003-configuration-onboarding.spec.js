import { test } from '../fixtures/test';

test.describe('Configuration Onboarding', () => {
  test('Configure using dummy', async ({ onboardingSettingsPage }) => {
    await onboardingSettingsPage.fillConnectForm({});
    await onboardingSettingsPage.fillCountriesForm({ countries: ['ES', 'PT', 'IT'] });
    await onboardingSettingsPage.fillWidgetsForm({});
  });
});
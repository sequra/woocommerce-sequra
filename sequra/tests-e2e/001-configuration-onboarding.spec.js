import { test, expect } from './fixtures';

test.beforeAll('Setup', async ({ request }) => {
  const response = await request.post('./?sq-webhook=clear_config');
  expect(response.status()).toBe(200);
  const json = await response.json();
  expect(json.success).toBe(true);
});

test.describe.configure({ mode: 'parallel' });
test.describe('Configuration Onboarding', () => {
  // TODO: implement tests for the onboarding process.

  test.only('Configure using dummy', async ({ page, configuration }) => {
    await configuration.goto({ page, configurationPage: '#onboarding-connect' });
    await configuration.fillOnboardingConnectForm({ page });
    await configuration.fillOnboardingCountriesForm({ page, merchant: 'dummy', countries: ['ES', 'FR', 'PT', 'IT'] });
    await configuration.fillOnboardingWidgetsForm({ page, merchant: 'dummy' });
  });
});
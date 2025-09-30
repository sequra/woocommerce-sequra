import { test } from '../../fixtures/test';

test.describe('Connection settings', () => {

  test('Disconnect', async ({ connectionSettingsPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await connectionSettingsPage.goto();
    await connectionSettingsPage.expectLoadingShowAndHide();
    await connectionSettingsPage.disconnect();
  });

  test('Change', async ({ page, connectionSettingsPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await connectionSettingsPage.goto();
    await connectionSettingsPage.expectLoadingShowAndHide();

    const wrongConnOptions = {
      username: 'dummy_username',
      password: 'dummy_password',
      env: 'live'
    };

    const dummyServicesConnOptions = connectionSettingsPage.getDummyServicesConnectionOptions();
    const dummyConnOptions = connectionSettingsPage.getDummyConnectionOptions();

    // Test cancellation of the changes
    await connectionSettingsPage.fill(wrongConnOptions);
    await connectionSettingsPage.cancel();
    await connectionSettingsPage.expectToHaveValues(dummyConnOptions); // The default values are dummy values in sandbox.

    // Test wrong values keeping env in sandbox.
    await connectionSettingsPage.fill({ ...wrongConnOptions, env: 'sandbox' });
    await connectionSettingsPage.save({ expectLoadingShowAndHide: true });
    await page.locator('.sqp-alert-title').filter({ hasText: 'Invalid username or password. Validate connection data.' }).waitFor({ state: 'visible' });

    // Test wrong values changing env to live.
    await page.reload();
    await connectionSettingsPage.expectLoadingShowAndHide();
    await connectionSettingsPage.fill(wrongConnOptions);
    await connectionSettingsPage.save({ expectLoadingShowAndHide: false });
    await connectionSettingsPage.confirmModal();
    await page.locator('.sqp-alert-title').filter({ hasText: 'Invalid username or password. Validate connection data.' }).waitFor({ state: 'visible' });

    // Test valid values.
    await page.reload();
    await connectionSettingsPage.expectLoadingShowAndHide();
    await connectionSettingsPage.fill(dummyServicesConnOptions);
    await connectionSettingsPage.save({ expectLoadingShowAndHide: true });
    await page.waitForURL(/#onboarding-countries/);
  });
});
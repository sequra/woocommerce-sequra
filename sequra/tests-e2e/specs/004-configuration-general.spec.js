import { test } from '../fixtures/test';

test.describe.configure({ mode: 'serial' });
test.describe('Configuration', () => {

  test('Change allowed IP addresses', async ({ generalSettingsPage, productPage, checkoutPage }) => {

    await checkoutPage.setupForPhysicalProducts();

    // TODO: test cancellation of the changes

    // TODO: test with invalid IP addresses

    const publicIP = await generalSettingsPage.getPublicIP();
    const notAllowedIPAddressesMatrix = [
      ['8.8.8.8']
    ]
    const allowedIPAddressesMatrix = [
      [],
      [publicIP],
      [publicIP, '8.8.8.8']
    ]

    const fillAndAssert = async (ipAddresses, available) => {
      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
      await generalSettingsPage.fillAllowedIPAddresses(ipAddresses);
      await generalSettingsPage.save();

      await generalSettingsPage.logout();

      await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

      await checkoutPage.goto();
      await checkoutPage.expectAnyPaymentMethod({ available });
    }

    for (const ipAddresses of notAllowedIPAddressesMatrix) {
      await fillAndAssert(ipAddresses, false);
    }

    for (const ipAddresses of allowedIPAddressesMatrix) {
      await fillAndAssert(ipAddresses, true);
    }
  });
});
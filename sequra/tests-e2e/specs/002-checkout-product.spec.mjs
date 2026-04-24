import { test } from '../fixtures/test.mjs';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Product checkout', () => {
  test('Make a 🍊 payment with "Review test approve" names', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields, toggle_delegated_selection } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] },
      { webhook: toggle_delegated_selection, args: [{ name: 'value', value: '0' }] }
    ]);
    const shopper = dataProvider.shopper('approve');

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    // await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'i1' });
    // await checkoutPage.waitForOrderSuccess(); // Skip this to speed up the test.
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider);
    await checkoutPage.expectOrderChangeTo(backOffice, { fromStatus: 'On-hold', toStatus: 'Processing' });
  });

  test('Make a 🍊 payment with "Review test cancel" names', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields, toggle_delegated_selection } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] },
      { webhook: toggle_delegated_selection, args: [{ name: 'value', value: '0' }] }
    ]);
    const shopper = dataProvider.shopper('cancel');

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    // await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'i1' });
    // await checkoutPage.waitForOrderSuccess(); // Skip this to speed up the test.
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider);
    await checkoutPage.expectOrderChangeTo(backOffice, { fromStatus: 'On-hold', toStatus: 'Cancelled' });
  });
});
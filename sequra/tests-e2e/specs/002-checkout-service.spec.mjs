import { test } from '../fixtures/test.mjs';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Service checkout', () => {

  test('Order of virtual product is set as processing after checkout', async ({ helper, dataProvider, productPage, checkoutPage, backOffice }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_services_config, checkout_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_services_config }
    ]);
    const shopper = dataProvider.shopper();

    // Execution
    await productPage.addToCart({ slug: 'album', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: false, ...shopper });
    // await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider, { isOrderForService: true });
    await checkoutPage.expectOrderChangeTo(backOffice, { toStatus: 'Processing' });
  });

  test('Order of virtual & downloadable product is set as completed after checkout', async ({ helper, dataProvider, productPage, checkoutPage, backOffice }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_services_config, checkout_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_services_config }
    ]);
    const shopper = dataProvider.shopper();

    // Execution
    await productPage.addToCart({ slug: 'downloadable-album', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: false, ...shopper });
    // await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider, { isOrderForService: true });
    await checkoutPage.expectOrderChangeTo(backOffice, { toStatus: 'Completed' });
  });
});
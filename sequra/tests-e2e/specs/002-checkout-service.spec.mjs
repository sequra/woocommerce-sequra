import { test } from '../fixtures/test.mjs';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Service checkout', () => {

  test('Multiple services order is set as processing after checkout', async ({ helper, dataProvider, productPage, checkoutPage, backOffice }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_services_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_services_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '1' }] }
    ]);
    const shopper = dataProvider.shopper();

    // Execution
    await productPage.addToCart({ slug: 'album', quantity: 2 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: false, ...shopper });
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.waitForOrderSuccess(); // remove this line once the issue is resolved
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider, { isOrderForService: true });
    await checkoutPage.expectOrderChangeTo(backOffice, { toStatus: 'Processing' });
  });

  test('Service order of virtual & downloadable product is set as completed after checkout', async ({ helper, dataProvider, productPage, checkoutPage, backOffice }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_services_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_services_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '1' }] }
    ]);
    const shopper = dataProvider.shopper();

    // Execution
    await productPage.addToCart({ slug: 'downloadable-album', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: false, ...shopper });
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.waitForOrderSuccess(); // remove this line once the issue is resolved
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider, { isOrderForService: true });
    await checkoutPage.expectOrderChangeTo(backOffice, { toStatus: 'Completed' });
  });
});
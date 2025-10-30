import { test } from '../fixtures/test.mjs';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Product checkout', () => {

  test('All available seQura products appear in the checkout', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    const shopper = dataProvider.shopper();
    const paymentMethods = dataProvider.checkoutPaymentMethods();
    await helper.executeWebhooksSequentially([
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] }
    ]);

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    for (const uiVersion of [DataProvider.UI_CLASSIC, DataProvider.UI_BLOCKS]) {
      // Set the UI version and a compatible theme.
      const theme = dataProvider.themeForUiVersion(uiVersion);
      await helper.executeWebhooksSequentially([
        { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
        { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] }
      ]);

      await checkoutPage.goto({ force: true });
      await checkoutPage.fillForm({ isShipping: uiVersion === DataProvider.UI_BLOCKS, ...shopper });
      await checkoutPage.expectPaymentMethodsBeingReloaded();
      for (const paymentMethod of paymentMethods) {
        await checkoutPage.expectPaymentMethodToBeVisible(paymentMethod);
        await checkoutPage.openAndCloseEducationalPopup(paymentMethod);
      }
    }
  });

  test('Complete a successful payment with SeQura', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] }
    ]);
    const shopper = dataProvider.shopper();

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    // await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'i1' });
    await checkoutPage.waitForOrderSuccess();
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider);
  });

  test('Complete a successful payment with SVEA', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] }
    ]);
    const shopper = dataProvider.shopper('france');

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    // await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.waitForOrderSuccess();
    await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider);
  });

  test('Make a 🍊 payment with "Review test approve" names', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] }
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
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] }
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
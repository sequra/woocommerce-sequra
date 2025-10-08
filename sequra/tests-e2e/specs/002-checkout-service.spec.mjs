import { test } from '../fixtures/test.mjs';

test.describe('Service checkout', () => {

  test('Order of virtual product is set as processing after checkout', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const { dummy_services_config, checkout_version } = helper.webhooks;
    await helper.executeWebhook({ webhook: dummy_services_config });
    await helper.executeWebhook({ webhook: checkout_version, args: [{ name: 'version', value: 'blocks' }] });
    const shopper = dataProvider.shopper();
    
    // Execution
    await productPage.addToCart({ slug: 'album', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.expectOrderHasTheCorrectMerchantId('ES', helper, dataProvider);
    await checkoutPage.expectOrderChangeTo(backOffice, { toStatus: 'Processing' });
  });

  test('Order of virtual & downloadable product is set as completed after checkout', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const { dummy_services_config, checkout_version } = helper.webhooks;
    await helper.executeWebhook({ webhook: dummy_services_config });
    await helper.executeWebhook({ webhook: checkout_version, args: [{ name: 'version', value: 'blocks' }] });
    const shopper = dataProvider.shopper();
    
    // Execution
    await productPage.addToCart({ slug: 'downloadable-album', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    await checkoutPage.expectOrderHasTheCorrectMerchantId('ES', helper, dataProvider);
    await checkoutPage.expectOrderChangeTo(backOffice, { toStatus: 'Completed' });
  });
});
import { test } from '../fixtures/test.mjs';

test.describe('Product checkout', () => {

  test('All available seQura products appear in the checkout', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const { dummy_config, checkout_version } = helper.webhooks;
    const shopper = dataProvider.shopper();
    const paymentMethods = dataProvider.checkoutPaymentMethods();
    await helper.executeWebhook({ webhook: dummy_config }); // Setup for physical products.

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    for (const version of ['classic', 'blocks']) {
      await helper.executeWebhook({ webhook: checkout_version, args: [{ name: 'version', value: version }] });
      await checkoutPage.goto({force: true});
      await checkoutPage.fillForm({isShipping: version === 'blocks', ...shopper});
      await checkoutPage.expectPaymentMethodsBeingReloaded();
      for (const paymentMethod of paymentMethods) {
        await checkoutPage.expectPaymentMethodToBeVisible(paymentMethod);
        await checkoutPage.openAndCloseEducationalPopup(paymentMethod);
      }
    }
  });

  test('Complete a successful payment with SeQura', async ({ helper, dataProvider, productPage, checkoutPage }) => {
    // Setup
    const { dummy_config, checkout_version } = helper.webhooks;
    await helper.executeWebhook({ webhook: dummy_config }); // Setup for physical products.
    await helper.executeWebhook({ webhook: checkout_version, args: [{ name: 'version', value: 'blocks' }] }); // Use modern checkout.
    const shopper = dataProvider.shopper();

    // Execution
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({isShipping: true, ...shopper});
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrder({ ...shopper, product: 'i1' });
    await checkoutPage.waitForOrderSuccess();
    await checkoutPage.expectOrderHasTheCorrectMerchantId('ES', helper, dataProvider);
  });

  // test('Complete a successful payment with SVEA', async ({ helper, dataProvider, productPage, checkoutPage }) => {
  //   // Setup
  //   const { dummy_config } = helper.webhooks;
  //   await helper.executeWebhook({ webhook: dummy_config }); // Setup for physical products.
  //   const shopper = dataProvider.shopper('france');

  //   // Execution
  //   await productPage.addToCart({ slug: 'push-it-messenger-bag', quantity: 1 });
  //   await checkoutPage.goto();
  //   await checkoutPage.fillForm(shopper);
  //   await checkoutPage.openAndCloseEducationalPopup({ product: 'pp3' });
  //   await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
  //   await checkoutPage.waitForOrderSuccess();
  //   await checkoutPage.expectOrderHasTheCorrectMerchantId('FR', helper, dataProvider);
  // });

  // test('Make a 🍊 payment with "Review test approve" names', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
  //   // Setup
  //   const { dummy_config } = helper.webhooks;
  //   await helper.executeWebhook({ webhook: dummy_config }); // Setup for physical products.
  //   const shopper = dataProvider.shopper('approve');

  //   // Execution
  //   await productPage.addToCart({ slug: 'push-it-messenger-bag', quantity: 1 });
  //   await checkoutPage.goto();
  //   await checkoutPage.fillForm(shopper);
  //   await checkoutPage.placeOrder({ ...shopper, product: 'i1' });
  //   await checkoutPage.waitForOrderSuccess();
  //   await checkoutPage.expectOrderHasTheCorrectMerchantId('ES', helper, dataProvider);
  //   await checkoutPage.expectOrderChangeTo(backOffice, { fromStatus: 'Pending Payment', toStatus: 'Processing' });
  // });

  // test('Make a 🍊 payment with "Review test cancel" names', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
  //   // Setup
  //   const { dummy_config } = helper.webhooks;
  //   await helper.executeWebhook({ webhook: dummy_config }); // Setup for physical products.
  //   const shopper = dataProvider.shopper('cancel');

  //   // Execution
  //   await productPage.addToCart({ slug: 'push-it-messenger-bag', quantity: 1 });
  //   await checkoutPage.goto();
  //   await checkoutPage.fillForm(shopper);
  //   await checkoutPage.placeOrder({ ...shopper, product: 'i1' });
  //   await checkoutPage.waitForOrderSuccess();
  //   await checkoutPage.expectOrderHasTheCorrectMerchantId('ES', helper, dataProvider);
  //   await checkoutPage.expectOrderChangeTo(backOffice, { fromStatus: 'Pending Payment', toStatus: 'Canceled' });
  // });
});
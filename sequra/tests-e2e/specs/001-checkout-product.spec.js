import SeQuraHelper from '../fixtures/SeQuraHelper';
import { test, expect } from '../fixtures/test';

test.describe('Product checkout', () => {

  test('All available seQura products appear in the checkout', async ({ productPage, checkoutPage, request }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    const helper = new SeQuraHelper(request, expect);
    for (const version of ['classic', 'blocks']) {
      await helper.executeWebhook({ webhook: helper.webhooks.CHECKOUT_VERSION, args: [{ name: 'version', value: version }] });
      await checkoutPage.goto();
      await checkoutPage.expectPaymentMethodsBeingReloaded();
      await checkoutPage.expectFp1ToBeVisible();
      await checkoutPage.expectI1ToBeVisible();
      await checkoutPage.expectSp1ToBeVisible();
      await checkoutPage.expectPp3ToBeVisible();
      await checkoutPage.expectPp3DecombinedToBeVisible();
    }
  });

  test('Make a successful payment using any shopper name', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.fillWithNonSpecialShopperName({});
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingFp1({});
    await checkoutPage.waitForOrderSuccess();
  });

  test('Make a 🍊 payment with "Review test approve" names', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.fillWithReviewTest({});
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingI1({});
    await checkoutPage.waitForOrderOnHold();
    await checkoutPage.expectOrderChangeTo({ toStatus: 'wc-processing' });
  });

  test('Make a 🍊 payment with "Review test cancel" names', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.fillWithReviewTest({ shopper: 'cancel' });
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingI1({});
    await checkoutPage.waitForOrderOnHold();
    await checkoutPage.expectOrderChangeTo({ toStatus: 'wc-cancelled' });
  });

  test('Make a payment attempt forcing a failure by changing the order payload amounts so it differs with the approved one.', async ({ page, productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillWithNonSpecialShopperName({});
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingFp1({ forceFailure: true });
    await checkoutPage.waitForOrderFailure();
  });
});
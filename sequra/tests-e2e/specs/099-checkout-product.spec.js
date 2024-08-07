import { test } from '../fixtures/test';

test.describe.configure({ mode: 'parallel' });
test.describe('Product checkout', () => {

  test('All available seQura products appear in the checkout', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.expectFp1ToBeVisible();
    await checkoutPage.expectI1ToBeVisible();
    await checkoutPage.expectSp1ToBeVisible();
    await checkoutPage.expectPp3ToBeVisible();
    await checkoutPage.expectPp3DecombinedToBeVisible();
  });

  test('Make a successful payment using any shopper name', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.expectFp1ToBeVisible();
    await checkoutPage.fillWithNonSpecialShopperName({});
    await checkoutPage.placeOrderUsingFp1({});
    await checkoutPage.waitForOrderSuccess();
  });

  test('Make a 🍊 payment with "Review test approve" names', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.expectI1ToBeVisible();
    await checkoutPage.fillWithReviewTest({});
    await checkoutPage.placeOrderUsingI1({});
    await checkoutPage.waitForOrderOnHold();
    await checkoutPage.expectOrderChangeTo({ toStatus: 'wc-processing' });
  });

  test('Make a 🍊 payment with "Review test cancel" names', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.expectI1ToBeVisible();
    await checkoutPage.fillWithReviewTest({ shopper: 'cancel' });
    await checkoutPage.placeOrderUsingI1({});
    await checkoutPage.waitForOrderOnHold();
    await checkoutPage.expectOrderChangeTo({ toStatus: 'wc-cancelled' });
  });

  test('Make a payment attempt forcing a failure by changing the order payload amounts so it differs with the approved one.', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForPhysicalProducts();
    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

    await checkoutPage.goto();
    await checkoutPage.expectFp1ToBeVisible();
    await checkoutPage.fillWithNonSpecialShopperName({});
    await checkoutPage.placeOrderUsingFp1({ forceFailure: true });
    await checkoutPage.waitForOrderFailure();
  });
});
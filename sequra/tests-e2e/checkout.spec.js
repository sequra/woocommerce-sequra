import { test, expect } from './fixtures';

test.beforeEach('Restore state', async ({ page }) => {
  // TODO: restore the database to a known state
  // console.log('Restore state');
});

test.describe('Checkout', () => {

  test('All available seQura products appear in the checkout', async ({ page, cart, checkout }) => {
    await cart.add({ page, product: 'sunglasses', quantity: 1 });
    await checkout.open({ page });
    await checkout.expectFp1ToBeVisible({ page });
    await checkout.expectI1ToBeVisible({ page });
    await checkout.expectSp1ToBeVisible({ page });
    await checkout.expectPp3ToBeVisible({ page });
    await checkout.expectPp3DecombinedToBeVisible({ page });
  });

  test('Make a successful payment using any shopper name', async ({ page, cart, checkout }) => {
    await cart.add({ page, product: 'sunglasses', quantity: 1 });
    await checkout.open({ page });
    await checkout.expectFp1ToBeVisible({ page });
    await checkout.fillWithNonSpecialShopperName({ page });
    await checkout.placeOrderUsingFp1({ page });
    await checkout.waitForOrderSuccess({ page });
  });

  test.only('Make a 🍊 payment with "Review test approve" names', async ({ page, cart, checkout }) => {
    await cart.add({ page, product: 'sunglasses', quantity: 1 });
    await checkout.open({ page });
    await checkout.expectI1ToBeVisible({ page });
    await checkout.fillWithReviewTestApprove({ page });
    await checkout.placeOrderUsingI1AndReviewTestApprove({ page });
    await checkout.waitForOrderOnHold({ page });
    await checkout.expectOrderChangeToProcessing({ page });
    await page.pause();
  });
});
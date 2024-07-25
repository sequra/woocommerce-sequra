import { test } from '../fixtures/test';

test.describe.configure({ mode: 'parallel' });
test.describe('Service checkout', () => {

  test('Make a successful payment using any shopper name', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForServices();
    await productPage.addToCart({ slug: 'album', quantity: 1 });
    
    await checkoutPage.goto();
    await checkoutPage.expectPp3ToBeVisible();
    await checkoutPage.fillWithNonSpecialShopperName({ fieldGroup: 'billing' });
    await checkoutPage.placeOrderUsingPp3({});
    await checkoutPage.waitForOrderSuccess();
  });

});
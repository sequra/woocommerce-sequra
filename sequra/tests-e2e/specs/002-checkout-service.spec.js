import { test } from '../fixtures/test';

test.describe('Service checkout', () => {

  test('Make a successful payment using any shopper name', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForServices();
    await productPage.addToCart({ slug: 'album', quantity: 1 });
    
    await checkoutPage.goto();
    await checkoutPage.fillWithNonSpecialShopperName({ fieldGroup: 'billing' });
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingPp3({});
    await checkoutPage.waitForOrderSuccess();
  });

});
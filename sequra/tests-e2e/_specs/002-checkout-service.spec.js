import { test } from '../../fixtures/test';

test.describe('Service checkout', () => {

  test('Order of virtual product is set as processing after checkout', async ({ productPage, checkoutPage, wpAdmin }) => {
    await checkoutPage.setupForServices();
    await productPage.addToCart({ slug: 'album', quantity: 1 });
    
    await checkoutPage.goto();
    await checkoutPage.fillWithNonSpecialShopperName({ fieldGroup: 'billing' });
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingPp3({});
    await checkoutPage.waitForOrderSuccess();
    await checkoutPage.expectOrderHasStatus({ status: 'wc-processing' });
  });

  test('Order of virtual & downloadable product is set as completed after checkout', async ({ productPage, checkoutPage }) => {
    await checkoutPage.setupForServices();
    await productPage.addToCart({ slug: 'downloadable-album', quantity: 1 });
    
    await checkoutPage.goto();
    await checkoutPage.fillWithNonSpecialShopperName({ fieldGroup: 'billing' });
    await checkoutPage.expectPaymentMethodsBeingReloaded();
    await checkoutPage.placeOrderUsingPp3({});
    await checkoutPage.waitForOrderSuccess();
    await checkoutPage.expectOrderHasStatus({ status: 'wc-completed' });
  });

});
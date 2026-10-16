import { test, expect } from '../fixtures/test.mjs';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Checkout reload', () => {
  test('Reloading the payment page after paying the down payment keeps the order', async ({ page, helper, dataProvider, backOffice, productPage, checkoutPage }) => {
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields, toggle_delegated_selection } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config },
      { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] },
      { webhook: toggle_delegated_selection, args: [{ name: 'value', value: '0' }] }
    ]);
    const shopper = dataProvider.shopper('approve');

    // Once seQura confirms the order it sends the shopper to the return webhook, which
    // redirects to order-received. Cancelling that navigation (ERR_ABORTED keeps the
    // current page) pins the browser to the payment page, so the reload below always
    // re-renders /order-pay/ instead of the confirmation page.
    const returnWebhook = /[?&]wc-api=woocommerce_sequra_return(&|$)/;
    await page.route(returnWebhook, route => route.abort('aborted'));

    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await checkoutPage.goto();
    await checkoutPage.fillForm({ isShipping: true, ...shopper });
    await checkoutPage.placeOrder({ ...shopper, product: 'pp3' });
    // Reload as soon as the form closes, before the confirmation lands: that is when the
    // reload used to re-solicit the order and orphan the paid one.
    await expect(page.frameLocator('#sq-identification-pp3').locator('#mufasa-iframe')).toHaveCount(0, { timeout: 60000 });
    await expect(page).toHaveURL(/\/order-pay\/\d+\//);
    const orderNumber = page.url().match(/\/order-pay\/(\d+)\//)[1];

    await page.reload();
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/\/order-pay\/\d+\//);
    await page.unroute(returnWebhook);

    await backOffice.gotoOrderListing();
    await checkoutPage.waitForOrderStatus({ orderNumber, status: 'Processing', waitFor: 180 });
    await checkoutPage.expectOrderHasStatus({ orderNumber, status: 'Processing' });
  });
});

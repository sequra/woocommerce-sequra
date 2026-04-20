import { test, expect } from '../fixtures/test.mjs';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Delegated payment selection checkout', () => {

    /**
     * Helper: set up blocks checkout environment with delegated selection toggled on or off.
     */
    async function setupBlocksCheckout(helper, dataProvider, { delegated }) {
        const uiVersion = DataProvider.UI_BLOCKS;
        const theme = dataProvider.themeForUiVersion(uiVersion);
        const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields, toggle_delegated_selection } = helper.webhooks;
        await helper.executeWebhooksSequentially([
            { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
            { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
            { webhook: clear_config },
            { webhook: dummy_config },
            { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] },
            { webhook: toggle_delegated_selection, args: [{ name: 'value', value: delegated ? '1' : '0' }] },
        ]);
    }

    /**
     * Helper: set up classic checkout environment with delegated selection toggled on or off.
     */
    async function setupClassicCheckout(helper, dataProvider, { delegated }) {
        const uiVersion = DataProvider.UI_CLASSIC;
        const theme = dataProvider.themeForUiVersion(uiVersion);
        const { clear_config, dummy_config, checkout_version, set_theme, remove_address_fields, toggle_delegated_selection } = helper.webhooks;
        await helper.executeWebhooksSequentially([
            { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
            { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
            { webhook: clear_config },
            { webhook: dummy_config },
            { webhook: remove_address_fields, args: [{ name: 'value', value: '0' }] },
            { webhook: toggle_delegated_selection, args: [{ name: 'value', value: delegated ? '1' : '0' }] },
        ]);
    }

    // (a) Filter off — blocks checkout multi-method flow is unaffected
    test('Blocks checkout shows seQura payment methods when delegated selection is off', async ({ helper, dataProvider, productPage, checkoutPage }) => {
        await setupBlocksCheckout(helper, dataProvider, { delegated: false });
        const shopper = dataProvider.shopper('spain');

        await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
        await checkoutPage.goto();
        await checkoutPage.fillForm({ isShipping: true, ...shopper });

        // Individual seQura payment-method radio buttons must be visible.
        await checkoutPage.expectAnyPaymentMethod({ available: true, timeout: 20000 });
    });

    // (b) Filter on — blocks checkout routes through tbs
    test('Blocks checkout completes with product=tbs when delegated selection is enabled', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
        await setupBlocksCheckout(helper, dataProvider, { delegated: true });
        const shopper = dataProvider.shopper('approve');

        await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
        await checkoutPage.goto();
        await checkoutPage.fillForm({ isShipping: true, ...shopper });

        await checkoutPage.expectNoInputPerSqProduct();
        await checkoutPage.expectVisibleSqLogoInPaymentOption();
        await checkoutPage.placeOrder({ ...shopper, product: 'tbs' });
        await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider);
        await checkoutPage.expectOrderChangeTo(backOffice, { fromStatus: 'On-hold', toStatus: 'Processing' });
    });

    // (c) Filter on — classic checkout routes through tbs
    test('Classic checkout completes with product=tbs when delegated selection is enabled', async ({ helper, dataProvider, backOffice, productPage, checkoutPage }) => {
        await setupClassicCheckout(helper, dataProvider, { delegated: true });
        const shopper = dataProvider.shopper('approve');

        await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
        await checkoutPage.goto();
        await checkoutPage.fillForm({ isShipping: false, ...shopper });

        // No per-method radio buttons in delegated mode — hidden input present instead.
        await checkoutPage.expectNoInputPerSqProduct();
        await checkoutPage.expectVisibleSqLogoInPaymentOption();
        await checkoutPage.placeOrder({ ...shopper, product: 'tbs' });
        await checkoutPage.expectOrderHasTheCorrectMerchantId(shopper.country, helper, dataProvider);
        await checkoutPage.expectOrderChangeTo(backOffice, { fromStatus: 'On-hold', toStatus: 'Processing' });
    });
});

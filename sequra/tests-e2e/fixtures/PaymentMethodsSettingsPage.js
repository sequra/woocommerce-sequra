import SettingsPage from './SettingsPage';
import { countries as dataCountries, merchant as dataMerchant } from './data';

export default class PaymentMethodsSettingsPage extends SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, baseURL, expect, request) {
        super(page, baseURL, 'payment-methods', expect, request);
    }

    async setup() {
        const webhooks = [
            this.helper.webhooks.DUMMY_CONFIG,
        ];

        for (const webhook of webhooks) {
            await this.helper.executeWebhook({ webhook });
        }
    }

    async expectAvailablePaymentMethodsAreVisible({ merchant = 'dummy', countries = ['ES', 'FR', 'PT', 'IT'] }) {
        const countryName = dataCountries.default[countries[0]];
        const countrySelectedLocator = this.page.locator('span.sqs--selected', { hasText: countryName })
        await this.expect(countrySelectedLocator, `The default country "${countryName}" is shown as selected`).toBeVisible();

        for (const country of countries) {
            const countryName = dataCountries.default[country];
            await this.page.locator('.sqp-dropdown-button').click();
            await this.page.locator('.sqp-dropdown-button + .sqp-dropdown-list .sqp-dropdown-list-item', { hasText: countryName }).click();
            await this.expect(this.page.locator('.sqp-dropdown-button > .sqs--selected', { hasText: countryName }), `The country "${countryName}" is shown as selected`).toBeVisible();

            await this.expectLoadingShowAndHide();

            for (const pm of dataMerchant[merchant].paymentMethods[country]) {
                await this.expect(this.page.locator('.sqp-payment-method-title', { hasText: pm }), `The payment method "${pm}" is visible`).toBeVisible();
            }

        }
    }
}
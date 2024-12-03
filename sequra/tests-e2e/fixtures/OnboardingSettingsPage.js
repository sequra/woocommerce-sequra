import SettingsPage from './SettingsPage';
import { countries as dataCountries, merchant as dataMerchant } from './data';

export default class OnboardingSettingsPage extends SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, baseURL, expect, request) {
        super(page, baseURL, 'onboarding-connect', expect, request);

        this.selector = {
            onboarding: {
                completedStepConnect: '.sqp-step.sqs--completed[href="#onboarding-connect"]',
                completedStepCountries: '.sqp-step.sqs--completed[href="#onboarding-countries"]',
            },
            ...this.selector
        }
    }

    async setup() {
        const webhooks = [
            this.helper.webhooks.CLEAR_CONFIG,
        ];

        for (const webhook of webhooks) {
            await this.helper.executeWebhook({ webhook });
        }
    }

    async fillConnectForm({ merchant = 'dummy_automated_tests', env = 'sandbox' }) {
        await this.page.locator(this.selector.env[env]).click();
        await this.page.locator(this.selector.username).fill(dataMerchant[merchant].username);
        await this.page.locator(this.selector.password).fill(dataMerchant[merchant].password);
        await this.page.locator(this.selector.primaryBtn).click();
        await this.page.waitForSelector(this.selector.onboarding.completedStepConnect, { timeout: 5000 });
    }

    async fillCountriesForm({ merchant = 'dummy_automated_tests', countries = ['ES'] }) {
        await this.page.locator(this.selector.multiSelect).click();
        await this.page.waitForSelector(this.selector.dropdownListItem, { timeout: 1000 });

        this.expect(countries.length, 'At least one country should be selected').toBeGreaterThan(0);

        for (const country of countries) {
            await this.page.locator(this.selector.dropdownListItem, { hasText: dataCountries.default[country] }).click();
        }
        for (const country of countries) {
            const merchantRefInput = `[name="country_${country}"]`;
            await this.page.locator(merchantRefInput).click();
            await this.page.locator(merchantRefInput).fill(dataMerchant[merchant].ref[country]);
        }

        await this.page.locator(this.selector.primaryBtn).click();

        await this.page.waitForSelector(this.selector.onboarding.completedStepCountries, { timeout: 5000 });
    }

    async fillWidgetsForm({ merchant = 'dummy_automated_tests' }) {
        await this.page.locator(this.selector.yesOption).click();
        await this.page.waitForSelector(this.selector.assetsKey, { timeout: 1000 });
        await this.page.locator(this.selector.assetsKey).click();
        await this.page.locator(this.selector.assetsKey).fill(dataMerchant[merchant].assetsKey);
        await this.page.locator(this.selector.primaryBtn).click();
        await this.page.locator(this.selector.primaryBtn).click();
        await this.page.waitForSelector(this.selector.headerNavbar, { timeout: 5000 });
    }
}
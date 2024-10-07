import SettingsPage from './SettingsPage';
import { merchant as dataMerchant } from './data';

export default class ConnectionSettingsPage extends SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, baseURL, expect, request) {
        super(page, baseURL, 'settings-connection', expect, request);
        this.selector = {
            ...this.selector,
            modalConfirmButton: '#sq-modal .sq-button.sqt--primary'
        }
    }

    async setup() {
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.CLEAR_CONFIG });
    }

    /**
     * 
     * @param {object} options 
     * @param {boolean} options.locate Use 'input' to locate the checkbox input element, 'label' to locate the label element with the .sq-toggle class.
     */
    #envLiveLocator(options = { locate: 'input' }) {
        const selector = options.locate === 'input' ? '[type="radio"][value="live"]' : '.sq-radio-input:has([type="radio"][value="live"])';
        return this.page.locator(selector);
    }

    /**
     * 
     * @param {object} options 
     * @param {boolean} options.locate Use 'input' to locate the checkbox input element, 'label' to locate the label element with the .sq-toggle class.
     */
    #disconnectLocator() {
        return this.page.getByRole('button', { name: 'Disconnect' });
    }

    /**
     * 
     * @param {object} options 
     * @param {boolean} options.locate Use 'input' to locate the checkbox input element, 'label' to locate the label element with the .sq-toggle class.
     */
    #envSandboxLocator(options = { locate: 'input' }) {
        const selector = options.locate === 'input' ? '[type="radio"][value="sandbox"]' : '.sq-radio-input:has([type="radio"][value="sandbox"])';
        return this.page.locator(selector);
    }

    async confirmModal() {
        const confirmBtnLocator = this.page.locator(this.selector.modalConfirmButton);
        await confirmBtnLocator.waitFor({ state: 'visible' });
        await confirmBtnLocator.click();
    }

    async disconnect() {
        await this.#disconnectLocator().click();
        await this.confirmModal();
        await this.page.waitForURL(/#onboarding-connect/);
    }

    async fill({ username, password, env }) {
        const envLocator = env === 'sandbox' ? this.#envSandboxLocator({ locate: 'label' }) : this.#envLiveLocator({ locate: 'label' });
        await envLocator.click();
        await this.page.locator(this.selector.username).fill(username);
        await this.page.locator(this.selector.password).fill(password);
    }

    async expectConfigurationMatches({ username, password, env }) {
        await this.expect(this.page.locator(this.selector.username), 'Username should be ' + username).toHaveValue(username);
        await this.expect(this.page.locator(this.selector.password), 'Password should be ' + password).toHaveValue(password);
        const envLocator = env === 'sandbox' ? this.#envSandboxLocator() : this.#envLiveLocator();
        await this.expect(envLocator, 'Environment should be ' + env).toBeChecked();
    }

    getDummyConnectionOptions(environment = 'sandbox') {
        return { ...dataMerchant.dummy, env: environment };
    }

    getDummyServicesConnectionOptions(environment = 'sandbox') {
        return { ...dataMerchant.dummyServices, env: environment };
    }

    async expectToHaveValues(connectionOptions) {
        const { username, password, env } = connectionOptions;
        const envLocator = env === 'sandbox' ? this.#envSandboxLocator() : this.#envLiveLocator();
        await this.expect(envLocator, 'Environment should be ' + env).toBeChecked();
        await this.expect(this.page.locator(this.selector.username), 'Username should be ' + username).toHaveValue(username);
        await this.expect(this.page.locator(this.selector.password), 'Password should be ' + password).toHaveValue(password);
    }
}
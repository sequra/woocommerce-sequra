import SettingsPage from './SettingsPage';
// import { countries as dataCountries, merchant as dataMerchant } from './data';

export default class GeneralSettingsPage extends SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, baseURL, expect, request) {
        super(page, baseURL, 'settings-general', expect, request);

        // name="allowedIPAddresses-selector"
        this.selector = {
            allowedIPAddresses: {
                itemsRemoveBtn: '.sq-multi-item-selector:has([name="allowedIPAddresses-selector"]) .sqp-selected-item > .sqp-remove-button',
                // hiddenInput: '.sq-multi-item-selector [name="allowedIPAddresses-selector"]',
                input: '[name="allowedIPAddresses-selector"] + .sq-multi-input',
                hiddenInput: '[name="allowedIPAddresses-selector"]',
            },
            ...this.selector
        }
    }

    async setup() {
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.CLEAR_CONFIG });
    }

    async expectAllowedIPAddressesToBeEmpty() {
        await this.expect(this.page.locator(this.selector.allowedIPAddresses.hiddenInput), '"Allowed IP addresses" should be empty').toHaveValue('');
    }

    async getPublicIP() {
        const response = await this.request.get('https://checkip.amazonaws.com/');
        const publicIP = await response.text();
        return publicIP.trim();
    }

    /**
     * @param {string[]} addresses
     */
    async fillAllowedIPAddresses(addresses) {
        const hiddenInputLocator = this.page.locator(this.selector.allowedIPAddresses.hiddenInput);
        const inputLocator = this.page.locator(this.selector.allowedIPAddresses.input);
        const itemsRmBtnLocator = this.page.locator(this.selector.allowedIPAddresses.itemsRemoveBtn);

        // Clear previous values
        while ((await itemsRmBtnLocator.count()) > 0) {
            await itemsRmBtnLocator.first().click();
        }

        await this.expectAllowedIPAddressesToBeEmpty();

        let value = '';
        for (const address of addresses) {
            await inputLocator.focus();
            await inputLocator.fill(address);
            await this.page.keyboard.press('Enter');
            value += '' === value ? address : ',' + address;
            await this.expect(hiddenInputLocator, '"Allowed IP addresses" should have value "' + value + '"').toHaveValue(value);
            await this.expect(inputLocator, '"Allowed IP addresses" input field should be empty').toHaveValue('');
        }
    }


}
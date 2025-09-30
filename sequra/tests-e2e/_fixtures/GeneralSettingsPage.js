import SettingsPage from './SettingsPage';
import { countries as dataCountries } from './data';

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
            selectedItemRemoveButton: '.sqp-selected-item > .sqp-remove-button',
            countriesMultiSelect: '.sq-multi-item-selector:has([name="countries-selector"])',
            dropdownListVisible: '.sqp-dropdown-list.sqs--show',
            ...this.selector
        }
    }

    async setup() {
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.CLEAR_CONFIG });
    }

    async expectAllowedIPAddressesToBeEmpty() {
        await this.expect(this.page.locator(this.selector.allowedIPAddresses.hiddenInput), '"Allowed IP addresses" should be empty').toHaveValue('');
    }

    async expectAllowedIPAddressesToHaveValue(value) {
        await this.expect(this.page.locator(this.selector.allowedIPAddresses.hiddenInput), '"Allowed IP addresses" should have value: ' + value).toHaveValue(value);
    }

    async expectExcludedProductsToBeEmpty() {
        await this.expect(this.#getExcludedProductsHiddenInputLocator(), '"Excluded products" should be empty').toHaveValue('');
    }

    /**
     * @returns {import('@playwright/test').Locator}
     */
    #getExcludedProductsLocator() {
        return this.page.locator('.sq-field-wrapper').filter({ hasText: 'Excluded products' }).first().locator('.sq-label-wrapper + div').first();
    }

    /**
     * @returns {import('@playwright/test').Locator}
     */
    #getExcludedProductsHiddenInputLocator() {
        return this.#getExcludedProductsLocator().locator('.sqp-hidden-input');
    }

    /**
     * @returns {import('@playwright/test').Locator}
     */
    #getExcludedCategoriesLocator() {
        return this.page.locator('.sq-field-wrapper').filter({ hasText: 'Excluded categories' }).first().locator('.sq-label-wrapper + div').first();
    }

    /**
     * @returns {import('@playwright/test').Locator}
     */
    #getExcludedCategoriesInputHiddenLocator() {
        return this.#getExcludedCategoriesLocator().locator('.sqp-hidden-input').first()
    }

    async expectExcludedCategoriesToBeEmpty() {
        await this.expect(this.#getExcludedCategoriesInputHiddenLocator(), '"Excluded Categories" should be empty').toHaveValue('');
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

    /**
     * @param {string[]} values
     */
    async fillExcludedProducts(values) {

        const containerLocator = this.#getExcludedProductsLocator();
        const inputLocator = containerLocator.locator('.sq-multi-input');
        const hiddenInputLocator = this.#getExcludedProductsHiddenInputLocator();

        // Clear previous values
        const itemsRmBtnLocator = containerLocator.locator(this.selector.selectedItemRemoveButton);
        while ((await itemsRmBtnLocator.count()) > 0) {
            await itemsRmBtnLocator.first().click();
        }

        await this.expectExcludedProductsToBeEmpty();

        let value = '';
        if (values) {
            for (const v of values) {
                await inputLocator.focus();
                await inputLocator.fill(v);
                await this.page.keyboard.press('Enter');
                value += '' === value ? v : ',' + v;
                await this.expect(hiddenInputLocator, '"Excluded products" should have value "' + value + '"').toHaveValue(value);
                await this.expect(inputLocator, '"Excluded products" input field should be empty').toHaveValue('');
            }
        }
    }

    /**
     * @param {string[]} excludedCategories
     */
    async selectExcludedCategories(excludedCategories) {
        const selectLocator = this.#getExcludedCategoriesLocator();

        // Clear previous values
        const itemsRmBtnLocator = selectLocator.locator(this.selector.selectedItemRemoveButton);

        while ((await itemsRmBtnLocator.count()) > 0) {
            await itemsRmBtnLocator.first().click();
        }

        await this.expectExcludedCategoriesToBeEmpty();

        if (excludedCategories) {
            await selectLocator.click();
            for (const category of excludedCategories) {
                const listItemLocator = selectLocator.getByText(category);
                await listItemLocator.waitFor({ state: 'visible' });
                await listItemLocator.click();
                await selectLocator.locator('.sqp-selected-item').filter({ hasText: category }).waitFor({ state: 'visible' });
            }
        }
    }

    /**
     * 
     * @param {object} options 
     * @param {boolean} options.locate Use 'input' to locate the checkbox input element, 'label' to locate the label element with the .sq-toggle class.
     */
    #enabledForServicesToggleLocator(options = { locate: 'input' }) {
        return this.page.getByRole('heading', { name: 'Enabled for services' }).locator(options.locate === 'label' ? '.sq-toggle' : '.sqp-toggle-input');
    }
    #allowFirstServicePaymentDelayToggleLocator(options = { locate: 'input' }) {
        return this.page.getByRole('heading', { name: 'Allow first service payment delay' }).locator(options.locate === 'label' ? '.sq-toggle' : '.sqp-toggle-input');
    }
    #allowRegistrationItemsToggleLocator(options = { locate: 'input' }) {
        return this.page.getByRole('heading', { name: 'Allow registration items' }).locator(options.locate === 'label' ? '.sq-toggle' : '.sqp-toggle-input');
    }
    #defaultServicesEndDateInputLocator() {
        return this.page.locator('.sq-text-input.sq-default-services-end-date');
    }

    /**
     * @param {boolean} value 
     */
    async expectEnabledForServicesToBe(value) {
        const enabledForServicesToggleLocator = this.#enabledForServicesToggleLocator();
        const allowFirstServicePaymentDelayToggleLocator = this.#allowFirstServicePaymentDelayToggleLocator({ locate: 'label' });
        const allowRegistrationItemsToggleLocator = this.#allowRegistrationItemsToggleLocator({ locate: 'label' });
        const defaultServicesEndDateInputLocator = this.#defaultServicesEndDateInputLocator();
        await this.expect(enabledForServicesToggleLocator, 'Enable for service toggle should be ' + (value ? 'ON' : 'OFF')).toBeChecked({ checked: value });
        await this.expect(allowFirstServicePaymentDelayToggleLocator, 'Allow first service payment delay toggle should be ' + (value ? 'visible' : 'hidden')).toBeVisible({ visible: value });
        await this.expect(allowRegistrationItemsToggleLocator, 'Allow registration items toggle should be ' + (value ? 'visible' : 'hidden')).toBeVisible({ visible: value });
        await this.expect(defaultServicesEndDateInputLocator, 'Default services end date input should be ' + (value ? 'visible' : 'hidden')).toBeVisible({ visible: value });
    }

    /**
     * @param {boolean} value 
     */
    async expectAllowFirstServicePaymentDelayToBe(value) {
        const locator = this.#allowFirstServicePaymentDelayToggleLocator();
        await this.expect(locator, 'Allow First Service Payment Delay toggle should be ' + (value ? 'ON' : 'OFF')).toBeChecked({ checked: value });
    }

    /**
     * @param {boolean} value 
     */
    async expectAllowRegistrationItemsToBe(value) {
        const locator = this.#allowRegistrationItemsToggleLocator();
        await this.expect(locator, 'Allow Registration Items toggle should be ' + (value ? 'ON' : 'OFF')).toBeChecked({ checked: value });
    }

    /**
     * @param {object} options 
     * @param {boolean} options.enabled
     */
    async enableEnabledForServices(options = { enabled: true }) {
        const { enabled } = options;
        const enabledForServicesToggleLocator = this.#enabledForServicesToggleLocator({ locate: 'label' });
        await this.expect(enabledForServicesToggleLocator.locator('.sqp-toggle-input'), 'Enable for service toggle should be ' + (enabled ? 'OFF' : 'ON')).toBeChecked({ checked: !enabled });
        await enabledForServicesToggleLocator.click();
        await this.expectEnabledForServicesToBe(enabled);
    }

    /**
     * @param {object} options 
     * @param {boolean} options.enabled 
     */
    async enableAllowFirstServicePaymentDelay(options = { enabled: true }) {
        const { enabled } = options;
        const locator = this.#allowFirstServicePaymentDelayToggleLocator({ locate: 'label' });
        await this.expect(locator.locator('.sqp-toggle-input'), 'Allow First Service Payment Delay toggle should be ' + (enabled ? 'OFF' : 'ON')).toBeChecked({ checked: !enabled });
        await locator.click();
    }

    /**
     * @param {object} options 
     * @param {boolean} options.enabled 
     */
    async enableAllowRegistrationItems(options = { enabled: true }) {
        const { enabled } = options;
        const locator = this.#allowRegistrationItemsToggleLocator({ locate: 'label' });
        await this.expect(locator.locator('.sqp-toggle-input'), 'Allow Registration Items toggle should be ' + (enabled ? 'OFF' : 'ON')).toBeChecked({ checked: !enabled });
        await locator.click();
    }

    async fillDefaultServicesEndDate(value) {
        const defaultServicesEndDateInputLocator = this.#defaultServicesEndDateInputLocator();
        await defaultServicesEndDateInputLocator.fill(value);
        await defaultServicesEndDateInputLocator.blur();
    }

    /**
     * @typedef {Object} CountryRef
     * @property {string} country
     * @property {string} ref
     */

    /**
     * 
     * @param {Array<CountryRef>} countryRefs 
     */
    async expectAvailableCountries(countryRefs) {
        let value = '';
        for (const countryRef of countryRefs) {
            value += '' === value ? countryRef.country : ',' + countryRef.country;

            const country = dataCountries.default[countryRef.country];
            const ref = countryRef.ref;

            const selectedItemLocator = this.page.locator('.sqp-selected-item').filter({ hasText: country });
            const inputLocator = this.page.locator(`[name="country_${countryRef.country}"]`);

            this.expect(selectedItemLocator, `Country "${country}" should show as selected`).toBeVisible();
            this.expect(inputLocator, `Country Ref input "${country}" should be visible`).toBeVisible();
            this.expect(inputLocator, `Country Ref input "${country}" should have value "${ref}"`).toHaveValue(ref);
        }

        this.expect(this.page.locator('[name="countries-selector"]'), `"countries-selector" input should have value "${value}"`).toHaveValue(value);
    }

    /**
    * 
    * @param {Array<CountryRef>} countryRefs 
    */
    async fillAvailableCountries(countryRefs) {
        const multiSelectLocator = this.page.locator(this.selector.countriesMultiSelect);
        const itemsRmBtnLocator = multiSelectLocator.locator(this.selector.selectedItemRemoveButton);

        // Clear previous values
        while ((await itemsRmBtnLocator.count()) > 0) {
            await itemsRmBtnLocator.first().click();
        }

        await this.expectAvailableCountries([]);

        await multiSelectLocator.click();
        await this.page.waitForSelector(this.selector.dropdownListVisible, { timeout: 1000 });
        for (const countryRef of countryRefs) {
            await this.page.locator(this.selector.dropdownListItem, { hasText: dataCountries.default[countryRef.country] }).click();
            await this.page.locator(`[name="country_${countryRef.country}"]`).fill(countryRef.ref);
        }
    }

}
import SettingsPage from './SettingsPage';

export default class WidgetSettingsPage extends SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, baseURL, expect, request) {
        super(page, baseURL, 'settings-widget', expect, request);

        this.selector = {
            ...this.selector,
            selForPrice: "[name=\"selForPrice\"]",
            selForAltPrice: "[name=\"selForAltPrice\"]",
            selForAltPriceTrigger: "[name=\"selForAltPriceTrigger\"]",
            selForDefaultLocation: "[name=\"selForDefaultLocation\"]",
            widgetStyles: "[name=\"widget-styles\"]",
            customLocations: {
                details: ".sq-locations-container details",
                add: ".sq-locations-container .sq-add",
                summary: "summary",
                removeBtn: ".sq-remove",

                // removeBtn: ".sq-locations-container details .sq-remove",
            }
        }
    }

    async setup(options = { widgets: true }) {
        const { widgets } = options;
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.DUMMY_CONFIG, args: [{ name: "widgets", value: widgets ? 1 : 0 }] });
    }

    #displayWidgetsLocator(input = false) {
        return this.page.getByRole('heading', { name: 'Display widget on product page' }).first().locator(input ? 'input' : 'span');
    }

    async #openDetails(details) {
        try {
            await this.expect(details.locator(this.selector.customLocations.removeBtn)).toBeHidden({ timeout: 1 });
            const summary = details.locator(this.selector.customLocations.summary);
            const box = await summary.boundingBox();
            await summary.click({ position: { x: box.width - 8, y: box.height / 2 } }); // click on the expand button.
        } catch {
        }
    }

    async fill({ enabled, widgetConfig, priceSel, altPriceSel, altPriceTriggerSel, locationSel, customLocations }) {
        let shouldChange = false;
        try {
            await this.expect(this.#displayWidgetsLocator(true)).toBeChecked({ checked: !enabled, timeout: 1 });
            shouldChange = true;
        } catch (e) {
            // Ignore, toggle is already in the desired state
        }
        if (shouldChange) {
            await this.#displayWidgetsLocator().click();
            await this.#displayWidgetsLocator().blur();
        }

        const widgetStylesLocator = this.page.locator(this.selector.widgetStyles);
        await widgetStylesLocator.fill('');
        await widgetStylesLocator.pressSequentially(widgetConfig);
        await widgetStylesLocator.blur();

        if (enabled) {
            const selForPrice = this.page.locator(this.selector.selForPrice);
            await selForPrice.fill('');
            await selForPrice.pressSequentially(priceSel);
            await selForPrice.blur();

            const selForAltPrice = this.page.locator(this.selector.selForAltPrice);
            await selForAltPrice.fill('');
            await selForAltPrice.pressSequentially(altPriceSel);
            await selForAltPrice.blur();

            const selForAltPriceTrigger = this.page.locator(this.selector.selForAltPriceTrigger);
            await selForAltPriceTrigger.fill('');
            await selForAltPriceTrigger.pressSequentially(altPriceTriggerSel);
            await selForAltPriceTrigger.blur();

            const selForDefaultLocation = this.page.locator(this.selector.selForDefaultLocation);
            await selForDefaultLocation.fill('');
            await selForDefaultLocation.pressSequentially(locationSel);
            await selForDefaultLocation.blur();

            // Remove custom locations if any to start clean
            const customLocationLocator = this.page.locator(this.selector.customLocations.details);
            while ((await customLocationLocator.count()) > 0) {
                const details = customLocationLocator.last();
                await this.#openDetails(details);
                await details.locator(this.selector.customLocations.removeBtn).click();
            }

            // Add custom locations
            for (const customLocation of customLocations) {
                await this.page.locator(this.selector.customLocations.add).click();
                const details = this.page.locator(this.selector.customLocations.details).last();
                details.locator('select').selectOption({ label: customLocation.paymentMethod });
                await this.#openDetails(details);
                if (!customLocation.display) {
                    const toggle = details.locator('.sq-toggle');
                    await toggle.click();
                    await toggle.blur();
                }
                const locationSel = details.locator('input[type="text"]');
                await locationSel.fill('');
                await locationSel.pressSequentially(customLocation.locationSel);
                await locationSel.blur();

                const textarea = details.locator('textarea');
                await textarea.fill('');
                await textarea.pressSequentially(customLocation.widgetConfig);
                await textarea.blur();
            }
        }
    }

    async expectConfigurationMatches({ enabled, widgetConfig, priceSel, altPriceSel, altPriceTriggerSel, locationSel, customLocations }) {

        await this.expect(this.#displayWidgetsLocator(true)).toBeChecked({ checked: enabled, timeout: 1 });
        await this.expect(this.page.locator(this.selector.widgetStyles)).toHaveValue(widgetConfig);

        if (enabled) {
            await this.expect(this.page.locator(this.selector.selForPrice)).toHaveValue(priceSel);
            await this.expect(this.page.locator(this.selector.selForAltPrice)).toHaveValue(altPriceSel);
            await this.expect(this.page.locator(this.selector.selForAltPriceTrigger)).toHaveValue(altPriceTriggerSel);
            await this.expect(this.page.locator(this.selector.selForDefaultLocation)).toHaveValue(locationSel);

            const customLocationLocator = this.page.locator(this.selector.customLocations.details);
            this.expect((await customLocationLocator.count())).toBe(customLocations.length);

            for (let i = 0; i < customLocations.length; i++) {

                const customLocation = customLocations[i];
                const details = customLocationLocator.nth(i);

                await this.expect(details.locator('select')).toHaveValue(customLocation.paymentMethod);
                await this.#openDetails(details);
                await this.expect(details.locator('.sq-toggle input')).toBeChecked({ checked: customLocation.display });
                await this.expect(details.locator('input[type="text"]')).toHaveValue(customLocation.locationSel);
                await this.expect(details.locator('textarea')).toHaveValue(customLocation.widgetConfig);
            }
        }
    }

    getDefaultSettings() {
        return {
            enabled: false,
            priceSel: ".summary .price>.amount,.summary .price ins .amount",
            altPriceSel: ".woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount",
            altPriceTriggerSel: ".variations",
            locationSel: ".summary>.price",
            widgetConfig: '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
            customLocations: []
        }
    }
}
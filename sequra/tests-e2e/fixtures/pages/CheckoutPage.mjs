import { BackOffice, CheckoutPage as BaseCheckoutPage } from "playwright-fixture-for-plugins";
// TODO: pending review
/**
 * Checkout page
 */
export default class CheckoutPage extends BaseCheckoutPage {

    /**
    * Init the locators with the locators available
    * 
    * @returns {Object}
    */
    initLocators() {
        return {
            ...super.initLocators(),
            // loader: () => this.page.locator('.loading-mask', { state: 'visible' }),
            prefixedAddressField: (isShipping = false, field) => {
                const prefix = isShipping ? 'shipping' : 'billing';
                return this.page.locator(`#${prefix}_${field},#${prefix}-${field}`);
            },
            email: () => this.page.locator('#billing_email,#email'),
            firstName: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'first_name'),
            lastName: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'last_name'),
            address1: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'address_1'),
            country: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'country'),
            state: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'state'),
            city: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'city'),
            postcode: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'postcode'),
            phone: (isShipping = false) => this.locators.prefixedAddressField(isShipping, 'phone'),
            // flatRateShipping: () => this.page.locator('[value="flatrate_flatrate"]'),
            // continueButton: () => this.page.locator('.action.continue'),
            submitCheckout: () => this.page.locator('.wc-block-components-checkout-place-order-button:not([style="pointer-events: none;"])'),
            // TODO
            orderRowStatus: orderNumber => this.page.locator(`.data-row:has(td:has-text("${orderNumber}")) td:nth-child(9)`),
            orderNumber: () => this.page.locator('.wc-block-order-confirmation-summary-list-item:first-child .wc-block-order-confirmation-summary-list-item__value')
        };
    }

    /**
    * Provide the checkout URL
    * @param {Object} options
    * @returns {string} The checkout URL
    */
    checkoutUrl(options = {}) {
        return `${this.baseURL}/checkout/`;
    }

    /**
     * Fill the checkout page's form
     * @param {Object} options
     * @param {string} options.email Email
     * @param {string} options.firstName First name
     * @param {string} options.lastName Last name
     * @param {string} options.address1 Address first line
     * @param {string} options.country Typically a 2-letter ISO country code
     * @param {string} options.state Name of the state
     * @param {string} options.city Name of the city
     * @param {string} options.postcode Postcode
     * @param {string} options.phone Phone number
     * @param {boolean} options.isShipping Lookup shipping fields instead of billing
     * @returns {Promise<void>}
     */
    async fillForm(options) {
        const { email, firstName, lastName, address1, country, state, city, postcode, phone, isShipping } = { isShipping: false, ...options };
        await this.locators.email().fill(email);
        await this.locators.firstName(isShipping).fill(firstName);
        await this.locators.lastName(isShipping).fill(lastName);
        await this.locators.address1(isShipping).fill(address1);
        await this.locators.country(isShipping).selectOption(country);
        // State field might not exist in some countries.
        if (await this.locators.state(isShipping).count()) {
            await this.locators.state(isShipping).selectOption({ label: state });
        }
        await this.locators.city(isShipping).fill(city);
        await this.locators.postcode(isShipping).fill(postcode);
        await this.locators.phone(isShipping).fill(phone);
    }

    /**
    * Provide the locator to input the payment method
    * @param {Object} options
    * @param {string} options.product seQura product (i1, pp3, etc)
    * @param {boolean} options.checked Whether the payment method should be checked
    * @returns {import("@playwright/test").Locator}
    */
    paymentMethodInputLocator(options) {
        return this.page.locator(`.sequra-payment-method:has(.sequra_more_info[data-product="${options.product}"]) > .sequra-payment-method__input${options.checked ? ':checked' : ''}`);
    }

    /**
     * Provide the locator to input the payment method
     * @param {Object} options
     * @param {string} options.product seQura product (i1, pp3, etc)
     * @param {string} options.title Payment method title as it appears in the UI
     * @returns {import("@playwright/test").Locator}
     */
    paymentMethodTitleLocator(options) {
        return this.page.locator(`.sequra-payment-method > label:has(.sequra_more_info[data-product="${options.product}"])`);
    }

    /**
     * Provide the locator seQura payment methods
     * @param {Object} options
     * @returns {import("@playwright/test").Locator}
     */
    paymentMethodsLocator(options) {
        // TODO
        return this.page.locator('[id^="sequra_"]');
    }

    /**
    * Select the payment method and place the order
    * @param {Object} options 
    * @param {string} options.product seQura product (i1, pp3, etc)
    * @param {string} options.dateOfBirth Date of birth
    * @param {string} options.nin National identification number
    * @param {string[]} options.otp Digits of the OTP
    */
    async placeOrder(options) {
        await this.locators.paymentMethodInput({ ...options, checked: false }).click();
        // await this.#waitForFinishLoading();
        await this.locators.submitCheckout().click();
        // await this.#waitForFinishLoading();
        // Fill checkout form.
        switch (options.product) {
            case 'i1':
                await this.fillI1CheckoutForm(options);
                break;
            case 'pp3':
                await this.fillPp3CheckoutForm(options);
                break;
            case 'sp1':
                await this.fillSp1CheckoutForm(options);
                break;
            default:
                throw new Error(`Unknown product ${options.product}`);
        }
    }

    /**
     * Provide the locator for the moreInfo tag 
     * 
     * @param {Object} options
     * @param {string} options.product seQura product (i1, pp3, etc)
     * @returns {import("@playwright/test").Locator}
     */
    moreInfoLinkLocator(options) {
        return this.page.locator(`.sequra_more_info[data-product="${options.product}"]`);
    }

    /**
    * Define the expected behavior after placing an order
    * @param {Object} options
    * @param {string} options.expectedMessage The expected message to be shown
    */
    async waitForOrderSuccess(options) {
        await this.page.waitForURL(/\/checkout\/order-received\//, { timeout: 30000 });
        if (options?.expectedMessage) {
            await this.expect(this.page.getByText(options.expectedMessage)).toBeVisible();
        }
    }

    /**
     * Read the order number from the success page
     * 
     * @returns {Promise<string>}
     */
    async getOrderNumber() {
        // TODO
        return await this.locators.orderNumber().textContent();
    }

    /**
    * Expects the order to have the expected status
    * @param {Object} options 
    * @param {string} options.orderNumber The order number
    * @param {string} options.status The expected status
    * @returns {Promise<void>}
    */
    async expectOrderHasStatus(options) {
        const { orderNumber, status } = options;
        await this.expect(this.locators.orderRowStatus(orderNumber)).toHaveText(status);
    }

    /**
    * The timeout to wait before retrying to check the order status
    * @param {Object} options 
    * @returns {int}
    */
    getOrderStatusTimeoutInMs(options) {
        return 0;
    }

    /**
    * Check if the order changes to the expected state
    * @param {BackOffice} backOffice
    * @param {Object} options
    * @param {string} options.toStatus The expected status
    * @param {string} options.fromStatus The initial status. Optional
    * @param {int} options.waitFor The maximum amount of seconds to wait for the order status to change
    */
    async expectOrderChangeTo(backOffice, options) {
        const { toStatus, fromStatus = null, waitFor = 60 } = options;
        const orderNumber = await this.getOrderNumber();
        await backOffice.gotoOrderListing();
        if (fromStatus) {
            await this.waitForOrderStatus({ orderNumber, status: fromStatus, waitFor: 10 });
        }
        await this.waitForOrderStatus({ orderNumber, status: toStatus, waitFor });
    }

    /**
     * Expect payment methods being reloaded (spinner appears and disappears) multiple times if needed
     * 
     * @returns {Promise<void>}
     */
    async expectPaymentMethodsBeingReloaded() {
        while (true) {
            try {
                await this.page.waitForSelector('.sq-loader', { state: 'visible', timeout: 5000 });
                await this.page.waitForSelector('.sq-loader', { state: 'detached', timeout: 5000 });
            } catch (err) {
                break;
            }
        }
    }
}
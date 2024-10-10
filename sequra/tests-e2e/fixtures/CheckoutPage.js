import { shopper as dataShopper, sqProduct } from './data';
import SeQuraHelper from './SeQuraHelper';
import WpAdmin from './WpAdmin';
export default class CheckoutPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Expect} expect
     * @param {import('@playwright/test').Request} request
     */
    constructor(page, baseURL, expect, request) {
        this.page = page;
        this.expect = expect;
        this.request = request;
        this.wpAdmin = new WpAdmin(page, baseURL, expect);
        this.helper = new SeQuraHelper(request, expect);

        this.selector = {
            email: '#email',

            shipping: {
                // !Since Woo 9.2.0 this now is a native select instead of an input
                // country: '#shipping-country .components-combobox-control__input',
                // state: '#shipping-state .components-combobox-control__input',
                country: '#shipping-country',
                state: '#shipping-state',
                firstName: '#shipping-first_name',
                lastName: '#shipping-last_name',
                address1: '#shipping-address_1',
                postcode: '#shipping-postcode',
                city: '#shipping-city',
                phone: '#shipping-phone'
            },
            billing: {
                // !Since Woo 9.2.0 this now is a native select instead of an input
                // country: '#billing-country .components-combobox-control__input',
                // state: '#billing-state .components-combobox-control__input',
                country: '#billing-country',
                state: '#billing-state',
                firstName: '#billing-first_name',
                lastName: '#billing-last_name',
                address1: '#billing-address_1',
                postcode: '#billing-postcode',
                city: '#billing-city',
                phone: '#billing-phone'
            },

            paymentMethodFp1: '.sequra-payment-method:has([alt="Paga con tarjeta"]) [name="sequra_payment_method_data"]',
            paymentMethodI1: '.sequra-payment-method:has([alt="Paga Después"]) [name="sequra_payment_method_data"]',
            paymentMethodPp3: '.sequra-payment-method:has([alt="Paga Fraccionado"]) [name="sequra_payment_method_data"]',
            placeOrder: '.wc-block-components-checkout-place-order-button:not([style="pointer-events: none;"])',

            sqPaymentMethodName: '.sequra-payment-method__name',
            sqPaymentMethod: '.sequra-payment-method',

            sqIframeFp1: '#sq-identification-fp1',
            sqIframeMufasa: '#mufasa-iframe',
            sqCCNumber: '#cc-number',
            sqCCExp: '#cc-exp',
            sqCCCsc: '#cc-csc',

            sqIframeI1: '#sq-identification-i1',
            sqI1GivenNames: '[name="given_names"]',
            sqI1Surnames: '[name="surnames"]',
            sqDateOfBirth: '[name="date_of_birth"]',
            sqNin: '[name="nin"]',
            sqI1MobilePhone: '[name="mobile_phone"]',
            sqAcceptPrivacyPolicy: '[for="sequra_privacy_policy_accepted"]',
            sqAcceptServiceDuration: '[for="sequra_service_duration_accepted"]',
            sqIframeBtn: '.actions-section button:not([disabled])',

            sqIframePp3: '#sq-identification-pp3',
            sqPp3RegistrationFee: '.first-payment-sentence-confirmation',
            sqPp3CartTotal: '.credit-agreement-summary-details-row:nth-child(1) > span:nth-child(2)',

            sqOtp1: '[aria-label="Please enter OTP character 1"]',
            sqOtp2: '[aria-label="Please enter OTP character 2"]',
            sqOtp3: '[aria-label="Please enter OTP character 3"]',
            sqOtp4: '[aria-label="Please enter OTP character 4"]',
            sqOtp5: '[aria-label="Please enter OTP character 5"]',

            sqPayBtn: '.full-payment-btn-container button:not([disabled])',
            sqPayBtnAlt: '.payment-btn-container button:not([disabled])',
            adminOrderStatus: '#order_status',
        }
    }

    async goto() {
        await this.page.goto('./checkout/');
    }

    async setupForServices() {
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.DUMMY_SERVICE_CONFIG });
    }

    async setupForPhysicalProducts() {
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.DUMMY_CONFIG });
    }

    async fillWithReviewTest({ shopper = 'approve', fieldGroup = 'shipping' }) {
        const shopperData = dataShopper[shopper]

        await this.page.fill(this.selector.email, shopperData.email);

        // !Since Woo 9.2.0 this now is a native select instead of an input
        // await this.page.fill(this.selector[fieldGroup].country, shopperData.country);
        await this.page.locator(this.selector[fieldGroup].country).selectOption({ label: shopperData.country });

        await this.page.fill(this.selector[fieldGroup].firstName, shopperData.firstName);
        await this.page.fill(this.selector[fieldGroup].lastName, shopperData.lastName);
        await this.page.fill(this.selector[fieldGroup].address1, shopperData.address1);
        await this.page.fill(this.selector[fieldGroup].postcode, shopperData.postcode);
        await this.page.fill(this.selector[fieldGroup].city, shopperData.city);

        // !Since Woo 9.2.0 this now is a native select instead of an input
        // await this.page.fill(this.selector[fieldGroup].state, shopperData.state);
        await this.page.locator(this.selector[fieldGroup].state).selectOption({ label: shopperData.state });

        await this.page.fill(this.selector[fieldGroup].phone, shopperData.phone);
        await this.page.waitForSelector(this.selector.placeOrder);
    }

    async fillWithNonSpecialShopperName({ fieldGroup = 'shipping' }) {
        await this.page.fill(this.selector.email, dataShopper.nonSpecial.email);

        // !Since Woo 9.2.0 this now is a native select instead of an input
        // await this.page.fill(this.selector[fieldGroup].country, dataShopper.nonSpecial.country);
        await this.page.locator(this.selector[fieldGroup].country).selectOption({ label: dataShopper.nonSpecial.country });

        await this.page.fill(this.selector[fieldGroup].firstName, dataShopper.nonSpecial.firstName);
        await this.page.fill(this.selector[fieldGroup].lastName, dataShopper.nonSpecial.lastName);
        await this.page.fill(this.selector[fieldGroup].address1, dataShopper.nonSpecial.address1);
        await this.page.fill(this.selector[fieldGroup].postcode, dataShopper.nonSpecial.postcode);
        await this.page.fill(this.selector[fieldGroup].city, dataShopper.nonSpecial.city);

        // !Since Woo 9.2.0 this now is a native select instead of an input
        // await this.page.fill(this.selector[fieldGroup].state, dataShopper.nonSpecial.state);
        await this.page.locator(this.selector[fieldGroup].state).selectOption({ label: dataShopper.nonSpecial.state });

        await this.page.fill(this.selector[fieldGroup].phone, dataShopper.nonSpecial.phone);

        await this.page.waitForSelector(this.selector.placeOrder);
    }

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

    async placeOrderUsingFp1({ forceFailure = false }) {
        await this.page.click(this.selector.paymentMethodFp1);
        await this.page.click(this.selector.placeOrder);
        await this.page.waitForURL(/\/checkout\/order-pay\//, { timeout: 5000 });

        await this.page.waitForSelector(this.selector.sqIframeFp1, { state: 'attached', timeout: 10000 });
        const mainIframe = this.page.frameLocator(this.selector.sqIframeFp1);
        const innerIframe = mainIframe.frameLocator(this.selector.sqIframeMufasa);

        await innerIframe.locator(this.selector.sqCCNumber).waitFor({ state: 'attached', timeout: 10000 });

        if (forceFailure) {
            const url = new URL(this.page.url());
            // const orderId = url.searchParams.get('order-pay');
            const orderId = url.pathname.split('/order-pay/')[1].replace('/', '')

            this.helper.executeWebhook({
                webhook: this.helper.webhooks.FORCE_ORDER_FAILURE,
                args: [{ name: 'order_id', value: orderId }]
            });
        }

        await innerIframe.locator(this.selector.sqCCNumber).click();
        await innerIframe.locator(this.selector.sqCCNumber).pressSequentially(dataShopper.nonSpecial.creditCard.number, { delay: 100 });

        await innerIframe.locator(this.selector.sqCCExp).click();
        await innerIframe.locator(this.selector.sqCCExp).pressSequentially(dataShopper.nonSpecial.creditCard.exp, { delay: 100 });

        await innerIframe.locator(this.selector.sqCCCsc).click();
        await innerIframe.locator(this.selector.sqCCCsc).pressSequentially(dataShopper.nonSpecial.creditCard.cvc, { delay: 100 });

        await mainIframe.locator(this.selector.sqPayBtn).click();
    }

    async placeOrderUsingI1({ shopper = 'approve' }) {
        await this.page.click(this.selector.paymentMethodI1);
        await this.page.click(this.selector.placeOrder);
        await this.page.waitForURL(/\/checkout\/order-pay\//, { timeout: 5000 });

        await this.page.waitForSelector(this.selector.sqIframeI1, { state: 'attached', timeout: 10000 });
        const iframe = this.page.frameLocator(this.selector.sqIframeI1);

        const shopperData = dataShopper[shopper]

        // First name, last name, and mobile phone came already filled.
        await iframe.locator(this.selector.sqDateOfBirth).click();
        await iframe.locator(this.selector.sqDateOfBirth).pressSequentially(shopperData.dateOfBirth);
        await iframe.locator(this.selector.sqNin).click();
        await iframe.locator(this.selector.sqNin).pressSequentially(shopperData.dni);
        await iframe.locator(this.selector.sqAcceptPrivacyPolicy).click();
        await iframe.locator(this.selector.sqIframeBtn).click();

        await this.fillOtp({ iframe, shopper });
    }

    async placeOrderUsingPp3({ shopper = 'nonSpecial' }) {
        await this.page.click(this.selector.paymentMethodPp3);
        await this.page.click(this.selector.placeOrder);
        await this.page.waitForURL(/\/checkout\/order-pay\//, { timeout: 5000 });

        await this.page.waitForSelector(this.selector.sqIframePp3, { state: 'attached', timeout: 10000 });
        const iframe = this.page.frameLocator(this.selector.sqIframePp3);

        const cartTotal = iframe.locator(this.selector.sqPp3CartTotal)

        await cartTotal.waitFor({ state: 'attached', timeout: 10000 });
        await this.expect(cartTotal, 'The checkout popup should show the service price as the cart amount').toHaveText('50,00 €');
        await this.expect(iframe.locator(this.selector.sqPp3RegistrationFee), 'The checkout popup should show the registration amount').toHaveText('Confirma la compra pagando hoy el pago de registro de 15,90 €');

        await iframe.locator(this.selector.sqIframeBtn).click();

        const shopperData = dataShopper[shopper]
        // First name, last name, and mobile phone came already filled.
        await iframe.locator(this.selector.sqDateOfBirth).click();
        await iframe.locator(this.selector.sqDateOfBirth).pressSequentially(shopperData.dateOfBirth);
        await iframe.locator(this.selector.sqNin).click();
        await iframe.locator(this.selector.sqNin).pressSequentially(shopperData.dni);
        await iframe.locator(this.selector.sqAcceptPrivacyPolicy).click();
        await iframe.locator(this.selector.sqAcceptServiceDuration).click();
        await iframe.locator(this.selector.sqIframeBtn).click();

        await this.fillOtp({ iframe, shopper });

        // Set card details
        const newCCBtn = iframe.getByRole('button', { name: 'Nueva tarjeta' });
        await newCCBtn.waitFor({ state: 'attached', timeout: 10000 });
        await newCCBtn.click();

        const innerIframe = iframe.frameLocator(this.selector.sqIframeMufasa);
        await innerIframe.locator(this.selector.sqCCNumber).waitFor({ state: 'attached', timeout: 10000 });

        await innerIframe.locator(this.selector.sqCCNumber).click();
        await innerIframe.locator(this.selector.sqCCNumber).pressSequentially(dataShopper.nonSpecial.creditCard.number, { delay: 100 });

        await innerIframe.locator(this.selector.sqCCExp).click();
        await innerIframe.locator(this.selector.sqCCExp).pressSequentially(dataShopper.nonSpecial.creditCard.exp, { delay: 100 });

        await innerIframe.locator(this.selector.sqCCCsc).click();
        await innerIframe.locator(this.selector.sqCCCsc).pressSequentially(dataShopper.nonSpecial.creditCard.cvc, { delay: 100 });

        await iframe.locator(this.selector.sqPayBtnAlt).click();
    }

    async fillOtp({ iframe, shopper = 'approve' }) {
        const shopperData = dataShopper[shopper]

        await iframe.locator(this.selector.sqOtp1).waitFor({ state: 'attached', timeout: 10000 });
        await iframe.locator(this.selector.sqOtp1).pressSequentially(shopperData.otp[0]);
        await iframe.locator(this.selector.sqOtp2).pressSequentially(shopperData.otp[1]);
        await iframe.locator(this.selector.sqOtp3).pressSequentially(shopperData.otp[2]);
        await iframe.locator(this.selector.sqOtp4).pressSequentially(shopperData.otp[3]);
        await iframe.locator(this.selector.sqOtp5).pressSequentially(shopperData.otp[4]);

        await iframe.locator(this.selector.sqIframeBtn).click();
    }

    async waitForOrderSuccess() {
        await this.page.waitForURL(/\/checkout\/order-received\//, { timeout: 30000, waitUntil: 'commit' });
    }

    async waitForOrderOnHold() {
        await this.page.waitForURL(/\/checkout\/order-received\//, { timeout: 30000 });
        await this.expect(this.page.getByText('seQura is processing your request.')).toBeVisible();
    }

    async waitForOrderFailure() {
        await this.page.waitForSelector('.woocommerce-notices-wrapper .is-error', { timeout: 30000 });
        await this.expect(this.page.locator('#place_order')).toBeVisible();
    }

    /**
     * @param {Object} options
     * @param {string} options.toStatus The status to expect the order to change to. Use the wc- prefix
     * @param {string} options.fromStatus The status to expect the order to change from. Use the wc- prefix
     * @param {number} options.waitFor The maximum amount of seconds to wait for the order status to change
     */
    async expectOrderChangeTo({ toStatus, fromStatus = 'wc-on-hold', waitFor = 60 }) {
        const url = new URL(this.page.url());
        const orderId = url.pathname.split('/order-received/')[1].replace('/', '')
        this.wpAdmin.gotoOrder({ orderId });

        await this.#expectOrderEditPageHasStatus({ status: fromStatus, waitFor });
        await this.#expectOrderEditPageHasStatus({ status: toStatus, waitFor });
    }

    /**
    * @param {Object} options
    * @param {string} options.status The status to expect the order to change to. Use the wc- prefix
    * @param {number} options.waitFor The maximum amount of seconds to wait for the order status to change
    */
    async #expectOrderEditPageHasStatus({ status, waitFor = 60 }) {
        console.log(`Waiting for order has status "${status}" for ${waitFor} seconds...`);
        for (let i = 0; i < waitFor; i++) {
            try {
                await this.expect(this.page.locator(this.selector.adminOrderStatus)).toHaveValue(status);
                console.log(`Order status changed to "${status}" after ${i} seconds`);
                break;
            } catch (err) {
                if (i >= waitFor) {
                    console.log(`Timeout: after ${i} seconds the order status didn't change to "${status}" `);
                    throw err
                }
                await this.page.waitForTimeout(1000);
                await this.page.reload();
            }
        }
    }

    async expectAnyPaymentMethod({ available = true }) {
        const locator = this.page.locator(this.selector.sqPaymentMethod);
        if (available) {
            await this.expect(locator.first(), `"seQura payment methods should be available`).toBeVisible({ timeout: 10000 });
        } else {
            await this.expect(locator, `"seQura payment methods should not be available`).toHaveCount(0, { timeout: 10000 });
        }
    }

    async expectPaymentMethodToBeVisible({ methodName }) {
        try {
            // check if radio #radio-control-wc-payment-method-options-sequra exists and it is not checked and check it
            const locator = this.page.locator('#radio-control-wc-payment-method-options-sequra');
            await this.expect(locator).toBeChecked({ checked: false, timeout: 1000 });
            await locator.click();
        } catch (err) {
            console.log('Radio control not found');
        }
        await this.expect(this.page.locator(this.selector.sqPaymentMethodName, { hasText: methodName }), `"${methodName}" payment method should be visible`).toBeVisible({ timeout: 10000 });
    }

    async expectFp1ToBeVisible() {
        await this.expectPaymentMethodToBeVisible({ methodName: sqProduct.fp1.es.name });
    }

    async expectI1ToBeVisible() {
        await this.expectPaymentMethodToBeVisible({ methodName: sqProduct.i1.es.name });
    }

    async expectSp1ToBeVisible() {
        await this.expectPaymentMethodToBeVisible({ methodName: sqProduct.sp1.es.name });
    }

    async expectPp3ToBeVisible() {
        await this.expectPaymentMethodToBeVisible({ methodName: sqProduct.pp3.es.name });
    }

    async expectPp3DecombinedToBeVisible() {
        await this.expectPaymentMethodToBeVisible({ methodName: sqProduct.pp3Decombined.es.name });
    }
}
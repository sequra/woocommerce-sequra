import { test as baseTest, expect } from '@playwright/test';

class Cart {
    async add({ page, product, quantity }) {
        const url = `./?product=${product}`;
        await page.goto(url);

        await page.fill('[name="quantity"]', `${quantity || 1}`);
        await page.click('[name="add-to-cart"]');

        await page.waitForURL(url);
    }
}

class Checkout {

    constructor() {

        this.selector = {
            email: '#email',
            country: '#shipping-country .components-combobox-control__input',
            state: '#shipping-state .components-combobox-control__input',
            firstName: '#shipping-first_name',
            lastName: '#shipping-last_name',
            address1: '#shipping-address_1',
            postcode: '#shipping-postcode',
            city: '#shipping-city',
            phone: '#shipping-phone',

            paymentMethodCard: '.sequra-payment-method:has([alt="Paga con tarjeta"]) [name="sequra_payment_method_data"]',
            placeOrder: '.wc-block-components-checkout-place-order-button:not([style="pointer-events: none;"])',

            sqIframeFp1: '#sq-identification-fp1',
            sqIframeMufasa: '#mufasa-iframe',
            sqCCNumber: '#cc-number',
            sqCCExp: '#cc-exp',
            sqCCCsc: '#cc-csc',
            sqPayBtn: '.full-payment-btn-container button:not([disabled])',
        }

        this.name = {
            approve: 'Review Test Approve',
            cancel: 'Review Test Cancel',
            nonSpecial: 'Shopper Name',
        }

        this.address = {
            address1: "Carrer d'Alí Bei, 7",
            email: 'test@sequra.es',
            city: 'Barcelona',
            state: 'Barcelona',
            postcode: '08010',
            country: 'Spain',
            phone: '666666666',
        }

        this.creditCard = {
            number: '4716773077339777',
            expiration: '12/30',
            cvc: '123',
        }
    }

    async open({ page }) {
        await page.goto('./?page_id=7');
    }

    async fillWithReviewTestApprove({ page }) {
        await page.fill(this.selector.email, this.address.email);
        await page.fill(this.selector.country, this.address.country);
        await page.fill(this.selector.firstName, this.name.approve);
        await page.fill(this.selector.lastName, this.name.approve);
        await page.fill(this.selector.address1, this.address.address1);
        await page.fill(this.selector.postcode, this.address.postcode);
        await page.fill(this.selector.city, this.address.city);
        await page.fill(this.selector.state, this.address.state);
        await page.fill(this.selector.phone, this.address.phone);
        await page.waitForSelector(this.selector.placeOrder);
    }

    async placeOrderUsingCardPayment({ page }) {
        await page.click(this.selector.paymentMethodCard);
        await page.click(this.selector.placeOrder);
        await page.waitForURL(/page_id=7&order-pay=/);

        await page.waitForSelector(this.selector.sqIframeFp1, { state: 'attached', timeout: 10000 });
        const mainIframe = page.frameLocator(this.selector.sqIframeFp1);
        const innerIframe = mainIframe.frameLocator(this.selector.sqIframeMufasa);

        await innerIframe.locator(this.selector.sqCCNumber).waitFor({ state: 'attached', timeout: 10000 });

        await innerIframe.locator(this.selector.sqCCNumber).click();
        await innerIframe.locator(this.selector.sqCCNumber).type(this.creditCard.number);

        await innerIframe.locator(this.selector.sqCCExp).click();
        await innerIframe.locator(this.selector.sqCCExp).type(this.creditCard.expiration);

        await innerIframe.locator(this.selector.sqCCCsc).click();
        await innerIframe.locator(this.selector.sqCCCsc).type(this.creditCard.cvc);

        await mainIframe.locator(this.selector.sqPayBtn).click();
    }

    async waitForOrderSuccess({ page }) {
        await page.waitForURL(/page_id=7&order-received=/);
    }
}

export const test = baseTest.extend({
    cart: async ({ }, use) => await use(new Cart()),
    checkout: async ({ }, use) => await use(new Checkout())
});

export { expect };
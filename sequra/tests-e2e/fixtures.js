import { test as baseTest, expect } from '@playwright/test';

const login = async ({ page }) => {
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForNavigation();
}

class Cart {
    async add({ page, product, quantity }) {
        const url = `./?product=${product}`;
        await page.goto(url);

        await page.fill('[name="quantity"]', `${quantity || 1}`);
        await page.click('[name="add-to-cart"]');

        await page.waitForURL(url, { timeout: 5000, waitUntil: 'commit' });
    }
}

class Checkout {

    constructor() {

        this.selector = {
            email: '#email',

            shipping: {
                country: '#shipping-country .components-combobox-control__input',
                state: '#shipping-state .components-combobox-control__input',
                firstName: '#shipping-first_name',
                lastName: '#shipping-last_name',
                address1: '#shipping-address_1',
                postcode: '#shipping-postcode',
                city: '#shipping-city',
                phone: '#shipping-phone'
            },
            billing: {
                country: '#billing-country .components-combobox-control__input',
                state: '#billing-state .components-combobox-control__input',
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

        const defaultShopperData = {
            address1: "Carrer d'Alí Bei, 7",
            email: 'test@sequra.es',
            city: 'Barcelona',
            state: 'Barcelona',
            postcode: '08010',
            country: 'Spain',
            phone: '666666666',
            dateOfBirth: '01/01/2000',
            dni: '23232323T',
            creditCard: {
                number: '4716773077339777',
                exp: '12/30',
                cvc: '123',
            },
            otp: ['6', '6', '6', '6', '6']
        }

        this.shopper = {
            approve: {
                firstName: 'Review Test Approve',
                lastName: 'Review Test Approve',
                ...defaultShopperData

            },
            cancel: {
                firstName: 'Review Test Cancel',
                lastName: 'Review Test Cancel',
                ...defaultShopperData

            },
            nonSpecial: {
                firstName: 'Fulano',
                lastName: 'De Tal',
                ...defaultShopperData
            }
        }

        this.sqProduct = {
            fp1: {
                es: {
                    name: 'Paga con tarjeta',
                }
            },
            i1: {
                es: {
                    name: 'Paga Después',
                }
            },
            sp1: {
                es: {
                    name: 'Divide tu pago en 3',
                }
            },
            pp3: {
                es: {
                    name: 'Paga Fraccionado',
                }
            },
            pp3Decombined: {
                es: {
                    name: "€/mes (DECOMBINED)",
                }
            }
        }
    }

    async open({ page }) {
        await page.goto('./?page_id=7');
    }

    async fillWithReviewTest({ page, shopper = 'approve', fieldGroup = 'shipping' }) {
        const shopperData = this.shopper[shopper]

        await page.fill(this.selector.email, shopperData.email);
        await page.fill(this.selector[fieldGroup].country, shopperData.country);
        await page.fill(this.selector[fieldGroup].firstName, shopperData.firstName);
        await page.fill(this.selector[fieldGroup].lastName, shopperData.lastName);
        await page.fill(this.selector[fieldGroup].address1, shopperData.address1);
        await page.fill(this.selector[fieldGroup].postcode, shopperData.postcode);
        await page.fill(this.selector[fieldGroup].city, shopperData.city);
        await page.fill(this.selector[fieldGroup].state, shopperData.state);
        await page.fill(this.selector[fieldGroup].phone, shopperData.phone);
        await page.waitForSelector(this.selector.placeOrder);
    }

    async fillWithNonSpecialShopperName({ page, fieldGroup = 'shipping' }) {
        await page.fill(this.selector.email, this.shopper.nonSpecial.email);
        await page.fill(this.selector[fieldGroup].country, this.shopper.nonSpecial.country);
        await page.fill(this.selector[fieldGroup].firstName, this.shopper.nonSpecial.firstName);
        await page.fill(this.selector[fieldGroup].lastName, this.shopper.nonSpecial.lastName);
        await page.fill(this.selector[fieldGroup].address1, this.shopper.nonSpecial.address1);
        await page.fill(this.selector[fieldGroup].postcode, this.shopper.nonSpecial.postcode);
        await page.fill(this.selector[fieldGroup].city, this.shopper.nonSpecial.city);
        await page.fill(this.selector[fieldGroup].state, this.shopper.nonSpecial.state);
        await page.fill(this.selector[fieldGroup].phone, this.shopper.nonSpecial.phone);

        await page.waitForSelector(this.selector.placeOrder);
    }

    async placeOrderUsingFp1({ page, forceFailure = false, request = null }) {
        await page.click(this.selector.paymentMethodFp1);
        await page.click(this.selector.placeOrder);
        await page.waitForURL(/page_id=7&order-pay=/, { timeout: 5000 });

        await page.waitForSelector(this.selector.sqIframeFp1, { state: 'attached', timeout: 10000 });
        const mainIframe = page.frameLocator(this.selector.sqIframeFp1);
        const innerIframe = mainIframe.frameLocator(this.selector.sqIframeMufasa);

        await innerIframe.locator(this.selector.sqCCNumber).waitFor({ state: 'attached', timeout: 10000 });

        if (forceFailure) {
            const url = new URL(page.url());
            const orderId = url.searchParams.get('order-pay');

            const response = await request.post('./?sq-webhook=force_order_failure&order_id=' + orderId);
            expect(response.status(), 'The webhook response should have status 200').toBe(200);
            const json = await response.json();
            expect(json.success, 'The webhook response payload should have success:true').toBe(true);
        }

        await innerIframe.locator(this.selector.sqCCNumber).click();
        await innerIframe.locator(this.selector.sqCCNumber).type(this.shopper.nonSpecial.creditCard.number);

        await innerIframe.locator(this.selector.sqCCExp).click();
        await innerIframe.locator(this.selector.sqCCExp).type(this.shopper.nonSpecial.creditCard.exp);

        await innerIframe.locator(this.selector.sqCCCsc).click();
        await innerIframe.locator(this.selector.sqCCCsc).type(this.shopper.nonSpecial.creditCard.cvc);

        await mainIframe.locator(this.selector.sqPayBtn).click();
    }

    async placeOrderUsingI1({ page, shopper = 'approve' }) {
        await page.click(this.selector.paymentMethodI1);
        await page.click(this.selector.placeOrder);
        await page.waitForURL(/page_id=7&order-pay=/, { timeout: 5000 });

        await page.waitForSelector(this.selector.sqIframeI1, { state: 'attached', timeout: 10000 });
        const iframe = page.frameLocator(this.selector.sqIframeI1);

        const shopperData = this.shopper[shopper]

        // First name, last name, and mobile phone came already filled.
        await iframe.locator(this.selector.sqDateOfBirth).click();
        await iframe.locator(this.selector.sqDateOfBirth).fill(shopperData.dateOfBirth);
        await iframe.locator(this.selector.sqNin).click();
        await iframe.locator(this.selector.sqNin).fill(shopperData.dni);
        await iframe.locator(this.selector.sqAcceptPrivacyPolicy).click();
        await iframe.locator(this.selector.sqIframeBtn).click();

        await this.fillOtp({ iframe, shopper });
    }

    async placeOrderUsingPp3({ page, shopper = 'nonSpecial' }) {
        await page.click(this.selector.paymentMethodPp3);
        await page.click(this.selector.placeOrder);
        await page.waitForURL(/page_id=7&order-pay=/, { timeout: 5000 });

        await page.waitForSelector(this.selector.sqIframePp3, { state: 'attached', timeout: 10000 });
        const iframe = page.frameLocator(this.selector.sqIframePp3);

        const cartTotal = iframe.locator(this.selector.sqPp3CartTotal)

        await cartTotal.waitFor({ state: 'attached', timeout: 10000 });
        await expect(cartTotal, 'The checkout popup should show the service price as the cart amount').toHaveText('50,00 €');
        await expect(iframe.locator(this.selector.sqPp3RegistrationFee), 'The checkout popup should show the registration amount').toHaveText('Confirma la compra pagando hoy el pago de registro de 15,90 €');

        await iframe.locator(this.selector.sqIframeBtn).click();

        const shopperData = this.shopper[shopper]
        // First name, last name, and mobile phone came already filled.
        await iframe.locator(this.selector.sqDateOfBirth).click();
        await iframe.locator(this.selector.sqDateOfBirth).fill(shopperData.dateOfBirth);
        await iframe.locator(this.selector.sqNin).click();
        await iframe.locator(this.selector.sqNin).fill(shopperData.dni);
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
        await innerIframe.locator(this.selector.sqCCNumber).type(this.shopper.nonSpecial.creditCard.number);

        await innerIframe.locator(this.selector.sqCCExp).click();
        await innerIframe.locator(this.selector.sqCCExp).type(this.shopper.nonSpecial.creditCard.exp);

        await innerIframe.locator(this.selector.sqCCCsc).click();
        await innerIframe.locator(this.selector.sqCCCsc).type(this.shopper.nonSpecial.creditCard.cvc);

        await iframe.locator(this.selector.sqPayBtnAlt).click();
    }

    async fillOtp({ iframe, shopper = 'approve' }) {
        const shopperData = this.shopper[shopper]

        await iframe.locator(this.selector.sqOtp1).waitFor({ state: 'attached', timeout: 10000 });
        await iframe.locator(this.selector.sqOtp1).fill(shopperData.otp[0]);
        await iframe.locator(this.selector.sqOtp2).fill(shopperData.otp[1]);
        await iframe.locator(this.selector.sqOtp3).fill(shopperData.otp[2]);
        await iframe.locator(this.selector.sqOtp4).fill(shopperData.otp[3]);
        await iframe.locator(this.selector.sqOtp5).fill(shopperData.otp[4]);

        await iframe.locator(this.selector.sqIframeBtn).click();
    }

    async waitForOrderSuccess({ page }) {
        await page.waitForURL(/page_id=7&order-received=/, { timeout: 30000, waitUntil: 'commit' });
    }

    async waitForOrderOnHold({ page }) {
        await page.waitForURL(/page_id=7&order-received=/, { timeout: 30000 });
        await expect(page.getByText('seQura is processing your request.')).toBeVisible();
    }

    async waitForOrderFailure({ page }) {
        await page.waitForSelector('.woocommerce-notices-wrapper .is-error', { timeout: 30000 });
        await expect(page.locator('#place_order')).toBeVisible();
    }

    async gotoAdminOrder({ page }) {
        const url = new URL(page.url());
        const orderId = url.searchParams.get('order-received');
        await page.goto(`${url.origin}/wp-admin/post.php?post=${orderId}&action=edit`);

        login({ page });
    }

    async expectOrderChangeTo({ page, toStatus, fromStatus = 'wc-on-hold' }) {
        this.gotoAdminOrder({ page });

        const retries = 60;
        for (let i = 0; i < retries; i++) {
            try {
                await expect(page.locator(this.selector.adminOrderStatus)).toHaveValue(fromStatus);
                if (i < retries - 1) {
                    await page.waitForTimeout(1000);
                    await page.reload();
                }
            } catch (err) {
                // console.log('Order has changed from on-hold');
                break;
            }
        }

        await expect(page.locator(this.selector.adminOrderStatus), 'The order status should be: ' + toStatus).toHaveValue(toStatus);
    }

    async expectPaymentMethodToBeVisible({ page, methodName }) {
        await expect(page.locator(this.selector.sqPaymentMethodName, { hasText: methodName }), `"${methodName}" payment method should be visible`).toBeVisible({ timeout: 3000 });
    }

    async expectFp1ToBeVisible({ page }) {
        await this.expectPaymentMethodToBeVisible({ page, methodName: this.sqProduct.fp1.es.name });
    }

    async expectI1ToBeVisible({ page }) {
        await this.expectPaymentMethodToBeVisible({ page, methodName: this.sqProduct.i1.es.name });
    }

    async expectSp1ToBeVisible({ page }) {
        await this.expectPaymentMethodToBeVisible({ page, methodName: this.sqProduct.sp1.es.name });
    }

    async expectPp3ToBeVisible({ page }) {
        await this.expectPaymentMethodToBeVisible({ page, methodName: this.sqProduct.pp3.es.name });
    }

    async expectPp3DecombinedToBeVisible({ page }) {
        await this.expectPaymentMethodToBeVisible({ page, methodName: this.sqProduct.pp3Decombined.es.name });
    }
}

export const test = baseTest.extend({
    cart: async ({ }, use) => await use(new Cart()),
    checkout: async ({ }, use) => await use(new Checkout())
});

export { expect };
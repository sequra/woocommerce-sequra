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

            paymentMethodFp1: '.sequra-payment-method:has([alt="Paga con tarjeta"]) [name="sequra_payment_method_data"]',
            paymentMethodI1: '.sequra-payment-method:has([alt="Paga Después"]) [name="sequra_payment_method_data"]',
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
            sqI1DateOfBirth: '[name="date_of_birth"]',
            sqI1Nin: '[name="nin"]',
            sqI1MobilePhone: '[name="mobile_phone"]',
            // sqI1AcceptPrivacyPolicy: '#sequra_privacy_policy_accepted',
            sqI1AcceptPrivacyPolicy: '[for="sequra_privacy_policy_accepted"]',
            sqI1Btn: '.actions-section button:not([disabled])',
            sqOtp1: '[aria-label="Please enter OTP character 1"]',
            sqOtp2: '[aria-label="Please enter OTP character 2"]',
            sqOtp3: '[aria-label="Please enter OTP character 3"]',
            sqOtp4: '[aria-label="Please enter OTP character 4"]',
            sqOtp5: '[aria-label="Please enter OTP character 5"]',

            sqPayBtn: '.full-payment-btn-container button:not([disabled])',
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
            dateOfBirth: '23/12/2000',
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
                firstName: 'John',
                lastName: 'Doe',
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

    async fillWithReviewTestApprove({ page }) {
        await page.fill(this.selector.email, this.shopper.approve.email);
        await page.fill(this.selector.country, this.shopper.approve.country);
        await page.fill(this.selector.firstName, this.shopper.approve.firstName);
        await page.fill(this.selector.lastName, this.shopper.approve.lastName);
        await page.fill(this.selector.address1, this.shopper.approve.address1);
        await page.fill(this.selector.postcode, this.shopper.approve.postcode);
        await page.fill(this.selector.city, this.shopper.approve.city);
        await page.fill(this.selector.state, this.shopper.approve.state);
        await page.fill(this.selector.phone, this.shopper.approve.phone);
        await page.waitForSelector(this.selector.placeOrder);
    }

    async fillWithReviewTestCancel({ page }) {
        await page.fill(this.selector.email, this.shopper.cancel.email);
        await page.fill(this.selector.country, this.shopper.cancel.country);
        await page.fill(this.selector.firstName, this.shopper.cancel.firstName);
        await page.fill(this.selector.lastName, this.shopper.cancel.lastName);
        await page.fill(this.selector.address1, this.shopper.cancel.address1);
        await page.fill(this.selector.postcode, this.shopper.cancel.postcode);
        await page.fill(this.selector.city, this.shopper.cancel.city);
        await page.fill(this.selector.state, this.shopper.cancel.state);
        await page.fill(this.selector.phone, this.shopper.cancel.phone);
        await page.waitForSelector(this.selector.placeOrder);
    }

    async fillWithNonSpecialShopperName({ page }) {
        await page.fill(this.selector.email, this.shopper.nonSpecial.email);
        await page.fill(this.selector.country, this.shopper.nonSpecial.country);
        await page.fill(this.selector.firstName, this.shopper.nonSpecial.firstName);
        await page.fill(this.selector.lastName, this.shopper.nonSpecial.lastName);
        await page.fill(this.selector.address1, this.shopper.nonSpecial.address1);
        await page.fill(this.selector.postcode, this.shopper.nonSpecial.postcode);
        await page.fill(this.selector.city, this.shopper.nonSpecial.city);
        await page.fill(this.selector.state, this.shopper.nonSpecial.state);
        await page.fill(this.selector.phone, this.shopper.nonSpecial.phone);
        await page.waitForSelector(this.selector.placeOrder);
    }

    async placeOrderUsingFp1({ page }) {
        await page.click(this.selector.paymentMethodFp1);
        await page.click(this.selector.placeOrder);
        await page.waitForURL(/page_id=7&order-pay=/);

        await page.waitForSelector(this.selector.sqIframeFp1, { state: 'attached', timeout: 10000 });
        const mainIframe = page.frameLocator(this.selector.sqIframeFp1);
        const innerIframe = mainIframe.frameLocator(this.selector.sqIframeMufasa);

        await innerIframe.locator(this.selector.sqCCNumber).waitFor({ state: 'attached', timeout: 10000 });

        await innerIframe.locator(this.selector.sqCCNumber).click();
        await innerIframe.locator(this.selector.sqCCNumber).type(this.shopper.nonSpecial.creditCard.number);

        await innerIframe.locator(this.selector.sqCCExp).click();
        await innerIframe.locator(this.selector.sqCCExp).type(this.shopper.nonSpecial.creditCard.exp);

        await innerIframe.locator(this.selector.sqCCCsc).click();
        await innerIframe.locator(this.selector.sqCCCsc).type(this.shopper.nonSpecial.creditCard.cvc);

        await mainIframe.locator(this.selector.sqPayBtn).click();
    }

    async placeOrderUsingI1AndReviewTestApprove({ page }) {
        await page.click(this.selector.paymentMethodI1);
        await page.click(this.selector.placeOrder);
        await page.waitForURL(/page_id=7&order-pay=/);

        await page.waitForSelector(this.selector.sqIframeI1, { state: 'attached', timeout: 10000 });
        const iframe = page.frameLocator(this.selector.sqIframeI1);

        // First name, last name, and mobile phone came already filled.
        await iframe.locator(this.selector.sqI1DateOfBirth).click();
        await iframe.locator(this.selector.sqI1DateOfBirth).fill(this.shopper.approve.dateOfBirth);
        await iframe.locator(this.selector.sqI1Nin).click();
        await iframe.locator(this.selector.sqI1Nin).fill(this.shopper.approve.dni);
        await iframe.locator(this.selector.sqI1AcceptPrivacyPolicy).click();
        await iframe.locator(this.selector.sqI1Btn).click();

        this.fillOtp({ iframe });
    }

    async fillOtp({ iframe }) {
        await iframe.locator(this.selector.sqOtp1).waitFor({ state: 'attached', timeout: 10000 });
        await iframe.locator(this.selector.sqOtp1).fill(this.shopper.approve.otp[0]);
        await iframe.locator(this.selector.sqOtp2).fill(this.shopper.approve.otp[1]);
        await iframe.locator(this.selector.sqOtp3).fill(this.shopper.approve.otp[2]);
        await iframe.locator(this.selector.sqOtp4).fill(this.shopper.approve.otp[3]);
        await iframe.locator(this.selector.sqOtp5).fill(this.shopper.approve.otp[4]);

        await iframe.locator(this.selector.sqI1Btn).click();
    }

    async waitForOrderSuccess({ page }) {
        await page.waitForURL(/page_id=7&order-received=/);
    }

    async waitForOrderOnHold({ page }) {
        await page.waitForURL(/page_id=7&order-received=/);
        await expect(page.getByText('seQura is processing your request.')).toBeVisible();
    }

    async gotoAdminOrder({ page }) {
        const url = new URL(page.url());
        const orderId = url.searchParams.get('order-received');
        await page.goto(`${url.origin}/wp-admin/post.php?post=${orderId}&action=edit`);

        login({ page });
    }

    async expectOrderChangeToProcessing({ page }) {
        this.gotoAdminOrder({ page });

        const retries = 60;
        for (let i = 0; i < retries; i++) {
            try {
                await expect(page.locator(this.selector.adminOrderStatus)).toHaveValue("wc-on-hold");
                if (i < retries - 1) {
                    await page.waitForTimeout(1000);
                    await page.reload();
                }
            } catch (err) {
                console.log('Order has changed from on-hold');
                break;
            }
        }

        await expect(page.locator(this.selector.adminOrderStatus)).toHaveValue("wc-processing");
    }

    async expectFp1ToBeVisible({ page }) {
        await expect(page.locator(this.selector.sqPaymentMethodName, { hasText: this.sqProduct.fp1.es.name })).toBeVisible({ timeout: 100 });
    }

    async expectI1ToBeVisible({ page }) {
        await expect(page.locator(this.selector.sqPaymentMethodName, { hasText: this.sqProduct.i1.es.name })).toBeVisible({ timeout: 100 });
    }

    async expectSp1ToBeVisible({ page }) {
        await expect(page.locator(this.selector.sqPaymentMethodName, { hasText: this.sqProduct.sp1.es.name })).toBeVisible({ timeout: 100 });
    }

    async expectPp3ToBeVisible({ page }) {
        await expect(page.locator(this.selector.sqPaymentMethodName, { hasText: this.sqProduct.pp3.es.name })).toBeVisible({ timeout: 100 });
    }

    async expectPp3DecombinedToBeVisible({ page }) {
        await expect(page.locator(this.selector.sqPaymentMethodName, { hasText: this.sqProduct.pp3Decombined.es.name })).toBeVisible({ timeout: 100 });
    }
}

export const test = baseTest.extend({
    cart: async ({ }, use) => await use(new Cart()),
    checkout: async ({ }, use) => await use(new Checkout())
});

export { expect };
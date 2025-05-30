import { test as baseTest, expect } from "@playwright/test";
import AdvancedSettingsPage from "./AdvancedSettingsPage";
import PaymentMethodsSettingsPage from "./PaymentMethodsSettingsPage";
import OnboardingSettingsPage from "./OnboardingSettingsPage";
import ProductPage from "./ProductPage";
import CheckoutPage from "./CheckoutPage";
import GeneralSettingsPage from "./GeneralSettingsPage";
import ConnectionSettingsPage from "./ConnectionSettingsPage";
import WidgetSettingsPage from "./WidgetSettingsPage";
import CartPage from "./CartPage";
import ShopPage from "./ShopPage";
import WpAdmin from "./WpAdmin";
import SeQuraHelper from "./SeQuraHelper";

const test = baseTest.extend({
    advancedSettingsPage: async ({ page, baseURL, request }, use) => {
        const advancedSettingsPage = new AdvancedSettingsPage(page, baseURL, request, expect);

        await advancedSettingsPage.setup();
        await advancedSettingsPage.goto();

        // Provide the fixture to the test
        await use(advancedSettingsPage);
    },

    paymentMethodsSettingsPage: async ({ page, baseURL, request }, use) => {
        const paymentMethodsSettingsPage = new PaymentMethodsSettingsPage(page, baseURL, expect, request);

        await paymentMethodsSettingsPage.setup();
        await paymentMethodsSettingsPage.goto();

        // Provide the fixture to the test
        await use(paymentMethodsSettingsPage);
    },

    onboardingSettingsPage: async ({ page, baseURL, request }, use) => {
        const onboardingSettingsPage = new OnboardingSettingsPage(page, baseURL, expect, request);

        await onboardingSettingsPage.setup();
        await onboardingSettingsPage.goto();

        // Provide the fixture to the test
        await use(onboardingSettingsPage);
    },

    generalSettingsPage: async ({ page, baseURL, request }, use) => {
        const generalSettingsPage = new GeneralSettingsPage(page, baseURL, expect, request);

        await generalSettingsPage.setup();

        // Provide the fixture to the test
        await use(generalSettingsPage);
    },

    connectionSettingsPage: async ({ page, baseURL, request }, use) => {
        const connectionSettingsPage = new ConnectionSettingsPage(page, baseURL, expect, request);

        await connectionSettingsPage.setup();

        // Provide the fixture to the test
        await use(connectionSettingsPage);
    },

    widgetSettingsPage: async ({ page, baseURL, request }, use) => {
        const widgetSettingsPage = new WidgetSettingsPage(page, baseURL, expect, request);

        await widgetSettingsPage.setup();

        // Provide the fixture to the test
        await use(widgetSettingsPage);
    },
    wpAdmin: async ({ page, baseURL }, use) => await use(new WpAdmin(page, baseURL, expect)),
    sqHelper: async ({ request }, use) => await use(new SeQuraHelper(request, expect)),
    productPage: async ({ page }, use) => await use(new ProductPage(page, expect)),
    cartPage: async ({ page }, use) => await use(new CartPage(page, expect)),
    shopPage: async ({ page }, use) => await use(new ShopPage(page, expect)),
    checkoutPage: async ({ page, baseURL, request }, use) => await use(new CheckoutPage(page, baseURL, expect, request))
});

test.afterEach(async ({ page }, testInfo) => {
    if (testInfo.status !== testInfo.expectedStatus) {
        const screenshotPath = testInfo.outputPath(`screenshot.png`);
        testInfo.attachments.push({
            name: 'screenshot', path:
                screenshotPath, contentType: 'image/png'
        });
        await page.screenshot({ path: screenshotPath, fullPage: true });
    }
});

export { test, expect };
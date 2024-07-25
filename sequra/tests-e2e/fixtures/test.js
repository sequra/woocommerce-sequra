import { test as baseTest, expect } from "@playwright/test";
import AdvancedSettingsPage from "./AdvancedSettingsPage";
import PaymentMethodsSettingsPage from "./PaymentMethodsSettingsPage";
import OnboardingSettingsPage from "./OnboardingSettingsPage";
import ProductPage from "./ProductPage";
import CheckoutPage from "./CheckoutPage";
import GeneralSettingsPage from "./GeneralSettingsPage";

export const test = baseTest.extend({
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

    productPage: async ({ page }, use) => await use(new ProductPage(page)),
    checkoutPage: async ({ page, baseURL, request }, use) => await use(new CheckoutPage(page, baseURL, expect, request))
});

export { expect };
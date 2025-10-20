import { ProductPage as BaseProductPage } from "playwright-fixture-for-plugins";
// TODO: pending review
/**
 * Product page
 */
export default class ProductPage extends BaseProductPage {

    /**
   * Init the locators with the locators available
   * 
   * @returns {Object}
   */
    initLocators() {
        return {
            ...super.initLocators(),
            messageSuccess: () => this.page.locator('.wc-block-components-notice-banner.is-success,.woocommerce-message'),
            resetVariations: () => this.page.locator('.reset_variations'),
            variationSelect: attributeName => this.page.locator(`select[name="attribute_${attributeName}"]`),
        };
    }

    /**
    * Provide the product URL
    * @param {Object} options
    * @param {string} options.slug The product slug
    * @returns {string} The product URL
    */
    productUrl(options) {
        const { slug } = options;
        return `${this.baseURL}/products/${slug}/`;
    }

    /**
     * Provide the locator for the quantity input
     * 
     * @param {Object} options
     * @returns {import("@playwright/test").Locator}
     */
    qtyLocator(options = {}) {
        return this.page.locator('[name="quantity"]');
    }
    /**
     * Provide the locator for adding to cart button
     * 
     * @param {Object} options
     * @returns {import("@playwright/test").Locator}
     */
    addToCartLocator(options = {}) {
        return this.page.locator('[name="add-to-cart"]');
    }

    /**
     * Wait for the product to be in the cart
     * @param {Object} options
     * @returns {Promise<void>}
     */
    async expectProductIsInCart(options = {}) {
        await this.locators.messageSuccess().waitFor({ timeout: 10000 });
    }

    /**
     * Select a product variation
     * 
     * @param {string} attributeName The attribute name
     * @param {string} value The value to select
     * @return {Promise<void>}
     */
    async selectVariation(attributeName, value) {
        await this.locators.variationSelect(attributeName).selectOption(value);
    }

    /**
     * Clear selected variations
     * @return {Promise<void>}
     */
    async clearVariations() {
        await this.locators.resetVariations().click();
    }
}
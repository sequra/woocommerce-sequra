import { CartPage as BaseCartPage } from "playwright-fixture-for-plugins";
import DataProvider from "../utils/DataProvider.mjs";
// TODO: pending review
/**
 * Cart page
 */
export default class CartPage extends BaseCartPage {

    /**
     * Provide the cart URL
     * @param {Object} options
     * @returns {string} The cart URL
     */
    cartUrl(options) {
        return `${this.baseURL}/checkout/cart/`;
    }

    /**
     * Provide the locator for the coupon input
     * @param {Object} options Additional options if needed
     * @returns {import("@playwright/test").Locator}
     */
    couponInputLocator(options) {
        return this.page.locator('[name="coupon_code"],.wc-block-components-totals-coupon__form input');
    }

    /**
     * Provide the locator for the apply coupon button
     * @param {Object} options Additional options if needed
     * @returns {import("@playwright/test").Locator}
     */
    applyCouponBtnLocator(options) {
        return this.page.locator('[name="apply_coupon"],.wc-block-components-totals-coupon__form button');
    }

    /**
     * Provide the locator for the remove coupon button
     * @param {Object} options Additional options if needed
     * @returns {import("@playwright/test").Locator}
     */
    removeCouponBtnLocator(options) {
        return this.page.locator('.woocommerce-remove-coupon,.wc-block-components-totals-discount__coupon-list-item.is-removable button');
    }

    /**
     * Provide the locator for the quantity input
     * @param {Object} options Additional options if needed
     * @returns {import("@playwright/test").Locator}
     */
    quantityInputLocator(options) {
        return this.page.locator('.qty,.wc-block-components-quantity-selector__input');
    }

    /**
     * Provide the locator for the update cart button
     * @param {Object} options Additional options if needed
     * @param {string} options.uiVersion Use one of DataProvider.UI_BLOCKS or DataProvider.UI_CLASSIC. Defaults to DataProvider.UI_BLOCKS
     * @returns {import("@playwright/test").Locator|null} The locator for the update cart button, or null if not applicable
     */
    updateCartBtnLocator(options) {
        const { uiVersion = DataProvider.UI_BLOCKS } = options;
        if (uiVersion === DataProvider.UI_BLOCKS) {
            return null;
        }
        return this.page.locator('[name="update_cart"]');
    }

    /**
     * Some systems have a button to expand the coupon form
     * @param {Object} options Additional options if needed
     * @param {string} options.uiVersion Use one of DataProvider.UI_BLOCKS or DataProvider.UI_CLASSIC. Defaults to DataProvider.UI_BLOCKS
     * @returns {import("@playwright/test").Locator|null} The locator for the expand coupon form button, or null if not applicable
     */
    expandCouponFormBtnLocator(options) {
        const { uiVersion = DataProvider.UI_BLOCKS } = options;
        if (uiVersion !== DataProvider.UI_BLOCKS) {
            return null;
        }
        return this.page.locator('.wp-block-woocommerce-cart-order-summary-coupon-form-block .wc-block-components-panel__button');
    }

    /**
     * Provide the locator to look for the text when the cart is empty
     * @param {Object} options Additional options if needed
     * @returns {import("@playwright/test").Locator|null}
     */
    cartIsEmptyTextLocator(options) {
        return this.page.getByText('Your cart is currently empty!');
    }

    /**
     * Provide the locator for the remove cart item buttons
     * @param {Object} options Additional options if needed
     * @returns {import("@playwright/test").Locator}
     */
    removeCartItemBtnLocator(options) {
        return this.page.locator('.wc-block-cart-item__remove-link,.remove');
    }
}
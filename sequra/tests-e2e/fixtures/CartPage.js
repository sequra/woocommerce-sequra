import MiniWidgetPage from './MiniWidgetPage';

export default class CartPage extends MiniWidgetPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, expect) {
        super(page, expect);
        this.theme = {
            'storefront': {
                couponInput: '[name="coupon_code"]',
                applyCouponBtn: '[name="apply_coupon"]',
                rmCouponSel: '.woocommerce-remove-coupon',
                quantityInput: '.qty',
                updateCartBtn: '[name="update_cart"]'
            },
            'twentytwentyfour': {
                expandCouponForm: '.wp-block-woocommerce-cart-order-summary-coupon-form-block .wc-block-components-panel__button',
                couponInput: '.wc-block-components-totals-coupon__form input',
                applyCouponBtn: '.wc-block-components-totals-coupon__form button',
                rmCouponSel: '.wc-block-components-totals-discount__coupon-list-item.is-removable button',
                quantityInput: '.wc-block-components-quantity-selector__input'
            }
        };
    }

    async goto() {
        await this.page.goto('./cart/');
    }

    async applyCoupon({ coupon, theme = 'storefront' }) {

        const { expandCouponForm = null, couponInput, applyCouponBtn, rmCouponSel } = this.theme[theme];

        if (expandCouponForm) {
            await this.page.locator(expandCouponForm).click();
        }

        await this.page.locator(couponInput).fill(coupon);
        await this.page.locator(applyCouponBtn).click();
        await this.page.waitForSelector(rmCouponSel, { timeout: 5000 });
    }

    async removeCoupon({ theme = 'storefront' }) {
        const rmCouponSel = this.theme[theme].rmCouponSel;
        await this.page.locator(rmCouponSel).click();
        await this.page.waitForSelector(rmCouponSel, { state: 'detached' });
    }

    async setQuantity({ quantity, theme = 'storefront' }) {
        const { quantityInput, updateCartBtn = null } = this.theme[theme];
        await this.page.locator(quantityInput).fill(`${quantity}`);
        if (updateCartBtn) {
            await this.page.locator(updateCartBtn).click();
        }
    }

    async emptyCart() {
        try {
            await this.expect(this.page.getByText('Your cart is currently empty!')).toBeVisible({ timeout: 1000 });
            return; // The cart is already empty
        } catch (e) {
            // The cart is not empty, continue.
        }
        // '.wc-block-cart-item__remove-link,.remove'
        const rmBtnLocator = this.page.locator('.wc-block-cart-item__remove-link,.remove');
        // count the existing remove buttons
        let qty = await rmBtnLocator.count();
        while (qty > 0){
            // click the first one
            await rmBtnLocator.first().click();
            qty -= 1;
            // then wait until the amount of remove buttons is less than before
            await this.page.waitForFunction(`document.querySelectorAll('.wc-block-cart-item__remove-link,.remove').length == ${qty}`);
        } 
    }
}
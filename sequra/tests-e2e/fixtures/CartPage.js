import MiniWidgetPage from './MiniWidgetPage';

export default class CartPage extends MiniWidgetPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, expect) {
        super(page, expect);
        this.theme = {
            'storefront': { rmCouponSel: '.woocommerce-remove-coupon' },
            'twentytwentyfour': { rmCouponSel: '.woocommerce-remove-coupon' }
        };
    }

    async goto() {
        await this.page.goto('./cart/');
    }

    async applyCoupon({ coupon, theme = 'storefront' }) {
        await this.page.locator('[name="coupon_code"]').fill(coupon);
        await this.page.locator('[name="apply_coupon"]').click();
        await this.page.waitForSelector(this.theme[theme].rmCouponSel);
    }

    async removeCoupon({ theme = 'storefront' }) {
        const rmCouponSel = this.theme[theme].rmCouponSel;
        await this.page.locator(rmCouponSel).click();
        await this.page.waitForSelector(rmCouponSel, { state: 'detached' });
    }

    async setQuantity({ quantity, theme = 'storefront' }) {
        await this.page.locator('.qty').fill(`${quantity}`);
        await this.page.locator('[name="update_cart"]').click();
    }
}
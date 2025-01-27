export default class MiniWidgetPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, expect) {
        this.page = page;
        this.expect = expect;
    }

    async expectMiniWidgetToBeVisible({ navigate = true, visible = true, locationSel, product, amount = 0, message = '' }) {
        if (navigate) {
            await this.goto();
        }
        let containerSel = `${locationSel} ~ .sequra-educational-popup.sequra-educational-popup--${product}[data-product="${product}"]`;
        if (amount) {
            containerSel += `[data-amount="${amount}"]`;
        }
        if (visible) {
            await this.page.locator(containerSel, { hasText: message }).first().waitFor({ timeout: 5000 });
        } else {
            await this.expect(this.page.locator(containerSel)).toHaveCount(0, { timeout: 5000 });
        }
    }
}
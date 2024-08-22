export default class ProductPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} slug
     */
    constructor(page) {
        this.page = page;
    }

    /**
     * @param {Object} options
     * @param {string} options.slug
     * @returns {Promise<string>} The URL of the product
     */
    async goto({ slug }) {
        const url = this.getProductUrl(slug);
        await this.page.goto(url);
        return url;
    }

    getProductUrl(slug) {
        // return `./?product=${slug}`;
        return `./product/${slug}/`;
    }

    /**
     * @param {Object} options
     * @param {string} options.slug The product slug
     * @param {number} options.quantity The quantity to add to the cart
     */
    async addToCart({ slug, quantity }) {
        const url = await this.goto({ slug });
        await this.page.fill('[name="quantity"]', `${quantity || 1}`);
        await this.page.click('[name="add-to-cart"]');
        await this.page.waitForURL(url, { timeout: 5000, waitUntil: 'commit' });
    }

    async expectWidgetToBeVisible({ locationSel, widgetConfig, product, amount, registrationAmount, campaign = null }) {
        let containerSel = `${locationSel} ~ .sequra-promotion-widget.sequra-promotion-widget--${product}`;
        // [data-amount="9000"][data-registration-amount="0"]
        const styles = JSON.parse(widgetConfig);
        Object.keys(styles).forEach(key => {
            containerSel += '' !== styles[key] ? `[data-${key}="${styles[key]}"]` : `[data-${key}]`;
        });
        containerSel += `[data-amount="${amount}"][data-registration-amount="${registrationAmount}"][data-loaded="1"]`;
        if (campaign) {
            containerSel += `[data-campaign="${campaign}"]`;
        }

        const iframeSel = `${containerSel} iframe.Sequra__PromotionalWidget`;

        await this.page.locator(iframeSel).waitFor({ timeout: 5000 });
    }
}
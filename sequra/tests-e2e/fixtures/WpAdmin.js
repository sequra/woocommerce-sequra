export default class WpAdmin {

    /**
     * @param {import('@playwright/test').Page} page 
     */
    constructor(page, baseURL, expect) {
        this.page = page;
        this.baseURL = baseURL;
        this.expect = expect;
    }

    /**
     * Login to WordPress admin
     * 
     * @param {Object} options
     * @param {boolean} options.force Whether to force the login even if already logged in
     * @returns {Promise<void>}
     */
    async login({ force = false }) {
        try {
            if (!force) {
                const cookies = await this.page.context().cookies();
                if (cookies.find(c => -1 !== c.name.search('wordpress_logged_in'))) {
                    return;
                }
            }
            await this.page.goto(`${this.baseURL}/wp-login.php?redirect_to=${encodeURIComponent(`${this.baseURL}/wp-admin/`)}&reauth=1`);
            await this.expect(this.page.locator('#loginform')).toBeVisible({ timeout: 0 });
            await this.page.fill('#user_login', process.env.WP_ADMIN_USER);
            await this.page.fill('#user_pass', process.env.WP_ADMIN_PASSWORD);
            await this.page.click('#wp-submit');
            await this.page.waitForURL('./wp-admin/');
        }
        catch {
            return;
        }
    }

    /**
     * Navigate to SeQura settings page
     * 
     * @param {Object} options
     * @param {string} options.page The page within settings to navigate to
     */
    async gotoSeQuraSettings({ page = '' }) {
        await this.login({ force: false });
        await this.page.goto(`./wp-admin/options-general.php?page=sequra${page ? `#${page}` : ''}`, { waitUntil: 'domcontentloaded' });
    }

    async gotoOrder({orderId}) {
        await this.login({force: false});
        await this.page.goto(`${this.baseURL}/wp-admin/post.php?post=${orderId}&action=edit`);
    }
}
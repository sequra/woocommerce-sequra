import { BackOffice as BaseBackOffice } from 'playwright-fixture-for-plugins';
export default class BackOffice extends BaseBackOffice {

    /**
     * Init the locators with the locators available
     * 
     * @returns {Object}
     */
    initLocators() {
        return {
            usernameInput: () => this.page.locator('#user_login'),
            passwordInput: () => this.page.locator('#user_pass'),
            loginButton: () => this.page.locator('#wp-submit'),
            menuBarItemSeQuraLink: () => this.page.locator('[href="admin.php?page=sequra"]'),
            menuBarItemOrdersLink: () => this.page.locator('[href="admin.php?page=wc-orders"]'),
        };
    }

    /**
     * Login
     * 
     * @param {Object} options Additional options
     * @returns {Promise<void>}
     */
    async login(options = { waitUntil: 'domcontentloaded', force: false }) {
        const { force, waitUntil } = { waitUntil: 'domcontentloaded', force: false, ...options };
        
        if (!force) {
            const cookies = await this.page.context().cookies();
            if (cookies.find(c => -1 !== c.name.search('wordpress_logged_in'))) {
                return;
            }
        }

        try {
            await this.page.goto(
                `${this.baseURL}/wp-login.php?redirect_to=${encodeURIComponent(`${this.baseURL}/wp-admin/`)}&reauth=1`,
                { waitUntil: waitUntil }
            );
            await this.expect(this.locators.usernameInput(), 'Username input is visible').toBeVisible({ timeout: 100 });
            await this.expect(this.locators.passwordInput(), 'Password input is visible').toBeVisible({ timeout: 100 });
        }
        catch {
            return;
        }

        const user = process.env.WP_ADMIN_USER;
        const pass = process.env.WP_ADMIN_PASSWORD;

        console.log(`Logging in as user: "${user}" with password: "${pass}"`);

        await this.locators.usernameInput().fill(user);
        await this.locators.passwordInput().fill(pass);
        await this.locators.loginButton().click();
        await this.page.waitForURL('./wp-admin/');
    }

    /**
     * Logout
     * 
     * @param {Object} options Additional options
     * @returns {Promise<void>}
     */
    async logout(options = {}) {
        // Clear WordPress login cookies.
        await this.page.context().clearCookies({ name: /wordpress_logged_in.*/ });
    }

    async #gotoLinkTarget(link, append = '') {
        const url = './wp-admin/' + (await link.getAttribute('href')) + append;
        await this.page.goto(url, { waitUntil: 'domcontentloaded' });
    }

    /**
     * Navigate to SeQura settings page
     * 
     * @param {Object} options
     * @param {string} options.page The page within settings to navigate to
     */
    async gotoSeQuraSettings(options = { page: '' }) {
        await this.login();
        await this.#gotoLinkTarget(this.locators.menuBarItemSeQuraLink(), `#${options.page}`);
    }

    /**
     * Navigate to Order listing page
     * 
     * @param {Object} options
     */
    async gotoOrderListing(options) {
        await this.login();
        await this.#gotoLinkTarget(this.locators.menuBarItemOrdersLink());
    }

    /**
     * Navigate to a specific post edition page
     *
     * @param {Object} options
     * @param {number} options.postId The Post ID to navigate to
     */
    async #gotoPostEdit({ postId }) {
        await this.login({ force: false });
        await this.page.goto(`${this.baseURL}/wp-admin/post.php?post=${postId}&action=edit`);
    }
}
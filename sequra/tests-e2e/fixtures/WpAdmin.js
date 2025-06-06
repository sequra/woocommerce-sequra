export default class WpAdmin {

    /**
     * @param {import('@playwright/test').Page} page 
     */
    constructor(page, baseURL, expect) {
        this.page = page;
        this.baseURL = baseURL;
        this.expect = expect;

        this.locator = {
            deactivatePluginBtn: plugin => this.page.locator(`.deactivate > a[href*="${encodeURIComponent(plugin)}"]`),
            installPluginSubmit: () => this.page.locator('#install-plugin-submit'),
            activatePluginActionBtn: () => this.page.locator('a[href*="action=activate"]'),
            notice: ({ message }) => this.page.locator(`.notice > p`, { hasText: message }),
            fileInput: () => this.page.locator('input[type="file"]'),
            overwritePluginBtn: () => this.page.locator('.update-from-upload-overwrite'),
            isSequraBannedCheckbox: () => this.page.locator('#is_sequra_banned'),
            publishPostBtn: () => this.page.locator('#publish'),
        };
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
            const user = process.env.WP_ADMIN_USER;
            const pass = process.env.WP_ADMIN_PASSWORD;
            console.log(`Logging in as user: "${user}" with password: "${pass}"`);

            await this.page.goto(`${this.baseURL}/wp-login.php?redirect_to=${encodeURIComponent(`${this.baseURL}/wp-admin/`)}&reauth=1`, { waitUntil: 'domcontentloaded' });
            await this.page.locator('#user_login').pressSequentially(user);
            await this.page.locator('#user_pass').pressSequentially(pass);
            await this.page.locator('#wp-submit').click();
            await this.page.waitForURL('./wp-admin/');
        }
        catch {
            return;
        }
    }

    async logout() {
        // await this.page.goto(`${this.baseURL}/wp-login.php?action=logout`);
        // logout by clearing cookies
        await this.page.context().clearCookies({ name: /wordpress_logged_in.*/ });
    }

    /**
     * Navigate to SeQura settings page
     * 
     * @param {Object} options
     * @param {string} options.page The page within settings to navigate to
     */
    async gotoSeQuraSettings({ page = '' }) {
        await this.login({ force: false });
        await this.page.goto(`./wp-admin/admin.php?page=sequra${page ? `#${page}` : ''}`, { waitUntil: 'domcontentloaded' });
    }

    async gotoOrder({ orderId }) {
        await this.#gotoPostEdit({ postId: orderId });
    }

    async gotoProduct({ productId }) {
        await this.#gotoPostEdit({ postId: productId });
    }

    async setProductAsBanned(banned = true) {
        await this.locator.isSequraBannedCheckbox().setChecked(banned);
        await this.locator.publishPostBtn().click();
        await this.page.waitForURL(/post\.php\?post=\d+&action=edit/);
    }

    async #gotoPostEdit({ postId }) {
        await this.login({ force: false });
        await this.page.goto(`${this.baseURL}/wp-admin/post.php?post=${postId}&action=edit`);
    }

    /**
     * Navigate to Plugins page
     */
    async gotoPlugins() {
        await this.login({ force: false });
        await this.page.goto('./wp-admin/plugins.php', { waitUntil: 'domcontentloaded' });
    }

    /**
     * Deactivate a plugin
     * 
     * @param {Object} options
     * @param {string} options.plugin The plugin basename to deactivate. For example '_sequra/sequra.php'
     */
    async deactivatePlugin({ plugin }) {
        await this.locator.deactivatePluginBtn(plugin).click({ timeout: 5000 });
    }

    /**
    * Upload a plugin and activate it
    * 
    * @param {string} path The path to the plugin zip file
    * @param {Object} opt
    * @param {boolean} opt.activate Whether to activate the plugin after uploading
    * @param {boolean} opt.upgrade Whether to upgrade the plugin if already installed
    * @param {string} opt.method The HTTP method to use for the request
    */
    async uploadPlugin(path, opt = { filename: null, activate: true, upgrade: true, method: 'GET' }) {
        await this.page.goto('./wp-admin/plugin-install.php?tab=upload');

        if (!opt.filename) {
            const parts = path.split('/');
            const filename = parts[parts.length - 1];
            opt.filename = filename.endsWith('.zip') ? filename : `plugin.zip`;
        }

        await this.locator.fileInput().setInputFiles({
            name: opt.filename,
            mimeType: 'application/zip',
            buffer: Buffer.from(await (await fetch(path, { method: opt.method })).arrayBuffer())
        });
        await this.locator.installPluginSubmit().click();
        await this.page.waitForURL('./wp-admin/update.php?action=upload-plugin', { waitUntil: 'domcontentloaded' });

        if (opt.upgrade) {
            await this.locator.overwritePluginBtn().click();
            await this.page.waitForURL(/overwrite=update-plugin/, { waitUntil: 'domcontentloaded' });
        } else if (opt.activate) {
            await this.locator.activatePluginActionBtn().click();
            await this.page.waitForURL(/\/wp-admin\/plugins\.php/, { waitUntil: 'domcontentloaded' });
            await this.locator.notice({ message: 'Plugin activated.' }).waitFor({ state: 'visible' });

        }
    }
}
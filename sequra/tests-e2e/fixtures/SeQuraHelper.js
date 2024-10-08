export default class SeQuraHelper {

    /**
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(request, expect) {
        this.request = request;
        this.expect = expect;
        this.webhooks = {
            CLEAR_CONFIG: 'clear_config',
            DUMMY_CONFIG: 'dummy_config',
            DUMMY_SERVICE_CONFIG: 'dummy_services_config',
            REMOVE_LOG: 'remove_log',
            PRINT_LOGS: 'print_logs',
            FORCE_ORDER_FAILURE: 'force_order_failure',
            SET_THEME: 'set_theme',
            CART_VERSION: 'cart_version',
            CHECKOUT_VERSION: 'checkout_version',
            REMOVE_DB_TABLES: 'remove_db_tables',
            V2_CONFIG: 'v2_config',
        };
    }

    /**
     * Login to WordPress admin
     * 
     * @param {Object} options
     * @param {string} options.webhook The webhook to execute
     * @param {Array<Object>} options.args The arguments to pass to the webhook. Each argument is an object with `name` and `value` properties
     * @returns {Promise<void>}
     */
    async executeWebhook({ webhook, args = [] }) {
        let url = `./?sq-webhook=${webhook}`;
        for (const { name, value } of args) {
            url += `&${name}=${encodeURIComponent(value)}`;
        }
        try {
            const response = await this.request.post(url);
            this.expect(response.status(), 'Webhook response has HTTP 200 code').toBe(200);
            const json = await response.json();
            this.expect(json.success, 'Webhook was processed successfully').toBe(true);
        } catch (e) {
            console.log(webhook, args, e);
            throw e;
        }
    }
}
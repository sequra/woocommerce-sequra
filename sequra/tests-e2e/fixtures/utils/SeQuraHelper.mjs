import { SeQuraHelper as BaseSeQuraHelper } from 'playwright-fixture-for-plugins';

export default class SeQuraHelper extends BaseSeQuraHelper {

    /**
     * Init the webhooks available
     * 
     * @returns {Object} The webhooks available
     */
    initWebhooks() {
        return {
            clear_config: 'clear_config',
            dummy_config: 'dummy_config',
            dummy_services_config: 'dummy_services_config',
            clear_front_end_cache: 'clear_front_end_cache',
            verify_order_has_merchant_id: 'verify_order_has_merchant_id',
            remove_log: 'remove_log',
            print_logs: 'print_logs',
            force_order_failure: 'force_order_failure',
            set_theme: 'set_theme',
            cart_version: 'cart_version',
            checkout_version: 'checkout_version',
            remove_db_tables: 'remove_db_tables',
            v2_config: 'v2_config',
            remove_address_fields: 'remove_address_fields',
        };
    }

     /**
     * Prepare the URL to use
     * 
     * @param {Object} options Additional options
     * @param {string} options.webhook The webhook
     * @param {Array<Object>} options.args The arguments to pass to the webhook. Each argument is an object with `name` and `value` properties
     * @returns {string} The URL to use
     */
     getWebhookUrl(options = { webhook, args: [] }) {
        const { webhook, args } = options;
        return `${this.baseURL}/?sq-webhook=${webhook}${this.getWebhookUrlArgs(args)}`;
    }
}
<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraCampaignPaymentGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        do_action('woocommerce_sequracampaign_before_load', $this);
        $this->id = 'sequracampaign';

        $this->method_title       = __('Campaña SeQura', 'wc_sequracampaign');
        $this->method_description = __('Allows special campaign, service ofered by SeQura.', 'wc_sequracampaign');
        $this->supports           = array(
            'products'
        );

        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->coresettings = get_option('woocommerce_sequra_settings', array());

        // Get setting values
        $this->enabled              = $this->settings['enabled'];
        $this->title                = $this->settings['title'];
        $this->product              = 'pp5';//not an option
        $this->campaign             = $this->settings['campaign'];
        $this->icon                 = sequra_get_script_basesurl() . 'images/small-logo.png';
        $this->enable_for_countries = array('ES');
        $this->has_fields           = true;
        $this->env                  = $this->coresettings['env'];
        $this->helper               = new SequraHelper($this);
        // Logs
        if ($this->coresettings['debug'] == 'yes') {
            $this->log = new WC_Logger();
        }

        // Hooks
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('woocommerce_api_woocommerce_' . $this->id, array($this->helper, 'check_response'));
        $json       = get_option('sequracampaign_conditions');
        $conditions = json_decode($json, true);
        if ( ! $conditions) {
            $this->enabled = false;
        } else {
            foreach ($conditions[$this->product] as $campaign) {
                if ($campaign['campaign'] == $this->campaign) {
                    $this->first_date = strtotime($campaign['first_date']);
                    $this->last_date  = strtotime($campaign['last_date']);
                    $this->fees_table = array_map(function ($value) {
                        return [$value[0] / 100, $value[1] / 100];
                    }, $campaign['fees_table']);
                    $this->max_amount = $campaign['max_amount'] / 100;
                    $this->min_amount = $campaign['min_amount'] / 100;
                    break;
                }
            }
        }
        do_action('woocommerce_sequracampaign_loaded', $this);
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields()
    {
        $shipping_methods = array();

        if (is_admin()) {
            foreach (WC()->shipping->load_shipping_methods() as $method) {
                $shipping_methods[$method->id] = $method->get_title();
            }
        }
        $this->form_fields = array(
            'enabled'      => array(
                'title'       => __('Enable/Disable', 'wc_sequracampaign'),
                'type'        => 'checkbox',
                'description' => __('Habilitar campaña especial SeQura', 'wc_sequracampaign'),
                'default'     => 'no',
            ),
            'title'        => array(
                'title'       => __('Title', 'wc_sequracampaign'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.',
                    'wc_sequracampaign'),
                'default'     => __('Quiero pagarlo en octubre.', 'wc_sequracampaign'),
            ),
            'campaign'     => array(
                'title'       => __('Campaign code', 'wc_sequracampaign'),
                'type'        => 'text',
                'description' => __('Campaign code provided by SeQura.', 'wc_sequracampaign'),
                'default'     => __('verano2018', 'wc_sequracampaign'),
            ),
            'widget_theme' => array(
                'title'       => __('Widget theme', 'wc_sequra'),
                'type'        => 'text',
                'description' => __('Widget theme: white, default...', 'wc_sequra'),
                'default'     => 'white'
            )
        );
        $this->form_fields = apply_filters('woocommerce_sequracampaign_init_form_fields', $this->form_fields, $this);
    }

    /**
     * Check If The Gateway Is Available For Use
     *
     * @return bool
     */
    public function is_available()
    {
        if ($this->enabled !== 'yes') {
            return false;
        } elseif (is_admin()) {
            return true;
        }

        if (
            ($_SERVER['REQUEST_METHOD'] == 'POST' || is_page(wc_get_page_id('checkout'))) &&
            ! $this->is_available_in_checkout()
        ) {
            return false;
        }

        if (is_product() && ! $this->is_available_in_product_page()) {
            return false;
        }

        if (
            $this->enable_for_countries &&
            ! in_array(WC()->customer->get_shipping_country(), $this->enable_for_countries)
        ) {
            return false;
        }
        if (WC()->cart && 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total()) {
            return false;
        }
        if ('' != $this->coresettings['test_ips']) {
            $ips = explode(',', $this->coresettings['test_ips']);

            return in_array($_SERVER['REMOTE_ADDR'], $ips);
        }

        return true;
    }

    function is_available_in_checkout()
    {
        if (
            ! $this->isCampaignPeriod() ||
            $this->coresettings['enable_for_virtual'] == 'yes') {
            return false;
        }/* else if ( ! WC()->cart->needs_shipping() ) {
			return false;
		}*/

        return true;
    }

    private function isCampaignPeriod()
    {
        if (isset($_GET['sequra_campaign_preview']) && $_GET['sequra_campaign_preview'] == $this->campaign) {
            return true;
        }

        return time() < $this->last_date && time() > $this->first_date;
    }

    function is_available_in_product_page()
    {
        $product = $GLOBALS['product'];
        if (
            ! $this->isCampaignPeriod() ||
            $this->min_amount > $product->price
        ) {
            return false;
        }

        if ($this->coresettings['enable_for_virtual'] == 'yes') {
            //return get_post_meta( $product->id, 'is_sequra_service', true ) != 'no';
            return true;//Non-services can be purchased too but not alone.
        } elseif ( ! $product->needs_shipping()) {
            return false;
        }

        return true;
    }

    /**
     * There might be payment fields for SeQura, and we want to show the description if set.
     * */
    function payment_fields()
    {
        require(self::template_loader('campaign_fields'));
    }

    public static function template_loader($template)
    {
        if (file_exists(STYLESHEETPATH . '/' . WC_TEMPLATE_PATH . $template . '.php')) {
            return STYLESHEETPATH . '/' . WC_TEMPLATE_PATH . $template . '.php';
        } elseif (file_exists(TEMPLATEPATH . '/' . WC_TEMPLATE_PATH . $template . '.php')) {
            return TEMPLATEPATH . '/' . WC_TEMPLATE_PATH . $template . '.php';
        } elseif (file_exists(STYLESHEETPATH . '/' . $template . '.php')) {
            return STYLESHEETPATH . '/' . $template . '.php';
        } elseif (file_exists(TEMPLATEPATH . '/' . $template . '.php')) {
            return TEMPLATEPATH . '/' . $template . '.php';
        } else {
            return WP_CONTENT_DIR . "/plugins/" . plugin_basename(dirname(__FILE__)) . '/templates/' . $template . '.php';
        }
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     * */
    public function admin_options()
    {
        ?>
        <h3><?php _e('Campañas SeQura', 'wc_sequracampaign'); ?></h3>
        <p><?php _e('Permite ofrecer campañas especiales de SeQura', 'wc_sequracampaign'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table><!--/.form-table-->
        <?php
    }

    function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        do_action('woocommerce_sequracampaign_process_payment', $order, $this);
        $ret = array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );

        return apply_filters('woocommerce_sequracampaign_process_payment_return', $ret, $this);
    }

    //@todo: decide to move this to SequraHelper
    function receipt_page($order)
    {
        $order = new WC_Order($order);
        echo '<p>' . __('Thank you for your order, please click the button below to pay with SeQura.',
                'wc_sequra') . '</p>';
        $options = array('product' => $this->product);
        if ($this->campaign) {
            $options['campaign'] = $this->campaign;
        }
        $this->helper->get_identity_form($options, $order);
        $this->identity_form = $this->helper->identity_form;
        require(SequraHelper::template_loader('payment_identification'));
    }

    static public function available_products($products)
    {
        $products[] = 'pp5';

        return $products;
    }
}

add_filter('sequra_available_products', array('SequraCampaignPaymentGateway', 'available_products'));

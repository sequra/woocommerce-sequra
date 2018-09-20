<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraPartPaymentGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        do_action('woocommerce_sequra_pp_before_load', $this);
        $this->id                 = 'sequra_pp';
        $this->icon               = sequra_get_script_basesurl() . 'images/small-logo.png';
        $this->method_title       = __('Fraccionar pago', 'wc_sequra');
        $this->method_description = __('Allows payments part payments, service ofered by SeQura.', 'wc_sequra');
        //$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon.png';
        $this->supports = array(
            'products'
        );

        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->coresettings = get_option('woocommerce_sequra_settings', SequraHelper::empty_core_settings);
        // Get setting values
        $this->enabled              = $this->settings['enabled'];
        $this->title                = $this->settings['title'];
        $this->enable_for_countries = array('ES');
        $this->has_fields           = true;
        $this->price_css_sel        = htmlspecialchars_decode($this->settings['price_css_sel']);
        $this->dest_css_sel         = htmlspecialchars_decode($this->settings['dest_css_sel']);
        $this->product              = 'pp3';//not an option
        $this->pp_cost_url          = 'https://' .
                                      ($this->coresettings['env'] ? 'sandbox' : 'live') .
                                      '.sequracdn.com/scripts/' .
                                      $this->coresettings['merchantref'] . '/' .
                                      $this->coresettings['assets_secret'] .
                                      '/pp3_pp5_cost.js';
        $this->env                  = $this->coresettings['env'];
        $this->helper               = new SequraHelper($this);

        // Logs
        if (isset($this->coresettings['debug']) && $this->coresettings['debug'] == 'yes') {
            $this->log = new WC_Logger();
        }

        // Hooks
        add_action('woocommerce_receipt_' . $this->id, array($this->helper, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('woocommerce_api_woocommerce_' . $this->id, array($this->helper, 'check_response'));
        $json       = get_option('sequrapartpayment_conditions');
        $conditions = json_decode($json, true);
        if ( ! $conditions) {
            $this->part_max_amount = 3000;
            $this->min_amount      = 50;
        } else {
            $this->part_max_amount = $conditions[$this->product]['max_amount'] / 100;
            $this->min_amount      = $conditions[$this->product]['min_amount'] / 100;
        }
        do_action('woocommerce_sequra_pp_loaded', $this);
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
            'enabled'       => array(
                'title'       => __('Enable/Disable', 'wc_sequra'),
                'type'        => 'checkbox',
                'description' => __('Habilitar pasarela SeQura', 'wc_sequra'),
                'default'     => 'no'
            ),
            'title'         => array(
                'title'       => __('Title', 'wc_sequra'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wc_sequra'),
                'default'     => __('Fracciona tu pago.', 'wc_sequra')
            ),
            'widget_theme'  => array(
                'title'       => __('Widget theme', 'wc_sequra'),
                'type'        => 'text',
                'description' => __('Widget theme: white, default...', 'wc_sequra'),
                'default'     => 'white'
            ),
            'price_css_sel' => array(
                'title'       => __('CSS price selector', 'wc_sequra'),
                'type'        => 'text',
                'description' => __('CSS selector to get the price for installment simulator', 'wc_sequra'),
                'default'     => '.summary .price>.amount,.summary .price ins .amount'
            ),
            'dest_css_sel'  => array(
                'title'       => __('CSS selector for simulator', 'wc_sequra'),
                'type'        => 'text',
                'description' => __('CSS after which the simulator will be draw. if just showing it below the prices is not good. Usually empty should be fine',
                    'wc_sequra'),
                'default'     => ''
            ),
        );
        $this->form_fields = apply_filters('woocommerce_sequra_pp_init_form_fields', $this->form_fields, $this);
    }

    /**
     * Check If The Gateway Is Available For Use
     *
     * @return bool
     */
    public function is_available($product_id = null)
    {
        if ($this->enabled !== 'yes') {
            return false;
        } elseif (is_admin()) {
            return true;
        }
        if ((get_the_ID() == wc_get_page_id('checkout') || $_SERVER['REQUEST_METHOD'] == 'POST') && ! $this->is_available_in_checkout()) {
            return false;
        }
        if (
            ($_SERVER['REQUEST_METHOD'] == 'POST' || is_page(wc_get_page_id('checkout'))) &&
            ! $this->is_available_in_checkout()
        ) {
            return false;
        }
        if (is_product() && $product_id && ! $this->is_available_in_product_page($product_id)) {
            return false;
        }

        if (
            $this->enable_for_countries &&
            ! in_array(WC()->customer->get_shipping_country(), $this->enable_for_countries)
        ) {
            return false;
        }

        if (1 == $this->env && '' != $this->coresettings['test_ips']) { //Sandbox
            $ips = explode(',', $this->coresettings['test_ips']);

            return in_array($_SERVER['REMOTE_ADDR'], $ips);
        }

        return true;
    }

    function is_available_in_checkout()
    {
        if ($this->coresettings['enable_for_virtual'] == 'yes') {
            if ( ! $this->helper->isElegibleForServiceSale()) {
                return false;
            }
        } elseif ( ! $this->helper->isElegibleForProductSale()) {
            return false;
        }

        if (0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total()) {
            return false;
        }

        if (0 < $this->get_order_total() && $this->min_amount > $this->get_order_total()) {
            return false;
        }

        return true;
    }

    function is_available_in_product_page($product_id)
    {
        $product = new WC_Product($product_id);
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
        require(SequraHelper::template_loader('partpayment_fields'));
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     * */
    public function admin_options()
    {
        ?>
        <h3><?php _e('Pasarela SeQura', 'wc_sequra'); ?></h3>
        <p><?php _e('La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá dar la opción de "Recibe primero, paga después" en su comercio. Para ello necesitará una cuenta de vendedor en SeQura.',
                'wc_sequra'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table><!--/.form-table-->
        <?php
    }

    function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        do_action('woocommerce_sequra_pp_process_payment', $order, $this);
        $ret = array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );

        return apply_filters('woocommerce_sequra_pp_process_payment_return', $ret, $this);
    }

    static public function available_products($products)
    {
        $products[] = 'pp3';

        return $products;
    }

    /**
     * Save options in admin.
     */
    public function process_admin_options() {
        parent::process_admin_options();
        //Force update
        update_option( 'sequrapartpayment_next_update', 0 );
        sequrapartpayment_upgrade_if_needed();
    }
}

add_filter('sequra_available_products', array('SequraPartPaymentGateway', 'available_products'));

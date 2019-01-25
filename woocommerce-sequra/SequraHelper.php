<?php

class SequraHelper
{
    const ISO8601_PATTERN = '^((\d{4})-([0-1]\d)-([0-3]\d))+$|P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$';

    /* Payment Method */
    public $empty_core_settings;
    /* Sequra Client */
    private $_pm;
    /* Json builder */
    private $_client;
    private $_builder;

    public function __construct($pm)
    {
        $this->_pm           = $pm;
        $this->identity_form = null;
        $this->dir           = dirname(__FILE__) . '/';
        require_once($this->dir . 'vendor/autoload.php');
        if ( ! class_exists('SequraTempOrder')) {
            require_once($this->dir . 'SequraTempOrder.php');
        }
    }

    public static function get_empty_core_settings()
    {
        return array(
            'env'                => 1,
            'merchantref'        => '',
            'assets_secret'      => '',
            'user'               => '',
            'password'           => '',
            'enable_for_virtual' => 'no',
            'debug'              => 'no'
        );
    }

    public static function get_cart_info_from_session()
    {
        sequra_add_cart_info_to_session();

        return WC()->session->get('sequra_cart_info');
    }

    public static function isFullyVirtual(WC_Cart $cart)
    {
        return ! $cart::needs_shipping();
    }

    public static function validateServiceEndDate($service_end_date)
    {
        return preg_match('/' . self::ISO8601_PATTERN . '/', $service_end_date);
    }

    function get_credit_agreements($amount)
    {
        return $this->getClient()->getCreditAgreements($this->getBuilder()->integerPrice($amount),
            $this->_pm->merchantref);
    }

    public function getClient()
    {
        if ($this->_client instanceof \Sequra\PhpClient\Client) {
            return $this->_client;
        }
        if ( ! class_exists('\Sequra\PhpClient\Client')) {
            require_once($this->dir . 'lib/\Sequra\PhpClient\Client.php');
        }
        \Sequra\PhpClient\Client::$endpoint   = SequraPaymentGateway::$endpoints[$this->_pm->coresettings['env']];
        \Sequra\PhpClient\Client::$user       = $this->_pm->coresettings['user'];
        \Sequra\PhpClient\Client::$password   = $this->_pm->coresettings['password'];
        \Sequra\PhpClient\Client::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
        $this->_client            = new \Sequra\PhpClient\Client();

        return $this->_client;
    }

    public function getBuilder($order = null)
    {
        if ($this->_builder instanceof \Sequra\PhpClient\BuilderAbstract) {
            return $this->_builder;
        }
        if ( ! class_exists('SequraBuilderWC')) {
            require_once($this->dir . 'SequraBuilderWC.php');
        }
        $builderClass   = apply_filters('sequra_set_builder_class', 'SequraBuilderWC');
        $this->_builder = new $builderClass($this->_pm->coresettings['merchantref'], $order);

        return $this->_builder;
    }

    function check_response()
    {
        $order = new WC_Order($_REQUEST['order']);
        if (isset($_REQUEST['signature'])) {
            return $this->check_ipn($order);
        }
        $url = $this->_pm->get_return_url($order);
        if ( ! $order->is_paid()) {
            wc_add_notice(__('Ha habido un probelma con el pago. Por favor, inténtelo de nuevo o escoja otro método de pago.',
                'wc_sequra'), 'error');
            //$url = $pm->get_checkout_payment_url();  Notice is not shown in payment page
            $url = $order->get_cancel_order_url();
        }
        wp_redirect($url, 302);
    }

    function check_ipn($order)
    {
        do_action('woocommerce_' . $this->_pm->id . '_process_payment', $order, $this->_pm);
        if ($approval = apply_filters('woocommerce_' . $this->_pm->id . '_process_payment', $this->get_approval($order),
            $order, $this->_pm)) {
            // Payment completed
            $order->add_order_note(__('Payment accepted by SeQura', 'wc_sequra'));
            $this->add_payment_info_to_post_meta($order);
            $order->payment_complete();
        }
        exit();
    }

    function get_approval($order)
    {
        $client  = $this->getClient();
        $builder = $this->getBuilder($order);
        $builder->setPaymentMethod($this->_pm);
        if ($builder->sign($order->id) != $_REQUEST['signature'] &&
            $this->_pm->ipn
        ) {
            http_response_code(498);
            die('Not valid signature');
        }
        $data = $builder->build('confirmed');
        $uri  = $this->_pm->endpoint . '/' . $_REQUEST['order_ref'];
        $client->updateOrder($uri, $data);
        update_post_meta((int)$order->id, 'Transaction ID', $uri);
        update_post_meta((int)$order->id, 'Transaction Status', $client->getStatus());
        /*TODO: Store more information for later use in stats, like browser*/
        if ( ! $client->succeeded()) {
            http_response_code(410);
            die('Error: ' . $client->getJson());
        }

        return true;
    }

    function add_payment_info_to_post_meta($order)
    {
        if ($this->_pm->ipn) {
            update_post_meta((int)$order->id, 'Transaction ID', $_REQUEST['order_ref']);
            update_post_meta((int)$order->id, '_order_ref', $_REQUEST['order_ref']);
            update_post_meta((int)$order->id, '_product_code', $_REQUEST['product_code']);
            update_post_meta((int)$order->id, '_transaction_id', $_REQUEST['order_ref']);
            //@TODO
            //update_post_meta((int)$order->id, '_sequra_cart_ref', $sequra_cart_info['ref']);
        } else {
            $sequra_cart_info = WC()->session->get('sequra_cart_info');
            update_post_meta((int)$order->id, 'Transaction ID', WC()->session->get('sequraURI'));
            update_post_meta((int)$order->id, '_sequra_cart_ref', $sequra_cart_info['ref']);
        }
    }

    function receipt_page($order)
    {
        $order = new WC_Order($order);
        echo '<p>' . __('Thank you for your order, please click the button below to pay with SeQura.',
                'wc_sequra') . '</p>';
        $options = array('product' => $this->_pm->product);
        $this->get_identity_form($options, $order);
        require(SequraHelper::template_loader('payment_identification'));
    }

    /*
     * Test if order is virtual.
     *
     * @param WC_Order $order
     */

    function get_identity_form($options, $wc_order = null)
    {
        if (is_null($this->identity_form)) {
            $client  = $this->getClient();
            $builder = $this->getBuilder($wc_order);
            $builder->setPaymentMethod($this->_pm);
            try {
                $order = $builder->build();
                $client->startSolicitation($order);
                if ($client->succeeded()) {
                    $uri = $client->getOrderUri();
                    WC()->session->set('sequraURI', $uri);

                    $this->identity_form = $client->getIdentificationForm($uri, $options);
                }else{
                    if ($this->_pm->debug == 'yes') {
                        $this->_pm->log->add('sequra', $client->getJson());
                        $this->_pm->log->add('sequra', "Invalid payload:".$order);
                    };
                }
            } catch (Exception $e) {
                if ($this->_pm->debug == 'yes') {
                    $this->_pm->log->add('sequra', $e->getMessage());
                };
            }
        }

        return $this->identity_form;
    }


    /*
     * Test if order elgible for services
     * It must have 1 and no more serivces
     *
     * @param WC_Order $order
     */

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

    public function isElegibleForServiceSale()
    {
        $elegible       = false;
        $services_count = 0;
        foreach (WC()->cart->cart_contents as $values) {
            if (get_post_meta($values['product_id'], 'is_sequra_service', true) != 'no') {
                $services_count += $values['quantity'];
                $elegible       = $services_count == 1;
            }
        }

        return apply_filters('woocommerce_cart_is_elegible_for_service_sale', $elegible);
    }

    public function isElegibleForProductSale()
    {
        $elegible = true;
        //Only reject if all products are virtual (don't need shipping)
        if(!WC()->cart->needs_shipping()){
            $elegible = false;
        }
        return apply_filters('woocommerce_cart_is_elegible_for_product_sale', $elegible);
    }

}

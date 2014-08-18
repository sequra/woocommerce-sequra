<?php
/*
  Plugin Name: Pasarela de pago para SeQura
  Plugin URI: http://sequra.es/
  Description: Da la opción a tus clientes de recibir y luego pagar.
  Version: 0.1
  Author: Mikel Martin
  Author URI: http://SeQura.es/
 */

add_action('woocommerce_loaded', 'woocommerce_sequra_init', 100);

function woocommerce_sequra_init()
{
	load_plugin_textdomain('wc_sequra', false, dirname(plugin_basename(__FILE__)) . '/languages');

	/**
	 * Pasarela SeQura Gateway Class
	 * */
	class woocommerce_sequra extends WC_Payment_Gateway
	{
		const SEQURA_EMBED = 0;
		const SEQURA_POPUP = 1;
		static $endpoints = array(
			'https://live.sequrapi.com/orders',
			'https://sandbox.sequrapi.com/orders'
		);

		private $_client;
		private $_builder;

		public function __construct()
		{
			do_action('woocommerce_sequra_before_load', $this);
			$this->id = 'sequra';
			$this->dir = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/';
			//$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon.png';
			$this->supports = array(
				'products'
			);

			// Load the form fields
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Get setting values
			$this->enabled = $this->settings['enabled'];
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->merchantref = $this->settings['merchantref'];
			$this->user = $this->settings['user'];
			$this->password = $this->settings['password'];
			$this->enable_for_virtual = $this->settings['enable_for_virtual'];
			$this->enable_for_methods = $this->settings['enable_for_methods'];
			$this->enable_for_countries = array('ES');
			$this->debug = $this->settings['debug'];
			$this->max_amount = $this->settings['max_amount'];
			$this->env = $this->settings['env'];
			$this->endpoint = self::$endpoints[$this->env];
//            $this->stats = $this->settings['stats'];

			// Logs
			if ($this->debug == 'yes')
				$this->log = new WC_Logger();

			// Hooks
			if (version_compare(WOOCOMMERCE_VERSION, '2.0', '<')) {
				//BOF dejamos por compatibilidad con las versiones 1.6.x
				add_action('init', array(&$this, 'check_' . $this->id . '_resquest'));
				add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
				//EOF dejamos por compatibilidad con las versiones 1.6.x
			}
			add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_api_woocommerce_' . $this->id, array($this, 'check_' . $this->id . '_resquest'));
			do_action('woocommerce_sequra_loaded', $this);
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		function init_form_fields()
		{
			$shipping_methods = array();

			if (is_admin())
				foreach (WC()->shipping->load_shipping_methods() as $method) {
					$shipping_methods[$method->id] = $method->get_title();
				}
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'wc_sequra'),
					'type' => 'checkbox',
					'description' => __('Habilitar pasarela SeQura', 'wc_sequra'),
					'default' => 'no'
				),
				'title' => array(
					'title' => __('Title', 'wc_sequra'),
					'type' => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'wc_sequra'),
					'default' => __('Recibir antes de pagar', 'wc_sequra')
				),
				'description' => array(
					'title' => __('Description', 'wc_sequra'),
					'type' => 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'woothemes'),
					'default' => 'Paga después de recibir tu pedido con transferencia bancaria o ingreso en cuenta. Tendrás 7 días para pagar cuando tú quieras. La compra online 100% segura y sin gastos adicionales.'
				),
				'merchantref' => array(
					'title' => __('SeQura Merchant Reference', 'wc_sequra'),
					'type' => 'text',
					'description' => __('Id de comerciante proporcionado por SeQura.', 'wc_sequra'),
					'default' => ''
				),
				'user' => array(
					'title' => __('SeQura Username', 'wc_sequra'),
					'type' => 'text',
					'description' => __('Usuario proporcionado por SeQura.', 'wc_sequra'),
					'default' => ''
				),
				'password' => array(
					'title' => __('Password', 'wc_sequra'),
					'type' => 'text',
					'description' => __('Password proporcionada por SeQura.', 'wc_sequra'),
					'default' => ''
				),
				'max_amount' => array(
					'title' => __('Max order amount', 'wc_sequra'),
					'type' => 'number',
					'description' => __('SeQura payment method will be unavailable for orders beyond this amount', 'wc_sequra'),
					'default' => '400'
				),
				'enable_for_methods' => array(
					'title' => __('Enable for shipping methods', 'woocommerce'),
					'type' => 'multiselect',
					'class' => 'chosen_select',
					'css' => 'width: 450px;',
					'default' => '',
					'description' => __('If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce'),
					'options' => $shipping_methods,
					'desc_tip' => true,
					'custom_attributes' => array(
						'data-placeholder' => __('Select shipping methods', 'woocommerce')
					)
				),
				'enable_for_virtual' => array(
					'title' => __('Enable for virtual orders', 'woocommerce'),
					'label' => __('Enable SeQura if the order is virtual', 'wc_sequra'),
					'type' => 'checkbox',
					'default' => 'yes'
				),
				'env' => array(
					'title' => __('Entorno', 'wc_sequra'),
					'type' => 'select',
					'description' => __('While working in Sandbox the methos will only show to the following IP addresses.', 'wc_sequra'),
					'default' => '1',
					'desc_tip' => true,
					'options' => array(
						'1' => __('Sandbox - Pruebas', 'wc_sequra'),
						'0' => __('Live - Real', 'wc_sequra')
					)
				),
				'test_ips' => array(
					'title' => __('IPs for testing', 'wc_sequra'),
					'label' => '',
					'type' => 'test',
					'description' => __('When working is sandbox mode only these ips addresses will see the plugin', 'wc_sequra'),
					'desc_tip' => true,
					'default' => '80.25.103.126,46.4.22.81,' . $_SERVER['REMOTE_ADDR']
				),
				'debug' => array(
					'title' => __('Debugging', 'wc_sequra'),
					'label' => __('Modo debug', 'wc_sequra'),
					'type' => 'checkbox',
					'description' => __('Sólo para desarrolladores.', 'wc_sequra'),
					'default' => 'no'
				)
			);
			$this->form_fields = apply_filters('woocommerce_sequra_init_form_fields', $this->form_fields, $this);
		}

		/**
		 * Check If The Gateway Is Available For Use
		 *
		 * @return bool
		 */
		public function is_available()
		{
			$order = null;
			if (!$this->enable_for_virtual) {
				if (WC()->cart && !WC()->cart->needs_shipping()) {
					return false;
				}

				if (is_page(wc_get_page_id('checkout')) && 0 < get_query_var('order-pay')) {
					$order_id = absint(get_query_var('order-pay'));
					$order = new WC_Order($order_id);

					// Test if order needs shipping.
					$needs_shipping = false;

					if (0 < sizeof($order->get_items())) {
						foreach ($order->get_items() as $item) {
							$_product = $order->get_product_from_item($item);

							if ($_product->needs_shipping()) {
								$needs_shipping = true;
								break;
							}
						}
					}

					$needs_shipping = apply_filters('woocommerce_cart_needs_shipping', $needs_shipping);

					if ($needs_shipping) {
						return false;
					}
				}
			}

			if (!empty($this->enable_for_methods)) {

				// Only apply if all packages are being shipped via local pickup
				$chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

				if (isset($chosen_shipping_methods_session)) {
					$chosen_shipping_methods = array_unique($chosen_shipping_methods_session);
				} else {
					$chosen_shipping_methods = array();
				}

				$check_method = false;

				if (is_object($order)) {
					if ($order->shipping_method) {
						$check_method = $order->shipping_method;
					}

				} elseif (empty($chosen_shipping_methods) || sizeof($chosen_shipping_methods) > 1) {
					$check_method = false;
				} elseif (sizeof($chosen_shipping_methods) == 1) {
					$check_method = $chosen_shipping_methods[0];
				}

				if (!$check_method) {
					return false;
				}

				$found = false;

				foreach ($this->enable_for_methods as $method_id) {
					if (strpos($check_method, $method_id) === 0) {
						$found = true;
						break;
					}
				}

				if (!$found) {
					return false;
				}
			}

			if (
				$this->enable_for_countries &&
				!in_array(WC()->customer->get_shipping_country(), $this->enable_for_countries)
			) {
				return false;
			}
			if (WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total()) {
				return false;
			}
			return true;
		}

		/**
		 * There might be payment fields for SeQura, and we want to show the description if set.
		 * */
		function payment_fields()
		{
			if ('woocommerce_update_order_review' == $_REQUEST['action'])
				$this->identity_form = $this->get_identity_form();
			$payment_fields = array();
			$payment_fields = apply_filters('woocommerce_sequra_payment_fields', $payment_fields, $this);
			require($this->template_loader('payment_fields'));
		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 * */
		public function admin_options()
		{
			?>
			<h3><?php _e('Pasarela SeQura', 'wc_sequra'); ?></h3>
			<p><?php _e('La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá dar la opción de "Comprar ahora y pagar después" en su comercio. Para ello necesitará una cuenta de vendedor en SeQura.', 'wc_sequra'); ?></p>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->
		<?php
		}

		function get_identity_form()
		{
			$client = $this->getClient();
			$builder = $this->getBuilder();
			$order = $builder->build();
			$client->startSolicitation($order);
			if ($client->succeeded()) {
				$uri = $client->getOrderUri();
				return $client->getIdentificationForm($uri);
			}
		}

		public function getClient()
		{
			if ($this->_client instanceof SequraClient)
				return $this->_client;
			if (!class_exists('SequraClient')) require_once($this->dir . 'lib/SequraClient.php');
			SequraClient::$endpoint = $this->endpoint;
			SequraClient::$user = $this->user;
			SequraClient::$password = $this->password;
			SequraClient::$user_agent = 'cURL WooCommerce ' . WOOCOMMERCE_VERSION . ' php ' . phpversion();
			$this->_client = new SequraClient();

			return $this->_client;
		}

		public function getBuilder()
		{
			if ($this->_builder instanceof SequraClient)
				return $this->_builder;

			if (!class_exists('SequraBuilderAbstract')) require_once($this->dir . 'lib/SequraBuilderAbstract.php');
			if (!class_exists('SequraTempOrder')) require_once($this->dir . 'SequraTempOrder.php');
			if (!class_exists('SequraBuilderWC')) require_once($this->dir . 'SequraBuilderWC.php');
			$this->_builder = new SequraBuilderWC($this->merchantref);

			return $this->_builder;
		}

		/**
		 * Check for SeQura IPN Response
		 * */
		function check_sequra_resquest()
		{
		}

		/**
		 * Get SeQura Args for passing to PP
		 * */
		function get_sequra_args($order)
		{
			$ds_merchant_consumerlanguage = $this->_getLanguange();
			$ds_merchant_amount = round($order->get_total() * 100);
			$ds_merchant_currency = $this->currency;
			$ds_merchant_code = $this->merchant;
			$ds_merchant_user = $this->user;
			$ds_merchant_merchanturl = '';
			$ds_merchant_urlok = str_replace('https:', 'http:', add_query_arg(array('qbLstr' => 'notify', 'wc-api' => 'woocommerce_' . $this->id, 'rd' => '1'), home_url('/')));
			if (version_compare(WOOCOMMERCE_VERSION, '2.0', '<'))
				$ds_merchant_urlok = trailingslashit(home_url()) . '?qbLstr=notify&rd=1';

			$ds_merchant_order = str_pad(time(), 12, "0", STR_PAD_LEFT);
			$ds_merchant_data = $order->id;
			$ds_merchant_transactiontype = 0;

			$ds_merchant_titular = '';

			if ($this->notif_http == 'yes') {
				$ds_merchant_merchanturl = str_replace('https:', 'http:', add_query_arg(array('qbLstr' => 'notify', 'wc-api' => 'woocommerce_' . $this->id), home_url('/')));
				$ds_merchant_urlok = $this->get_return_url($order);
			}
			$ds_merchant_urlko = $order->get_cancel_order_url();
			if ($this->display_mode) {
				$ds_merchant_urlok = str_replace('https:', 'http:', add_query_arg(array('qbLstr' => 'notify', 'wc-api' => 'woocommerce_' . $this->id, 'rd' => 'ok', 'Ds_MerchantData' => $ds_merchant_data), home_url('/')));
				$ds_merchant_urlko = str_replace('https:', 'http:', add_query_arg(array('qbLstr' => 'notify', 'wc-api' => 'woocommerce_' . $this->id, 'rd' => 'ko', 'Ds_MerchantData' => $ds_merchant_data), home_url('/')));
			}

			$mensaje = $ds_merchant_amount . $ds_merchant_order . $ds_merchant_code . $ds_merchant_currency;
			if ($this->tipo_firma == 'a')
				$mensaje .= $ds_merchant_transactiontype . $ds_merchant_merchanturl;

			$signature = strtoupper(sha1($mensaje . $this->getClave()));

			$ds_merchant_productdescription = '';

			$sequra_args = array(
				'Ds_Merchant_Amount' => $ds_merchant_amount,
				'Ds_Merchant_Currency' => $ds_merchant_currency,
				'Ds_Merchant_Order' => $ds_merchant_order,
				'Ds_Merchant_ProductDescription' => $ds_merchant_productdescription,
				'Ds_Merchant_Titular' => $ds_merchant_titular,
				'Ds_Merchant_MerchantCode' => $ds_merchant_code,
				'Ds_Merchant_MerchantName' => utf8_decode($this->merchantname),
				'Ds_Merchant_UrlOK' => $ds_merchant_urlok,
				'Ds_Merchant_UrlKO' => $ds_merchant_urlko,
				'Ds_Merchant_ConsumerLanguage' => $ds_merchant_consumerlanguage,
				'Ds_Merchant_MerchantSignature' => $signature,
				'Ds_Merchant_Terminal' => $ds_merchant_user,
				'Ds_Merchant_MerchantData' => $ds_merchant_data,
				'Ds_Merchant_TransactionType' => $ds_merchant_transactiontype,
				'Ds_Merchant_PayMethods' => 'C'
			);
			if ($ds_merchant_merchanturl != '') {
				$sequra_args['Ds_Merchant_MerchantURL'] = $ds_merchant_merchanturl;
			}

			return apply_filters('woocommerce_sequra_args', $sequra_args, $this);
		}

		function getClave()
		{
			if ($this->env == 1)
				return 'qwertyasdf0123456789';
			return utf8_decode($this->password);
		}

		/**
		 * Generate the sequra button link
		 * */
		function generate_sequra_form($order_id)
		{
			global $woocommerce;

			$order = new WC_Order($order_id);

			$endpoint = $this->liveendpoint;
			if (1 == $this->env)
				$endpoint = $this->sandboxendpoint;

			$client = new SequraClient($this->user, $this->password, $endpoint);
			$builder = new SequraBuilderWC($this->merchantref, $order);
			$builder->setPaymentMethod($this);
			$client->startSolicitation($builder->build());
			if ($client->succeeded()) {
				$uri = $client->getOrderUri();
				$identity_form = $client->getIdentificationForm($uri);
				$html = $identity_form;
				$html = $sequra_args = apply_filters('woocommerce_sequra_build_html', $html, $identity_form);
				//if (self::SEQURA_EMBED == $this->display_mode) {}
				if (self::SEQURA_POPUP == $this->display_mode) {
					$html = '<div id="sequra_form">' . $html . '</div>';
					wc_enqueue_js('
            jQuery("body").block({
                    message: jQuery("#sequra_form").html(),
                    overlayCSS:
                    {
                        background: "#fff",
                        opacity: 0.6
                    },
                    css: {
                        padding:		20,
                        textAlign:	  "center",
                        color:		  "#555",
                        border:		 "3px solid #aaa",
                        backgroundColor:"#fff",
                        cursor:		 "wait",
                        lineHeight:		"32px"
                    }
                });');
				}
				return $html;
			}
		}

		function process_payment($order_id)
		{
			$order = new WC_Order($order_id);
			do_action('woocommerce_sequra_process_payment', $order, $this);
			$ret = array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			);
			return apply_filters('woocommerce_sequra_process_ payment_return', $ret, $this);
		}

		/**
		 * receipt_page
		 * */
		function receipt_page($order)
		{
			echo '<p>' . __('Thank you for your order, please click the button below to pay with your Credit Card.', 'wc_sequra') . '</p>';
			echo $this->generate_sequra_form($order);
		}

		/**
		 * Get post data if set
		 * */
		private function get_post($name)
		{
			if (isset($_POST[$name])) {
				return $_POST[$name];
			}
			return NULL;
		}

		static function order_actions($arr)
		{
			/*   global $theorder;
				if ($theorder->status != 'refunded' &&
					get_post_meta((int)$theorder->id, 'Ds_Order (Referencia)', true) != ''
				)
					$arr['refund'] = __('Refund payment in SeQura');
			*/
			return $arr;
		}

		public function template_loader($template)
		{
			if (file_exists(STYLESHEETPATH . '/' . WC_TEMPLATE_PATH . $template . '.php'))
				return STYLESHEETPATH . '/' . WC_TEMPLATE_PATH . $template . '.php';
			elseif (file_exists(TEMPLATEPATH . '/' . WC_TEMPLATE_PATH . $template . '.php'))
				return TEMPLATEPATH . '/' . WC_TEMPLATE_PATH . $template . '.php';
			elseif (file_exists(STYLESHEETPATH . '/' . $template . '.php'))
				return STYLESHEETPATH . '/' . $template . '.php';
			elseif (file_exists(TEMPLATEPATH . '/' . $template . '.php'))
				return TEMPLATEPATH . '/' . $template . '.php';
			else
				return WP_CONTENT_DIR . "/plugins/" . plugin_basename(dirname(__FILE__)) . '/templates/' . $template . '.php';
		}
	}

	/**
	 * Add the gateway to woocommerce
	 * */
	function add_sequra_gateway($methods)
	{
		$methods[] = 'woocommerce_sequra';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_sequra_gateway');

	//add_filter('woocommerce_order_actions', array('woocommerce_sequra', 'order_actions'), 10);
	/*function sequra_payment_refund($order)
	{
		$sequra = new woocommerce_sequra();
		$sequra->refund($order);
	}
	*/
	//add_action('woocommerce_order_action_refund', 'sequra_payment_refund', 10);

	/**
	 * Enqueue plugin style-file
	 */
	function sequra_add_stylesheet()
	{
		// Respects SSL, Style.css is relative to the current file
		wp_register_style('sequra-style', plugins_url('assets/css/style.css', __FILE__));
		wp_enqueue_style('sequra-style');
	}

	add_action('wp_enqueue_scripts', 'sequra_add_stylesheet');

	function sequra_add_cart_info_to_session()
	{
		$sequra_cart_info = WC()->session->get('sequra_cart_info');
		if ($sequra_cart_info)
			return $sequra_cart_info;
		$sequra_cart_info = array(
			'ref' => uniqid(),
			'created_at' => date('c')
		);
		WC()->session->set('sequra_cart_info', $sequra_cart_info);
	}

	add_action('woocommerce_add_to_cart', 'sequra_add_cart_info_to_session');
}
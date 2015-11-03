<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraPartPaymentGateway extends WC_Payment_Gateway {
	public function __construct() {
		do_action('woocommerce_sequra_pp_before_load', $this);
		$this->id = 'sequra_pp';
		//$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon.png';
		$this->supports = array(
			'products'
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->sequra = new SequraPaymentGateway();

		// Get setting values
		$this->enabled              = 'yes' == $this->settings['enabled'];
		$this->title                = $this->settings['title'];
		$this->description          = $this->settings['description'];
		$this->merchantref          = $this->sequra->settings['merchantref'];
		$this->user                 = $this->sequra->settings['user'];
		$this->password             = $this->sequra->settings['password'];
		$this->enable_for_countries = $this->sequra->enable_for_countries;
		$this->debug                = $this->sequra->settings['debug'];
		$this->max_amount           = $this->settings['max_amount'];
		$this->min_amount           = $this->settings['min_amount'];
		$this->env                  = $this->sequra->env;
		$this->fee                  = $this->sequra->fee;
		$this->endpoint             = $this->sequra->endpoint;
		$this->helper               = new SequraHelper($this);
		$this->has_fields           = true;
		$this->pp_product           = 'pp2';//not an option
//            $this->stats = $this->settings['stats'];
		$this->helper = new SequraHelper($this);

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
		add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_order_totals'), 999);
		do_action('woocommerce_sequra_pp_loaded', $this);
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	function init_form_fields() {
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
				'default' => __('Fraccionar tu pago.', 'wc_sequra')
			),
			'description' => array(
				'title' => __('Description', 'wc_sequra'),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', 'woothemes'),
				'default' => __('Selecciona una de las opciones. Todos los costes incluidos.', 'wc_sequra')
			),
			'max_amount' => array(
				'title' => __('Max order amount', 'wc_sequra'),
				'type' => 'number',
				'description' => __('SeQura payment method will be unavailable for orders beyond this amount', 'wc_sequra'),
				'default' => '400'
			),
			'min_amount' => array(
				'title' => __('Min order amount', 'wc_sequra'),
				'type' => 'number',
				'description' => __('SeQura PP payment method will be unavailable for orders below this amount', 'wc_sequra'),
				'default' => '50'
			)
		);
		$this->form_fields = apply_filters('woocommerce_sequra_pp_init_form_fields', $this->form_fields, $this);
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @return bool
	 */
	public function is_available() {
		if (!$this->enabled)
			return false;

		$order           = null;
		$sequra_settings = get_option($this->plugin_id . 'sequra_settings', null);
		if (
			$this->enable_for_countries &&
			!in_array(WC()->customer->get_shipping_country(), $this->enable_for_countries)
		) {
			return false;
		}
		if (WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total()) {
			return false;
		}
		if (WC()->cart && 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total()) {
			return false;
		}
		if (1 == $this->env && '' != $sequra_settings['test_ips']) { //Sandbox
			$ips = explode(',', $sequra_settings['test_ips']);
			return in_array($_SERVER['REMOTE_ADDR'], $ips);
		}
		return true;
	}

	/**
	 * Get radio button with credit agreements options
	 */
	public function credit_agreement_field() {
		$this->credit_agreements = $this->helper->get_credit_agreements($this->get_order_total());
		$options                 = array();
		$i                       = 0;
		$default                 = 0;
		foreach ($this->credit_agreements[$this->pp_product] as $ca) {
			$options[] = sprintf(__('<b>%s</b> mensualidades de <b>%s</b>', 'wc_sequra'), $ca['instalment_count'], $ca['instalment_total']['string']);
			if ($ca['default']) $default = $i;
			$i++;
		}
		return array(
			'selected_ca' => array(
				'title' => __('Fracciona tu pago', 'wc_sequra'),
				'type' => 'radio',
				'description' => __('This controls the title which the user sees during checkout.', 'wc_sequra'),
				'default' => $default,
				'options' => $options
			)
		);
	}

	/**
	 * There might be payment fields for SeQura, and we want to show the description if set.
	 * */
	function payment_fields() {
		$payment_fields = $this->credit_agreement_field();
		$payment_fields = apply_filters('woocommerce_sequra_pp_payment_fields', $payment_fields, $this);
		require($this->helper->template_loader('partpayment_fields'));
		?>
		<script>
			jQuery('form.checkout')
				.off('checkout_place_order_sequra')
				.on('checkout_place_order_sequra', function () {
					console.log('checkout_place_order_sequra');
					sequraButton = jQuery('#sequra-identification .sq_submit input[type=submit]');
					sequraButton.click();
					return false;
				});
			function shop_callback_sequra_approved() {
				console.log(shop_callback_sequra_approved);
				jQuery('form.checkout').off('checkout_place_order_sequra').submit();
			}
		</script><?php
		wc_enqueue_js("
		(function( $ ) {
			'use strict';
			$('body').on('change',  'input[name=\"billing_phone\"]', function() { $('body').trigger('update_checkout'); });
		})(jQuery);
		");
		if ($this->fee > 0)
			wc_enqueue_js("
		(function( $ ) {
			'use strict';
			$('body').on('change', 'input[name=\"payment_method\"]', function() { $('body').trigger('update_checkout'); });
		})(jQuery);
		");
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
		<h3><?php _e('Pasarela SeQura', 'wc_sequra'); ?></h3>
		<p><?php _e('La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá dar la opción de "Comprar ahora y pagar después" en su comercio. Para ello necesitará una cuenta de vendedor en SeQura.', 'wc_sequra'); ?></p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
	}

	function process_payment($order_id) {
		$order = new WC_Order($order_id);
		if (isset($_REQUEST['selected_ca'])) {
			WC()->session->set('selected_ca', $_REQUEST['selected_ca']);
		}
		do_action('woocommerce_sequra_pp_process_payment', $order, $this);
		return array(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		);
		return apply_filters('woocommerce_sequra_pp_process_payment_return', $ret, $this);
	}

	/**
	 * Add extra charge to cart totals
	 *
	 * @param double $totals
	 * return double
	 */
	public function calculate_order_totals($cart) {
		if ((isset($_POST['payment_method']) && $_POST['payment_method'] != $this->id) ||
			!defined('WOOCOMMERCE_CHECKOUT') ||
			!($this->fee > 0)
		) {
			return;
		}
		$cart->add_fee(__('Recargo "compra ahora, paga después"'), $this->fee, false);
	}

	/**
	 * receipt_page
	 * */
	function receipt_page($order) {
		global $woocommerce;
		$order = new WC_Order($order);
		echo '<p>' . __('Thank you for your order, please click the button below to pay with your Credit Card.', 'wc_sequra') . '</p>';
		$options             = array('product' => $this->pp_product);
		$this->identity_form = $this->helper->get_identity_form($options, $order);
		$back                = $woocommerce->cart->get_checkout_url();
		$extra_fields        = apply_filters('woocommerce_sequra_payment_extra_fields', array(), $this);
		$total_price         = round($order->get_total() * 100);
		$instalment_fee      = ($total_price < 20000 ? 300 : 500);
		$symbol_left         = '';
		$symbol_right        = '';
		$selected_ca         = WC()->session->get('selected_ca');
		switch (get_option('woocommerce_currency_pos')) {
			case 'left';
				$symbol_left = get_woocommerce_currency_symbol();
				break;
			case 'left_space';
				$symbol_left = get_woocommerce_currency_symbol() . ' ';
				break;
			case 'right';
				$symbol_right = get_woocommerce_currency_symbol();
				break;
			case 'right_space';
				$symbol_right = ' ' . get_woocommerce_currency_symbol();
				break;
		}
		$decimal_places  = get_option('woocommerce_price_num_decimals');
		$decimal_point   = get_option('woocommerce_price_thousand_sep');
		$thousands_point = get_option('woocommerce_price_decimal_sep');

		require($this->helper->template_loader('partpayment_identification'));
	}

	function setEndpoint($url) {
		$this->endpoint = $url;
	}

	function merchant($order) {
		return array(
			'approved_url' => str_replace('https:', 'http:', add_query_arg(array('order' => $order->id, 'wc-api' => 'woocommerce_' . $this->id, 'result' => 0), home_url('/'))),
			'abort_url' => $order->get_cancel_order_url()
		);
	}

	function check_sequra_pp_resquest() {
		$order = new WC_Order($_REQUEST['order']);
		$url   = $order->get_cancel_order_url();
		do_action('woocommerce_sequra_pp_process_payment', $order, $this);
		if ($approval = apply_filters('woocommerce_sequra_pp_process_payment', $this->helper->get_approval($order), $order, $this)) {
			// Payment completed
			$order->add_order_note(__('Payment accepted by SeQura', 'wc_sequra'));
			$sequra_cart_info = WC()->session->get('sequra_cart_info');
			update_post_meta((int)$order->id, 'Transaction ID', WC()->session->get('sequraURI'));
			update_post_meta((int)$order->id, '_sequra_cart_ref', $sequra_cart_info['ref']);
			$order->payment_complete();
			$url = $this->get_return_url($order);
		}
		wp_redirect($url, 303);
		exit();
	}
}

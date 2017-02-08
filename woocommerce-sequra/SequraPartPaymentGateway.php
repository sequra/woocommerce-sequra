<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraPartPaymentGateway extends WC_Payment_Gateway {
	public function __construct() {
		do_action( 'woocommerce_sequra_pp_before_load', $this );
		$this->id                 = 'sequra_pp';
		$this->method_title       = __( 'Fraccionar pago', 'wc_sequra' );
		$this->method_description = __( 'Allows payments part payments, service ofered by SeQura.', 'wc_sequra' );
		//$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon.png';
		$this->supports = array(
			'products'
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$sequra_settings = array_map(
			array( $this, 'format_settings' ),
			get_option( 'woocommerce_sequra_settings', null )
		);

		// Get setting values
		$this->enabled              = 'yes' == $this->settings['enabled'];
		$this->title                = $this->settings['title'];
		$this->merchantref          = $sequra_settings['merchantref'];
		$this->user                 = $sequra_settings['user'];
		$this->password             = $sequra_settings['password'];
		$this->enable_for_countries = array( 'ES' );
		$this->debug                = $sequra_settings['debug'];
		$this->max_amount           = $this->settings['max_amount'];
		$this->min_amount           = $this->settings['min_amount'];
		$this->env                  = $sequra_settings['env'];
		$this->endpoint             = SequraPaymentGateway::$endpoints[ $this->env ];
		$this->has_fields           = true;
		$this->pp_product           = 'pp3';//not an option
		$this->pp_cost_url          = 'https://' .
		                              ( $this->env ? 'sandbox' : 'live' ) .
		                              '.sequracdn.com/scripts/' .
		                              $this->merchantref . '/' .
		                              $sequra_settings['assets_secret'] .
		                              '/pp3_pp5_cost.js';
		$this->ipn                  = 1;
//            $this->stats = $this->settings['stats'];
		$this->helper = new SequraHelper( $this );

		// Logs
		if ( $this->debug == 'yes' ) {
			$this->log = new WC_Logger();
		}

		// Hooks
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			//BOF dejamos por compatibilidad con las versiones 1.6.x
			add_action( 'init', array( &$this, 'check_' . $this->id . '_resquest' ) );
			add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
			//EOF dejamos por compatibilidad con las versiones 1.6.x
		}
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this, 'check_' . $this->id . '_resquest' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_order_totals' ), 999 );
		do_action( 'woocommerce_sequra_pp_loaded', $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	function init_form_fields() {
		$shipping_methods = array();

		if ( is_admin() ) {
			foreach ( WC()->shipping->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_title();
			}
		}
		$this->form_fields = array(
			'enabled'    => array(
				'title'       => __( 'Enable/Disable', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Habilitar pasarela SeQura', 'wc_sequra' ),
				'default'     => 'no'
			),
			'title'      => array(
				'title'       => __( 'Title', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_sequra' ),
				'default'     => __( 'Fracciona tu pago.', 'wc_sequra' )
			),
			'max_amount' => array(
				'title'       => __( 'Max order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __( 'SeQura payment method will be unavailable for orders beyond this amount', 'wc_sequra' ),
				'default'     => '10000'
			),
			'min_amount' => array(
				'title'       => __( 'Min order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __( 'SeQura PP payment method will be unavailable for orders below this amount', 'wc_sequra' ),
				'default'     => '50'
			)
		);
		$this->form_fields = apply_filters( 'woocommerce_sequra_pp_init_form_fields', $this->form_fields, $this );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! $this->enabled ) {
			return false;
		}

		$order           = null;
		$sequra_settings = get_option( $this->plugin_id . 'sequra_settings', null );
		if (
			$this->enable_for_countries &&
			! in_array( WC()->customer->get_shipping_country(), $this->enable_for_countries )
		) {
			return false;
		}
		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			return false;
		}
		if ( WC()->cart && 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total() ) {
			return false;
		}
		if ( 1 == $this->env && '' != $sequra_settings['test_ips'] ) { //Sandbox
			$ips = explode( ',', $sequra_settings['test_ips'] );

			return in_array( $_SERVER['REMOTE_ADDR'], $ips );
		}

		return true;
	}

	/**
	 * Get radio button with credit agreements options
	 */
	public function credit_agreement_field() {
		$this->credit_agreements = $this->helper->get_credit_agreements( $this->get_order_total() );
		$options                 = array();
		$i                       = 0;
		$default                 = WC()->session->get( 'selected_ca' );
		foreach ( $this->credit_agreements[ $this->pp_product ] as $ca ) {
			$options[ $i ] = sprintf( __( '<b>%s</b> mensualidades de <b>%s</b>', 'wc_sequra' ), $ca['instalment_count'], $ca['instalment_total']['string'] );
			if ( $ca['default'] && ! $default ) {
				$default = $i;
			}
			$i ++;
		}

		return array(
			'selected_ca' => array(
				'title'   => __( 'Fracciona tu pago', 'wc_sequra' ),
				'type'    => 'radio',
				'default' => $default,
				'options' => $options
			)
		);
	}

	/**
	 * There might be payment fields for SeQura, and we want to show the description if set.
	 * */
	function payment_fields() {
		wp_enqueue_script( 'sequra-pp-cost-js', $this->pp_cost_url );
		$payment_fields = apply_filters( 'woocommerce_sequra_pp_payment_fields', $this->credit_agreement_field(), $this );
		require( $this->helper->template_loader( 'partpayment_fields' ) );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
      <h3><?php _e( 'Pasarela SeQura', 'wc_sequra' ); ?></h3>
      <p><?php _e( 'La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá dar la opción de "Recibe primero, paga después" en su comercio. Para ello necesitará una cuenta de vendedor en SeQura.', 'wc_sequra' ); ?></p>
      <table class="form-table">
		  <?php $this->generate_settings_html(); ?>
      </table><!--/.form-table-->
		<?php
	}

	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( isset( $_REQUEST['selected_ca'] ) ) {
			WC()->session->set( 'selected_ca', $_REQUEST['selected_ca'] );
		}
		do_action( 'woocommerce_sequra_pp_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);

		return apply_filters( 'woocommerce_sequra_pp_process_payment_return', $ret, $this );
	}

	/**
	 * receipt_page
	 * */
	function receipt_page( $order ) {
		global $woocommerce;
		$order = new WC_Order( $order );
		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with SeQura.', 'wc_sequra' ) . '</p>';
		$options             = array( 'product' => $this->pp_product );
		$this->identity_form = $this->helper->get_identity_form( $options, $order );
		$back                = $woocommerce->cart->get_checkout_url();
		$extra_fields        = apply_filters( 'woocommerce_sequra_payment_extra_fields', array(), $this );
		$total_price         = round( $order->get_total() * 100 );
		$symbol_left         = '';
		$symbol_right        = '';
		$selected_ca         = WC()->session->get( 'selected_ca' );
		switch ( get_option( 'woocommerce_currency_pos' ) ) {
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
		$decimal_places  = get_option( 'woocommerce_price_num_decimals' );
		$decimal_point   = get_option( 'woocommerce_price_thousand_sep' );
		$thousands_point = get_option( 'woocommerce_price_decimal_sep' );

		require( $this->helper->template_loader( 'payment_identification' ) );
	}

	function setEndpoint( $url ) {
		$this->endpoint = $url;
	}

	function check_sequra_pp_resquest() {
		$this->helper->check_response( $this );
	}
}

<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraPartPaymentGateway extends WC_Payment_Gateway {

	public function __construct() {
		do_action( 'woocommerce_sequra_pp_before_load', $this );
		$this->id                 = 'sequra_pp';
		$this->icon               = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/assets/img/small-logo.png';
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
		$this->coresettings = get_option( 'woocommerce_sequra_settings', array() );
		// Get setting values
		$this->enabled              = $this->settings['enabled'];
		$this->title                = $this->settings['title'];
		$this->enable_for_countries = array( 'ES' );
		$this->max_amount           = $this->settings['max_amount'];
		$this->min_amount           = $this->settings['min_amount'];
		$this->has_fields           = true;
		$this->price_css_sel        = htmlspecialchars_decode( $this->settings['price_css_sel'] );
		$this->dest_css_sel         = htmlspecialchars_decode( $this->settings['dest_css_sel'] );
		$this->product              = 'pp3';//not an option
		$this->pp_cost_url          = 'https://' .
		                              ( $this->coresettings['env'] ? 'sandbox' : 'live' ) .
		                              '.sequracdn.com/scripts/' .
		                              $this->coresettings['merchantref'] . '/' .
		                              $this->coresettings['assets_secret'] .
		                              '/pp3_pp5_cost.js';
		$this->env                  = $this->coresettings['env'];
		$this->helper               = new SequraHelper( $this );

		// Logs
		if ( $this->coresettings['debug'] == 'yes' ) {
			$this->log = new WC_Logger();
		}

		// Hooks
		add_action( 'woocommerce_receipt_' . $this->id, array( $this->helper, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this->helper, 'check_response' ) );
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
			'enabled'       => array(
				'title'       => __( 'Enable/Disable', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Habilitar pasarela SeQura', 'wc_sequra' ),
				'default'     => 'no'
			),
			'title'         => array(
				'title'       => __( 'Title', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_sequra' ),
				'default'     => __( 'Fracciona tu pago.', 'wc_sequra' )
			),
			'max_amount'    => array(
				'title'       => __( 'Max order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __( 'SeQura payment method will be unavailable for orders beyond this amount', 'wc_sequra' ),
				'default'     => '10000'
			),
			'min_amount'    => array(
				'title'       => __( 'Min order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __( 'SeQura PP payment method will be unavailable for orders below this amount', 'wc_sequra' ),
				'default'     => '50'
			),
			'price_css_sel' => array(
				'title'       => __( 'CSS price selector', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'CSS selector to get the price for installment simulator', 'wc_sequra' ),
				'default'     => '.summary .price>.amount,.summary .price ins .amount'
			),
			'dest_css_sel'  => array(
				'title'       => __( 'CSS selector for simulator', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'CSS after which the simulator will be draw. if just showing it below the prices is not good. Usually empty should be fine', 'wc_sequra' ),
				'default'     => ''
			),
		);
		$this->form_fields = apply_filters( 'woocommerce_sequra_pp_init_form_fields', $this->form_fields, $this );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( $this->enabled !== 'yes' ) {
			return false;
		} else if ( is_admin() ) {
			return true;
		}

		if ( is_page( wc_get_page_id( 'checkout' ) ) && ! $this->is_available_in_checkout() ) {
			return false;
		}

		if ( is_product() && ! $this->is_available_in_product_page() ) {
			return false;
		}

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
		if ( 1 == $this->env && '' != $this->coresettings['test_ips'] ) { //Sandbox
			$ips = explode( ',', $this->coresettings['test_ips'] );

			return in_array( $_SERVER['REMOTE_ADDR'], $ips );
		}

		return true;
	}

	function is_available_in_checkout() {
		if ( $this->coresettings['enable_for_virtual'] == 'yes' ) {
			if ( ! $this->helper->isElegibleForServiceSale() ) {
				return false;
			}
		} else if ( ! WC()->cart->needs_shipping() ) {
			return false;
		}

		return true;
	}

	function is_available_in_product_page() {
		global $product;
		if ( $this->coresettings['enable_for_virtual'] == 'yes' ) {
			//return get_post_meta( $product->id, 'is_sequra_service', true ) != 'no';
			return true;//Non-services can be purchased too but not alone.
		} else if ( ! $product->needs_shipping() ) {
			return false;
		}

		return true;
	}


	/**
	 * There might be payment fields for SeQura, and we want to show the description if set.
	 * */
	function payment_fields() {
		require( SequraHelper::template_loader( 'partpayment_fields' ) );
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
		do_action( 'woocommerce_sequra_pp_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);

		return apply_filters( 'woocommerce_sequra_pp_process_payment_return', $ret, $this );
	}
}

<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraInvoicePaymentGateway extends WC_Payment_Gateway {

	public function __construct() {
		do_action( 'woocommerce_sequra_before_load', $this );
		$this->id   = 'sequra_i';
		$this->icon = sequra_get_script_basesurl() . 'images/small-logo.png';

		$this->method_title       = __( 'Recibe primero, paga después', 'wc_sequra' );
		$this->method_description = __( 'Allows payments by \'Recibe primero, paga después\', service ofered by SeQura.',
			'wc_sequra' );
		$this->supports           = array(
			'products'
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->coresettings = get_option( 'woocommerce_sequra_settings', array() );

		//Not available for services
		if ( $this->coresettings['enable_for_virtual'] == 'yes' ) {
			$this->enabled = false;

			return;
		}

		$this->enabled = $this->settings['enabled'];

		// Get setting values
		if ( ! is_admin() || basename( $_SERVER['SCRIPT_NAME'] ) == 'admin-ajax.php' ) {
			$this->title = $this->settings['title'];
		} else {
			$this->title = strip_tags( $this->settings['title'] );
		}
		$this->max_amount = $this->settings['max_amount'];
		$this->fee        = (float) $this->settings['fee'];
		//$this->days_after           = (int) $this->settings['days_after'];
		$this->has_fields           = true;
		$this->enable_for_countries = array( 'ES' );
		$this->env                  = $this->coresettings['env'];
		$this->helper               = new SequraHelper( $this );
		$this->product              = 'i1';//not an option

		// Logs
		if ( $this->coresettings['debug'] == 'yes' ) {
			$this->log = new WC_Logger();
		}

		add_action( 'woocommerce_receipt_' . $this->id, array( $this->helper, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this->helper, 'check_response' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_order_totals' ), 999 );
		do_action( 'woocommerce_sequra_loaded', $this );
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
				'default'     => __( 'Recibe primero, paga después', 'wc_sequra' )
			),
			'max_amount' => array(
				'title'       => __( 'Max order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __( 'SeQura payment method will be unavailable for orders beyond this amount',
					'wc_sequra' ),
				'default'     => '400'
			),
            'widget_theme'  => array(
                'title'       => __('Widget theme', 'wc_sequra'),
                'type'        => 'text',
                'description' => __('Widget theme: white, default...', 'wc_sequra'),
                'default'     => 'white'
            )
		);
		$this->form_fields = apply_filters( 'woocommerce_sequra_init_form_fields', $this->form_fields, $this );
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

		if ( ! is_admin() || basename( $_SERVER['SCRIPT_NAME'] ) == 'admin-ajax.php' ) {
			if ( ! empty( $this->coresettings['enable_for_methods'] ) ) {

				// Only apply if all packages are being shipped via local pickup
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( isset( $chosen_shipping_methods_session ) ) {
					$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
				} else {
					$chosen_shipping_methods = array();
				}

				$check_method = false;

				if ( is_object( $order ) ) {
					if ( $order->shipping_method ) {
						$check_method = $order->shipping_method;
					}

				} elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
					$check_method = false;
				} elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
					$check_method = $chosen_shipping_methods[0];
				}

				if ( ! $check_method ) {
					return false;
				}

				$found = false;

				foreach ( $this->coresettings['enable_for_methods'] as $method_id ) {
					if ( strpos( $check_method, $method_id ) === 0 ) {
						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					return false;
				}
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
		}
		if ( 1 == $this->env && '' != $this->coresettings['test_ips'] ) { //Sandbox
			$ips = explode( ',', $this->coresettings['test_ips'] );

			return in_array( $_SERVER['REMOTE_ADDR'], $ips );
		}

		return true;
	}

	function is_available_in_checkout() {
		return
			WC()->cart &&
			WC()->cart->needs_shipping() &&
			$this->coresettings['enable_for_virtual'] != 'yes';
	}

	function is_available_in_product_page() {
		global $product;
		if ( ! $product->needs_shipping() ) {
			return false;
		}

		return true;
	}


	/**
	 * There might be payment fields for SeQura, and we want to show the description if set.
	 * */
	function payment_fields() {
		$payment_fields = apply_filters( 'woocommerce_sequra_payment_fields', array(), $this );
		require( SequraHelper::template_loader( 'payment_fields' ) );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
        <h3><?php _e( 'Recibe primero, paga edspués', 'wc_sequra' ); ?></h3>
        <p><?php _e( 'La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá dar la opción de "Recibe primero, paga después" en su comercio. Para ello necesitará una cuenta de vendedor en SeQura.',
				'wc_sequra' ); ?></p>
        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
        </table><!--/.form-table-->
		<?php
	}

	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		do_action( 'woocommerce_sequra_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);

		return apply_filters( 'woocommerce_sequra_process_payment_return', $ret, $this );
	}

	/**
	 * Add extra charge to cart totals
	 *
	 * @param double $totals
	 * return double
	 */
	public function calculate_order_totals( $cart ) {
		if (
			! isset( $_POST['payment_method'] ) ||
			$_POST['payment_method'] != $this->id ||
			! defined( 'WOOCOMMERCE_CHECKOUT' ) ||
			! ( $this->fee > 0 )
		) {
			return;
		}
		$cart->add_fee( __( 'Recargo "Recibe primero, paga después"' ), $this->fee, false );
	}

	public function available_products($products){
        $products[]='i1';
	    return $products;
    }
}
add_filter('sequra_available_products',array( 'SequraInvoicePaymentGateway', 'available_products' ));

<?php

/**
 * Pasarela SeQura Gateway Class
 * */
class SequraPaymentGateway extends WC_Payment_Gateway {
	public static $endpoints = array(
		'https://live.sequrapi.com/orders',
		'https://sandbox.sequrapi.com/orders'
	);

	public function __construct() {
		do_action( 'woocommerce_sequra_before_load', $this );
		$this->id = 'sequra';
		//$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon.png';
		$this->supports = array(
			'products'
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->enabled = 'yes' == $this->settings['enabled'];
		if ( ! is_admin() || basename( $_SERVER['SCRIPT_NAME'] ) == 'admin-ajax.php' ) {
			$this->title = $this->settings['title'];
		} else {
			$this->title = strip_tags( $this->settings['title'] );
		}
		$this->merchantref          = $this->settings['merchantref'];
		$this->user                 = $this->settings['user'];
		$this->password             = $this->settings['password'];
		$this->enable_for_virtual   = $this->settings['enable_for_virtual'];
		$this->enable_for_methods   = $this->settings['enable_for_methods'];
		$this->enable_for_countries = array( 'ES' );
		$this->debug                = $this->settings['debug'];
		$this->max_amount           = $this->settings['max_amount'];
		$this->env                  = $this->settings['env'];
		$this->fee                  = (float) $this->settings['fee'];
		$this->days_after           = (int) $this->settings['days_after'];
		$this->has_fields           = true;		
		$this->endpoint             = self::$endpoints[ $this->env ];
		$this->helper               = new SequraHelper( $this );
		$this->identity_form        = null;
		$this->ipn                  = 1;

//            $this->stats = $this->settings['stats'];

		// Logs
		if ( $this->debug == 'yes' ) {
			$this->log = new WC_Logger();
		}

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this, 'check_' . $this->id . '_resquest' ) );
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
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Habilitar pasarela SeQura', 'wc_sequra' ),
				'default'     => 'no'
			),
			'title'              => array(
				'title'       => __( 'Title', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_sequra' ),
				'default'     => __( 'Recibe primero, paga después', 'wc_sequra' )
			),
			'merchantref'        => array(
				'title'       => __( 'SeQura Merchant Reference', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Id de comerciante proporcionado por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'user'               => array(
				'title'       => __( 'SeQura Username', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Usuario proporcionado por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'password'           => array(
				'title'       => __( 'Password', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Password proporcionada por SeQura.', 'wc_sequra' ),
				'default'     => ''
			),
			'max_amount'         => array(
				'title'       => __( 'Max order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __( 'SeQura payment method will be unavailable for orders beyond this amount', 'wc_sequra' ),
				'default'     => '400'
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce' ),
				'options'           => $shipping_methods,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'woocommerce' )
				)
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Enable for virtual orders', 'woocommerce' ),
				'label'   => __( 'Enable SeQura if the order is virtual', 'wc_sequra' ),
				'type'    => 'checkbox',
				'default' => 'yes'
			),
			'fee'                => array(
				'title'       => __( 'Fee', 'wc_sequra' ),
				'description' => __( 'fee applied to this payment method' ),
				'type'        => 'text',
				'default'     => '1.95'
			),
			'days_after'         => array(
				'title'       => __( 'Payment term', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'days left after delivery date to make payment' ),
				'default'     => '7'
			),
			'env'                => array(
				'title'       => __( 'Entorno', 'wc_sequra' ),
				'type'        => 'select',
				'description' => __( 'While working in Sandbox the methos will only show to the following IP addresses.', 'wc_sequra' ),
				'default'     => '1',
				'desc_tip'    => true,
				'options'     => array(
					'1' => __( 'Sandbox - Pruebas', 'wc_sequra' ),
					'0' => __( 'Live - Real', 'wc_sequra' )
				)
			),
			'test_ips'           => array(
				'title'       => __( 'IPs for testing', 'wc_sequra' ),
				'label'       => '',
				'type'        => 'test',
				'description' => __( 'When working is sandbox mode only these ips addresses will see the plugin', 'wc_sequra' ),
				'desc_tip'    => true,
				'default'     => '54.76.175.81,' . $_SERVER['REMOTE_ADDR']
			),
			'debug'              => array(
				'title'       => __( 'Debugging', 'wc_sequra' ),
				'label'       => __( 'Modo debug', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Sólo para desarrolladores.', 'wc_sequra' ),
				'default'     => 'no'
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
		if ( ! $this->enabled ) {
			return false;
		}

		$order = null;
		if ( ! $this->enable_for_virtual ) {
			if ( WC()->cart && ! WC()->cart->needs_shipping() ) {
				return false;
			}

			if ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = new WC_Order( $order_id );

				// Test if order needs shipping.
				$needs_shipping = false;

				if ( 0 < sizeof( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $order->get_product_from_item( $item );

						if ( $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}

				$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

				if ( $needs_shipping ) {
					return false;
				}
			}
		}
		if ( ! is_admin() || basename( $_SERVER['SCRIPT_NAME'] ) == 'admin-ajax.php' ) {
			if ( ! empty( $this->enable_for_methods ) ) {

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

				foreach ( $this->enable_for_methods as $method_id ) {
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
		if ( 1 == $this->env && '' != $this->settings['test_ips'] ) { //Sandbox
			$ips = explode( ',', $this->settings['test_ips'] );

			return in_array( $_SERVER['REMOTE_ADDR'], $ips );
		}

		return true;
	}

	/**
	 * There might be payment fields for SeQura, and we want to show the description if set.
	 * */
	function payment_fields() {
		$payment_fields = apply_filters( 'woocommerce_sequra_payment_fields', array(), $this );
		require( $this->helper->template_loader( 'payment_fields' ) );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
		<h3><?php _e( 'Pasarela SeQura', 'wc_sequra' ); ?></h3>
		<p><?php _e( 'La pasarela <a href="https://sequra.es/">SeQura</a> para Woocommerce le permitirá dar la opción de "Comprar ahora y pagar después" en su comercio. Para ello necesitará una cuenta de vendedor en SeQura.', 'wc_sequra' ); ?></p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
	}

	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		do_action( 'woocommerce_sequra_process_payment', $order, $this );
		if ( is_user_logged_in() && '' == get_user_meta( get_current_user_id(), 'sequra_dob', true ) ) {
			add_user_meta( get_current_user_id(),
				'sequra_dob',
				(int) $_POST['dob_year'] . '-' .
				sprintf( '%02d', (int) $_POST['dob_month'] ) . '-' .
				sprintf( '%02d', (int) $_POST['dob_day'] )
			);
		}
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
		$cart->add_fee( __( 'Recargo "compra ahora, paga después"' ), $this->fee, false );
	}

	/**
	 * receipt_page
	 * */
	function receipt_page( $order ) {
		if ( ! is_null( $this->identity_form ) ) {
			return;
		}
		global $woocommerce;
		$order = new WC_Order( $order );
		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with SeQura.', 'wc_sequra' ) . '</p>';
		$options             = array( 'product' => 'i1' );
		$this->identity_form = $this->helper->get_identity_form( $options, $order );
		$back                = $woocommerce->cart->get_checkout_url();
		$extra_fields        = apply_filters( 'woocommerce_sequra_payment_extra_fields', array(), $this );
		$total_price         = round( $order->get_total() * 100 );
		require( $this->helper->template_loader( 'payment_identification' ) );
	}

	function setEndpoint( $url ) {
		$this->endpoint = $url;
	}

	function check_sequra_resquest() {
		$order = new WC_Order( $_REQUEST['order'] );
		$url   = $order->get_cancel_order_url();
		do_action( 'woocommerce_sequra_process_payment', $order, $this );
		if ( $approval = apply_filters( 'woocommerce_sequra_process_payment', $this->helper->get_approval( $order ), $order, $this ) ) {
			// Payment completed
			$order->add_order_note( __( 'Payment accepted by SeQura', 'wc_sequra' ) );
			$this->helper->add_payment_info_to_post_meta( $order );
			$order->payment_complete();
			$url = $this->get_return_url( $order );
		}
		if ( ! $this->ipn ) {
			wp_redirect( $url, 303 );
		}
		exit();
	}
}

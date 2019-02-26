<?php
/**
 * Sequra Invoice Gateway.
 *
 * @package woocommerce-sequra
 */

/**
 * Sequra Invoice Gateway.
 */
class SequraInvoiceGateway extends WC_Payment_Gateway {
	/**
	 * Undocumented function
	 */
	public function __construct() {
		do_action( 'woocommerce_sequra_before_load', $this );
		$this->id   = 'sequra_i';
		$this->icon = sequra_get_script_basesurl() . 'images/badges/invoicing.svg';

		$this->method_title       = __( 'Compra primero, paga después', 'wc_sequra' );
		$this->method_description = __(
			'Allows payments by \'Compra primero, paga después\', service ofered by Sequra.',
			'wc_sequra'
		);
		$this->supports           = array(
			'products',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->core_settings = get_option( 'woocommerce_sequra_settings', SequraHelper::get_empty_core_settings() );
		// Not available for services.
		if ( 'yes' === $this->core_settings['enable_for_virtual'] ) {
			$this->enabled = false;

			return;
		}

		$this->enabled = $this->settings['enabled'];

		// Get setting values.
		if ( SequraHelper::is_admin() ) {
			$this->title = $this->settings['title'];
		} else {
			$this->title = wp_strip_all_tags( $this->settings['title'] );
		}
		$this->max_amount = $this->settings['max_amount'];
		if ( isset( $this->settings['fee'] ) ) {
			$this->fee = (float) $this->settings['fee'];
		}
		$this->has_fields            = true;
		$this->dest_css_sel          = htmlspecialchars_decode( $this->settings['dest_css_sel'] );
		$this->enable_for_countries  = array( 'ES' );
		$this->enable_for_currencies = array( 'EUR' );
		$this->env                   = $this->core_settings['env'];
		$this->helper                = new SequraHelper( $this );
		$this->product               = 'i1';// not an option.

		// Logs.
		if ( isset( $this->core_settings['debug'] ) && 'yes' === $this->core_settings['debug'] ) {
			$this->log = new WC_Logger();
		}

		add_action( 'woocommerce_receipt_' . $this->id, array( $this->helper, 'receipt_page' ) );
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this->helper, 'check_response' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_order_totals' ), 999 );
		do_action( 'woocommerce_sequra_i_loaded', $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$shipping_methods = array();

		if ( is_admin() ) {
			foreach ( WC()->shipping->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_title();
			}
		}
		$this->form_fields = array(
			'enabled'      => array(
				'title'       => __( 'Enable/Disable', 'wc_sequra' ),
				'type'        => 'checkbox',
				'description' => __( 'Habilitar pasarela Sequra', 'wc_sequra' ),
				'default'     => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_sequra' ),
				'default'     => __( 'Compra ahora, paga después', 'wc_sequra' ),
			),
			'max_amount'   => array(
				'title'       => __( 'Max order amount', 'wc_sequra' ),
				'type'        => 'number',
				'description' => __(
					'Sequra payment method will be unavailable for orders beyond this amount',
					'wc_sequra'
				),
				'default'     => '400',
			),
			'widget_theme' => array(
				'title'       => __( 'Widget theme', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Widget visualization params', 'wc_sequra' ),
				'default'     => 'white',
			),
			'dest_css_sel' => array(
				'title'       => __( 'CSS selector for teaser', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __(
					'CSS after which the teaser will be draw. Leave #sequra_invoice_teaser to show it under add to cart button',
					'wc_sequra'
				),
				'default'     => '#sequra_invoice_teaser',
			),
		);
		$this->form_fields = apply_filters( 'woocommerce_sequra_init_form_fields', $this->form_fields, $this );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		} elseif ( is_admin() ) {
			return true;
		}
		if ( SequraHelper::is_checkout() && ! $this->is_available_in_checkout() ) {
			return false;
		}

		if ( is_product() && ! $this->is_available_in_product_page() ) {
			return false;
		}

		if ( SequraHelper::is_admin() ) {
			if ( ! empty( $this->core_settings['enable_for_methods'] ) ) {

				// Only apply if all packages are being shipped via local pickup.
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
				} elseif ( empty( $chosen_shipping_methods ) || count( $chosen_shipping_methods ) > 1 ) {
					$check_method = false;
				} elseif ( 1 === count( $chosen_shipping_methods ) ) {
					$check_method = $chosen_shipping_methods[0];
				}

				if ( ! $check_method ) {
					return false;
				}

				$found = false;

				foreach ( $this->core_settings['enable_for_methods'] as $method_id ) {
					if ( strpos( $check_method, $method_id ) === 0 ) {
						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					return false;
				}
			}
			if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
				return false;
			}
		}
		return $this->helper->is_available_for_country() &&
				$this->helper->is_available_for_currency() &&
				$this->helper->is_available_for_ip();
	}
	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_checkout() {
		return WC()->cart &&
			WC()->cart->needs_shipping() &&
			'yes' !== $this->core_settings['enable_for_virtual'];
	}

	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_product_page() {
		global $product;
		if ( ! $product->needs_shipping() ) {
			return false;
		}

		return true;
	}


	/**
	 * There might be payment fields for Sequra, and we want to show the description if set.
	 * */
	public function payment_fields() {
		$payment_fields = apply_filters( 'woocommerce_sequra_payment_fields', array(), $this );
		require SequraHelper::template_loader( 'payment-fields' );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'Recibe primero, paga edspués', 'wc_sequra' ); ?></h3>
		<p>
		<?php
		echo wp_kses(
			__( 'La pasarela <a href="https://sequra.es/">Sequra</a> para Woocommerce le permitirá dar la opción de "Compra primero, paga después" en su comercio. Para ello necesitará una cuenta de vendedor en Sequra.', 'wc_sequra' ),
			array( 'a' => 'href' )
		);
		?>
		</p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
	}
	/**
	 * Undocumented function
	 *
	 * @param int $order_id Order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		do_action( 'woocommerce_sequra_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);

		return apply_filters( 'woocommerce_sequra_process_payment_return', $ret, $this );
	}
	/**
	 * Undocumented function
	 *
	 * @param WC_Cart $cart The cart.
	 * @return void
	 */
	public function calculate_order_totals( WC_Cart $cart ) {
		if (
			! isset( $_POST['payment_method'] ) ||
			$_POST['payment_method'] != $this->id ||
			! defined( 'WOOCOMMERCE_CHECKOUT' ) ||
			! ( $this->fee > 0 )
		) {
			return;
		}
		$cart->add_fee( __( 'Recargo "Compra primero, paga después"', 'wc_sequra' ), $this->fee, false );
	}
	/**
	 * Undocumented function
	 *
	 * @param array $products Sequra Products.
	 * @return array
	 */
	public static function available_products( $products ) {
		$products[] = 'i1';

		return $products;
	}
}

add_filter( 'sequra_available_products', array( 'SequraInvoiceGateway', 'available_products' ) );

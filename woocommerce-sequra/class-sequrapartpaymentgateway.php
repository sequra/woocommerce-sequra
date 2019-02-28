<?php
/**
 * SeQura Part Payment Payment Gateway.
 *
 * @package woocommerce-sequra
 */

/**
 * SeQura Part Payment Payment Gateway.
 */
class SequraPartPaymentGateway extends WC_Payment_Gateway {

	/**
	 * Undocumented function
	 */
	public function __construct() {
		do_action( 'woocommerce_sequra_pp_before_load', $this );
		$this->id                 = 'sequra_pp';
		$this->icon               = sequra_get_script_basesurl() . 'images/badges/part_payment_s.svg';
		$this->method_title       = __( 'Pago flexible', 'wc_sequra' );
		$this->method_description = __( 'Allows payments part payments, service ofered by Sequra.', 'wc_sequra' );
		$this->supports           = array(
			'products',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->core_settings = get_option( 'woocommerce_sequra_settings', SequraHelper::get_empty_core_settings() );
		// Get setting values.
		$this->enabled               = $this->settings['enabled'];
		$this->title                 = $this->settings['title'];
		$this->enable_for_countries  = array( 'ES' );
		$this->enable_for_currencies = array( 'EUR' );
		$this->has_fields            = true;
		$this->price_css_sel         = htmlspecialchars_decode( $this->settings['price_css_sel'] );
		$this->dest_css_sel          = htmlspecialchars_decode( $this->settings['dest_css_sel'] );
		$this->product               = 'pp3';// not an option.
		$this->pp_cost_url           = 'https://' .
										( $this->core_settings['env'] ? 'sandbox' : 'live' ) .
										'.sequracdn.com/scripts/' .
										$this->core_settings['merchantref'] . '/' .
										$this->core_settings['assets_secret'] .
										'/pp3_pp5_cost.js';
		$this->env                   = $this->core_settings['env'];
		$this->helper                = new SequraHelper( $this );

		// Logs.
		if ( isset( $this->core_settings['debug'] ) && 'yes' === $this->core_settings['debug'] ) {
			$this->log = new WC_Logger();
		}

		// Hooks.
		add_action( 'woocommerce_receipt_' . $this->id, array( $this->helper, 'receipt_page' ) );
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this->helper, 'check_response' ) );
		$json       = get_option( 'sequrapartpayment_conditions' );
		$conditions = json_decode( $json, true );
		if ( ! $conditions ) {
			$this->part_max_amount = 3000;
			$this->min_amount      = 50;
		} else {
			$this->part_max_amount = $conditions[ $this->product ]['max_amount'] / 100;
			$this->min_amount      = $conditions[ $this->product ]['min_amount'] / 100;
		}
		do_action( 'woocommerce_sequra_pp_loaded', $this );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
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
				'description' => __( 'Habilitar pasarela Sequra', 'wc_sequra' ),
				'default'     => 'no',
			),
			'title'         => array(
				'title'       => __( 'Title', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_sequra' ),
				'default'     => __( 'Fracciona tu pago.', 'wc_sequra' ),
			),
			'widget_theme'  => array(
				'title'       => __( 'Widget theme', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'Widget visualization params', 'wc_sequra' ),
				'default'     => 'white',
			),
			'price_css_sel' => array(
				'title'       => __( 'CSS price selector', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __( 'CSS selector to get the price for installment simulator', 'wc_sequra' ),
				'default'     => '.summary .price>.amount,.summary .price ins .amount',
			),
			'dest_css_sel'  => array(
				'title'       => __( 'CSS selector for simulator', 'wc_sequra' ),
				'type'        => 'text',
				'description' => __(
					'CSS after which the simulator will be draw. if just showing it below the prices is not good. Usually empty should be fine',
					'wc_sequra'
				),
				'default'     => '',
			),
		);
		$this->form_fields = apply_filters( 'woocommerce_sequra_pp_init_form_fields', $this->form_fields, $this );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @param  int|null $product_id produt id if product page.
	 * @return boolean
	 */
	public function is_available( $product_id = null ) {
		if ( 'yes' !== $this->enabled ) {
			return false;
		} elseif ( is_admin() ) {
			return true;
		}
		if ( SequraHelper::is_checkout() && ! $this->is_available_in_checkout() ) {
			return false;
		}
		if ( is_product() && $product_id && ! $this->is_available_in_product_page( $product_id ) ) {
			return false;
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
		if ( 'yes' === $this->core_settings['enable_for_virtual'] ) {
			if ( ! $this->helper->is_elegible_for_service_sale() ) {
				return false;
			}
		} elseif ( ! $this->helper->is_elegible_for_product_sale() ) {
			return false;
		}

		if ( 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			return false;
		}

		if ( 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total() ) {
			return false;
		}

		return true;
	}
	/**
	 * Undocumented function
	 *
	 * @param int $product_id page's product id.
	 * @return boolean
	 */
	public function is_available_in_product_page( $product_id ) {
		$product = new WC_Product( $product_id );
		if ( 'yes' === $this->core_settings['enable_for_virtual'] ) {
			return true;// Non-services can be purchased too but not alone.
		} elseif ( ! $product->needs_shipping() ) {
			return false;
		}

		return true;
	}

	/**
	 * There might be payment fields for Sequra, and we want to show the description if set.
	 * */
	public function payment_fields() {
		require SequraHelper::template_loader( 'partpayment-fields' );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'Pasarela Sequra', 'wc_sequra' ); ?></h3>
		<p>
		<?php
		echo wp_kses(
			__( 'La pasarela <a href="https://sequra.es/">Sequra</a> para Woocommerce le permitirá dar la opción de "Pago flexible" en su comercio. Para ello necesitará una cuenta de vendedor en Sequra.', 'wc_sequra' ),
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
	 * @param int $order_id current order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		do_action( 'woocommerce_sequra_pp_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);

		return apply_filters( 'woocommerce_sequra_pp_process_payment_return', $ret, $this );
	}
	/**
	 * Undocumented function
	 *
	 * @param array $products Sequra's products.
	 * @return array
	 */
	public static function available_products( $products ) {
		$products[] = 'pp3';

		return $products;
	}

	/**
	 * Save options in admin.
	 */
	public function process_admin_options() {
		parent::process_admin_options();
		// Force update.
		update_option( 'sequrapartpayment_next_update', 0 );
		sequrapartpayment_upgrade_if_needed();
	}
}

add_filter( 'sequra_available_products', array( 'SequraPartPaymentGateway', 'available_products' ) );

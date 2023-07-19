<?php
/**
 * SeQura Gateway class.
 *
 * @package woocommerce-sequra
 */

/**
 * Pasarela seQura Gateway Class
 * */
class SequraPaymentGateway extends WC_Payment_Gateway {


	/**
	 * Endpoints
	 *
	 * @var array
	 */
	public static $endpoints = array(
		'https://live.sequrapi.com/orders',
		'https://sandbox.sequrapi.com/orders',
	);
	/**
	 * Remote configuration
	 *
	 * @var SequraRemoteConfig
	 */
	private $remote_config = null;

	/**
	 * Remote configuration
	 *
	 * @var SequraHelper
	 */
	public $helper = null;

	/**
	 * Is initialized
	 *
	 * @var boolean
	 */
	private static $initialized = false;

	/**
	 * Instance
	 *
	 * @var SequraPaymentGateway
	 */
	public static $instance = null;
	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new SequraPaymentGateway();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		/**
		 * Action hook to allow plugins to run when the class is loaded.
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_sequra_before_load', $this );
		$this->id = 'sequra';

		$this->method_title       = __( 'seQura', 'sequra' );
		$this->method_description = __( 'seQura payment method\'s configuration', 'sequra' );
		$this->supports           = array(
			'products',
		);

		// Load the settings.
		$this->init_settings();
		$this->helper = SequraHelper::get_instance( $this->settings );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( is_admin() && ! $this->helper->is_ajax_request() && isset( $_GET['section'] ) && 'sequra' === $_GET['section'] ) {
			// Load the form fields.
			$this->is_valid_auth = $this->helper->is_valid_auth();
			$this->init_form_fields();
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$this->enabled    = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : '';
		$this->title      = isset( $this->settings['title'] ) ? $this->settings['title'] : '';
		$this->has_fields = true;
		if ( ! self::$initialized ) {
			// Hooks.
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array(
					$this,
					'process_admin_options',
				)
			);
			add_action( 'woocommerce_api_woocommerce_' . $this->id, array( $this, 'check_response' ) );
			add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
			add_filter( 'woocommerce_order_get_payment_method_title', array( $this, 'order_get_payment_method_title' ), 10, 2 );
			add_action( 'woocommerce_after_checkout_form', array( $this, 'jscript_checkout' ) );
			/**
			 * Action hook to allow plugins to run when the class is loaded.
			 *
			 * @since 2.0.0 
			 */
			do_action( 'woocommerce_sequra_loaded', $this );
			self::$initialized = true;
		}
	}
	/**
	 * Get remote config object
	 * 
	 * @return array
	 */
	public function get_remote_config() {
		if ( is_null( $this->remote_config ) ) {
			$this->remote_config = new SequraRemoteConfig( $this->settings );
		}
		return $this->remote_config;
	}
	/**
	 * Set the proper payment method description in the order
	 *
	 * @param string   $value payment method title.
	 * @param WC_Order $order order object.
	 * @return string 
	 */
	public function order_get_payment_method_title( $value, $order ) {
		if ( $order->get_payment_method() !== $this->id ) {
			return $value;
		}
		return get_post_meta( (int) $order->get_id(), '_sq_method_title', true );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		if ( ! class_exists( 'SequraConfigFormFields' ) ) {
			require_once WC_SEQURA_PLG_PATH . 'class-sequraconfigformfields.php';
		}
		$sequraconfigfields = new SequraConfigFormFields( $this );
		$sequraconfigfields->add_form_fields();
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {    ?>
		<h3>
			<?php esc_html_e( 'Configuración Sequra', 'sequra' ); ?>
		</h3>
		<?php if ( ! $this->is_valid_auth ) { ?>
			<div class="error error-warning is-dismissible">
				<p>
					<?php
					echo wp_kses(
						__( 'Provided seQura credentials are not valid for the selected environment', 'sequra' ),
						array()
					);
					?>
				</p>
			</div>
		<?php } ?>
		<p>
			<?php
			echo wp_kses(
				__( '<a href="https://sequra.es/">seQura</a> payment gateway for WooCommerce allows you to configure available payment methods as seQura.', 'sequra' ),
				array( 'a' => array( 'href' ) )
			);
			?>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
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
		if ( SequraHelper::is_order_review() && count( $this->get_remote_config()->get_available_payment_methods() ) < 1 ) {
			$this->helper->get_logger()->debug( 'No sequra payment method available' );
			return false;
		}
		if ( SequraHelper::is_checkout() && WC()->cart && ! $this->is_available_in_checkout() ) {
			return false;
		}
		if ( is_product() && $product_id && ! $this->is_available_in_product_page( $product_id ) ) {
			return false;
		}
		$ret = $this->helper->is_available_for_ip();
		/**
		 * Filter hook to allow plugins to modify the return value for sequra availability.
		 * 
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_sequra_is_available', $ret );
	}
	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_checkout() {
		if ( ! WC()->cart ) {
			return false;
		}
		if ( 'yes' === $this->settings['enable_for_virtual'] ) {
			if ( ! $this->helper->is_elegible_for_service_sale() ) {
				$this->helper->get_logger()->debug( 'Order is not elegible for service sale.' );
				return false;
			}
		} elseif ( ! $this->helper->is_elegible_for_product_sale() ) {
			$this->helper->get_logger()->debug( 'Order is not elegible for product sale.' );
			return false;
		}
		return $this->helper->is_available_in_checkout();
	}
	/**
	 * Undocumented function
	 *
	 * @param int $product_id page's product id.
	 * @return boolean
	 */
	public function is_available_in_product_page( $product_id ) {
		$product = new WC_Product( $product_id );
		if ( 'yes' !== $this->settings['enable_for_virtual'] && ! $product->needs_shipping() ) {
			$this->helper->get_logger()->debug( 'Widget is not available in product page . Product id:' . $product_id );
			return false;
		}

		return $this->helper->is_available_in_product_page( $product_id );
	}

	/**
	 * There might be payment fields for Sequra, and we want to show the description if set.
	 * */
	public function payment_fields() {
		wp_enqueue_style( 'sequracheckout' );
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}
		$payment_methods = $this->get_remote_config()->get_available_payment_methods();
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		/**
		 * Filter hook to allow plugins to modify the payment methods.
		 *
		 * @since 2.0.0
		 */
		$payment_fields = apply_filters( 'woocommerce_sequra_payment_fields', array(), $this );
		foreach ( $payment_methods as $method ) {
			$sq_product_campaign = $this->get_remote_config()->build_unique_product_code( $method ); // Used in the template.
			require $this->helper->template_loader( 'payment-fields' );
		}
		//phpcs:enable
		$this->jscript_checkout();
	}
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function jscript_checkout() {
		// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		require $this->helper->template_loader( 'checkout-script' );
		// phpcs:enable
	}
	/**
	 * Undocumented function
	 *
	 * @param int $order_id current order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order    = new WC_Order( $order_id );
		$product  = null;
		$campaign = null;
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['sq_product_campaign'] ) ) {
			update_post_meta(
				(int) $order->get_id(),
				'_sq_method_title',
				$this->get_remote_config()->get_title_from_unique_product_code( sanitize_text_field( $_POST['sq_product_campaign'] ) )
			);
			$tmp      = explode( '_', sanitize_text_field( $_POST['sq_product_campaign'] ) );
			$product  = $tmp[0];
			$campaign = isset( $tmp[1] ) ? $tmp[1] : '';
		}
		// phpcs:enable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		/**
		 * Action hook to allow plugins to process payment.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_sequracheckout_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => add_query_arg(
				array(
					'sq_product'  => $product,
					'sq_campaign' => $campaign,
				),
				$order->get_checkout_payment_url( true )
			),
		);
		/**
		 * Filter hook to allow plugins to modify the return array.
		 * 
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_sequracheckout_process_payment_return', $ret, $this );
	}

	/**
	 * Undocumented function
	 *
	 * @param int $order_id common receipt_page method.
	 * @return void
	 */
	public function receipt_page( $order_id ) {
		$order = new WC_Order( $order_id );
		echo '<p>' . wp_kses_post(
			__(
				'Thank you for your order, please click the button below to pay with seQura.',
				'sequra'
			)
		) . '</p>';
		$options = $this->get_valid_product_campaign();
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$identity_form = $this->helper->get_identity_form(
			// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable 
			/**
			 * Filter the options to be sent to seQura if needed
			 * 
			 * @since 2.0.0
			 * */
			apply_filters( 'wc_sequra_pumbaa_options', $options, $order, $this->settings ),
			$order
		);
		// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		require SequraHelper::template_loader( 'payment-identification' );
		// phpcs:enable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	/**
	 * Get a valid product campaign combo from request
	 *
	 * @return array
	 */
	public function get_valid_product_campaign() {
		$product  = isset( $_GET['sq_product'] ) ? sanitize_key( $_GET['sq_product'] ) : '';
		$campaign = isset( $_GET['sq_campaign'] ) ? sanitize_key( $_GET['sq_campaign'] ) : '';
		return array_reduce(
			$this->get_remote_config()->get_merchant_payment_methods() ? $this->get_remote_config()->get_merchant_payment_methods() : array(),
			function ( $ret, $method ) use ( $product, $campaign ) {
				if ( '' === $ret['product'] || $method['product'] === $product ) {
					$ret['product']  = $method['product'];
					$ret['campaign'] = ( $method['campaign'] === $campaign ?
						$method['campaign'] : $ret['campaign']
					);
				}
				return $ret;
			},
			array(
				'product'  => '',
				'campaign' => '',
			)
		);
	}
	// phpcs:enable

	/**
	 * Undocumented function
	 *
	 * @return mixed
	 */
	public function check_response() {      // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_REQUEST['order'] ) ) {
			return;
		}
		$order = new WC_Order( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) );
		if ( isset( $_POST['event'] ) ) {
			return $this->check_webhook( $order );
		}
		if ( isset( $_REQUEST['signature'] ) ) {
			return $this->check_ipn( $order );
		}
		// phpcs:enable
		$url = $this->get_return_url( $order );
		if ( ! $order->is_paid() ) {
			wc_add_notice(
				__(
					'<p>seQura is processing your request.</p>
					<p>After a few minutes <b>you will get an email with your request result</b>.
					seQura might contact you to get some more information.</p>
					<p><b>Thanks for choosing seQura!</b>',
					'sequra'
				),
				'notice'
			);
		}
		wp_safe_redirect( $url, 302 );
		exit;
	}
	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	/**
	 * Undocumented function
	 * 
	 * @param mixed $str 
	 * @param mixed $order 
	 * @return string 
	 */
	public function order_received_text( $str, $order ) {
		$ret = wc_print_notices( true );
		return $ret . $str;
	}
	// phpcs:enable

	// PRIVATE METHODS.
	/**
	 * Undocumented function
	 *
	 * @param WC_Order $order check if ipn is correct.
	 * @return mixed
	 */
	protected function check_ipn( WC_Order $order ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		/**
		 * Action hook if you need to customize the IPN response handling.
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_' . $this->id . '_process_payment', $order, $this );
		$sq_state = isset( $_POST['sq_state'] ) ? sanitize_text_field( $_POST['sq_state'] ) : 'approved';
		// phpcs:enable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		switch ( $sq_state ) {
			case 'needs_review':
				/**
				 * Filter hold result from seQura if needed.
				 *
				 * @since 2.0.0
				 */
				$hold = apply_filters(
					'woocommerce_' . $this->id . '_hold_payment',
					$this->helper->set_on_hold( $order ),
					$order,
					$this
				);
				if ( $hold ) {
					// Payment pedimd.
					$title = get_post_meta( (int) $order->get_id(), '_sq_method_title', true );
					$order->add_order_note(
						// translators: %s is the payment method title.
						sprintf( __( 'Payment is in review by seQura.(%s)', 'sequra' ), $title )
					);
				}
				break;
			case 'approved':
				/**
				 * Filter approval result from seQura if needed.
				 * 
				 * @since 2.0.0
				 */
				$approval = apply_filters(
					'woocommerce_' . $this->id . '_process_payment',
					$this->helper->get_approval( $order ),
					$order,
					$this
				);
				if ( $approval ) {
					// Payment completed.
					$title = get_post_meta( (int) $order->get_id(), '_sq_method_title', true );
					// translators: %s is the payment method title.
					$order->add_order_note( sprintf( __( 'Payment accepted by seQura.(%s)', 'sequra' ), $title ) );
					$this->helper->add_payment_info_to_post_meta( $order );
					$order->payment_complete();
				}
		}

		exit();
	}

	/**
	 * Undocumented function
	 *
	 * @param WC_Order $order check if ipn is correct.
	 * @return mixed
	 */
	protected function check_webhook( WC_Order $order ) {
		$builder = $this->helper->get_builder( $order );
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		if (
			isset( $_POST['m_signature'] ) &&
			$builder->sign( 'webhook' . $order->get_id() ) !== sanitize_text_field( wp_unslash( $_POST['m_signature'] ) )
		) {
			http_response_code( 498 );
			die( 'Not valid signature' );
		}

		$event = isset( $_POST['event'] ) ? sanitize_key( $_POST['event'] ) : 'approved';
		// phpcs:enable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		$title = get_post_meta( (int) $order->get_id(), '_sq_method_title', true );
		switch ( $event ) {
			case 'cancelled':
				$order->add_order_note(
					sprintf(
						// translators: %s is the payment method title.
						__( 'The payment was NOT approved (%s)', 'sequra' ),
						$title
					)
				);
				$order->update_status(
					'cancelled',
					__( 'Order cancelled payment could not be approved.', 'sequra' )
				);
				$order->save();
				break;
			case 'risk_assessment':
				$this->setRiskLevel( $order );
				break;
			default:
				// No implemented should cancel the order in sequra and then if not sent.
				http_response_code( 409 );
				die( 'Not implemented' );
		}
		/**
		 * Action hook fired when a webhook is processed.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_' . $this->id . '_process_webhook', $order, $this );
		exit();
	}

	/**
	 * Set the risk level for the order
	 * 
	 * @param WC_Order $order The order to set the risk level.
	 */
	private function setRiskLevel( $order ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		$risk_level = isset( $_POST['risk_level'] ) ? sanitize_key( $_POST['risk_level'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.Missing
		switch ( $risk_level ) {
			case 'low_risk':
				$order->add_order_note( __( 'The risk assesment for this order is low', 'sequra' ) );
				break;
			case 'high_risk':
				$order->add_order_note( __( 'The risk assesment for this order is HIGH!!', 'sequra' ) );
				break;
			case 'unknown_risk':
				$order->add_order_note( __( 'The risk assesment for this order in progress', 'sequra' ) );
				break;
		}
	}
	/**
	 * Get the order total in checkout and pay_for_order.
	 *
	 * @return float
	 */
	protected function get_order_total() {
		$total    = 0;
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order total from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			$total = (float) $order->get_total();

			// Gets order total from cart/checkout.
		} elseif ( 0 < WC()->cart->total ) {
			$total = (float) WC()->cart->total;
		}
		return $total;
	}
}

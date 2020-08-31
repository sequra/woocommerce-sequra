<?php
/**
 * SeQura Gateway class.
 *
 * @package woocommerce-sequra
 */

use function PHPSTORM_META\map;

/**
 * Pasarela SeQura Gateway Class
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
	public $remote_config = null;

	/**
	 * Remote configuration
	 *
	 * @var SequraHelper
	 */
	public $helper = null;

	private static $initialized = false;

	/**
	 * Instance
	 *
	 * @var SequraPaymentGateway
	 */
	public static $instance = null;

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
		do_action( 'woocommerce_sequra_before_load', $this );
		$this->id = 'sequra';

		$this->method_title       = __( 'Sequra Checkout', 'wc_sequra' );
		$this->method_description = __( 'Configurtación para los métodos de pago Sequra', 'wc_sequra' );
		$this->supports           = array(
			'products',
		);

		// Load the settings.
		$this->init_settings();
		$this->remote_config = new SequraRemoteConfig( $this->settings );
		$this->helper        = new SequraHelper( $this->settings );

		// Load the form fields.
		$this->init_form_fields();
		$this->enabled    = $this->settings['enabled'];
		$this->title      = $this->settings['title'];
		$this->has_fields = true;

		// Logs.
		if ( isset( $this->settings['debug'] ) && 'yes' === $this->settings['debug'] ) {
			$this->log = new WC_Logger();
		}
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
			add_filter( 'woocommerce_thankyou_order_received_text' , array( $this, 'order_received_text' ), 10, 2 );
			add_filter( 'woocommerce_order_get_payment_method_title', array( $this, 'order_get_payment_method_title' ), 10, 2 );
			add_action( 'woocommerce_after_checkout_form', array( $this, 'jscript_checkout' ) );
			do_action( 'woocommerce_sequra_loaded', $this );
			self::$initialized = true;
		}

	}
	/**
	 * Set the proper payment method description
	 */
	public function order_get_payment_method_title( $value, $data ) {
		if ( $data->get_payment_method() !== $this->id ) {
			return $value;
		}
		$product = isset( $_GET['sq_product'] ) ?  $_GET['sq_product'] : null;
		$campaign = isset( $_GET['sq_campaign'] )? $_GET['sq_campaign'] : null;
		return $this->remote_config->get_title_from_product_campaign( $product, $campaign );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		if ( ! class_exists( 'SequraConfigFromFields' ) ) {
			require_once WC_SEQURA_PLG_PATH . 'class-sequraconfigformfields.php';
		}
		$sequraconfigfields = new SequraConfigFromFields( $this );
		$sequraconfigfields->add_form_fields();
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 * */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'Configuración Sequra', 'wc_sequra' ); ?></h3>
		<p>
		<?php
		echo wp_kses(
			__( 'La pasarela <a href="https://sequra.es/">Sequra</a> para Woocommerce le permitirá configurar los métodos de pago disponibles con Sequra.', 'wc_sequra' ),
			array( 'a' => 'href' )
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
		if ( SequraHelper::is_checkout() && ! $this->is_available_in_checkout() ) {
			return false;
		}
		if ( is_product() && $product_id && ! $this->is_available_in_product_page( $product_id ) ) {
			return false;
		}
		$ret = $this->helper->is_available_for_ip();
		return apply_filters( 'woocommerce_sequra_pp_is_available', $ret );
	}
	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function is_available_in_checkout() {
		if ( 'yes' === $this->settings['enable_for_virtual'] ) {
			if ( ! $this->helper->is_elegible_for_service_sale() ) {
				return false;
			}
		} elseif ( ! $this->helper->is_elegible_for_product_sale() ) {
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
			return false;
		}

		return $this->helper->is_available_in_product_page( $product_id );
	}

	/**
	 * There might be payment fields for Sequra, and we want to show the description if set.
	 * */
	public function payment_fields() {
		wp_enqueue_style( 'sequracheckout' );
		if( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			return;
		}
		$payment_methods = $this->remote_config->get_available_payment_methods();
		$payment_fields = apply_filters('woocommerce_sequra_payment_fields', array(), $this);
		foreach ( $payment_methods as $method ) {
			$sq_product_campaign = $this->remote_config->build_unique_product_code( $method ); //Used in the template.
			require( $this->helper->template_loader( 'payment-fields' ));
		}
		$this->jscript_checkout();
	}
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function jscript_checkout() {
		?>
		<script>
			Sequra.onLoad(function(){Sequra.refreshComponents();});
			jQuery('input[name=sq_product_campaign]:first').prop('checked', true)
			jQuery('#payment_method_sequra').click().removeClass( 'input-radio' ).hide();
			jQuery('label[for=payment_method_sequra').hide();
			jQuery('div.payment_method_sequra').removeClass( 'payment_box' );
			jQuery('input[name=sq_product_campaign]').on('click', function () {
				jQuery('#payment_method_sequra').prop('checked', true).click();
			})
			jQuery('input.input-radio').on('click', function () {
				jQuery('input[name=sq_product_campaign]').prop('checked', false);
			})
		</script>
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
		$product = $campaign = null;
		if( isset( $_POST['sq_product_campaign'] ) ){
			update_post_meta(
				(int) $order->get_id(),
				'_sq_method_title',
				$this->remote_config->get_title_from_unique_product_code( $_POST['sq_product_campaign'] )
			);
			list( $product, $campaign ) = explode( '_', $_POST['sq_product_campaign'] );
		}
		do_action( 'woocommerce_sequracheckout_process_payment', $order, $this );
		$ret = array(
			'result'   => 'success',
			'redirect' => add_query_arg(
				array(
					'sq_product'  => $product,
					'sq_campaign' => $campaign
				),
				$order->get_checkout_payment_url( true )
			),
		);

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
				'Thank you for your order, please click the button below to pay with Sequra.',
				'wc_sequra'
			)
		) . '</p>';
		$options = array(
			'product' =>  isset( $_GET['sq_product'] ) ?  $_GET['sq_product'] : '',
			'campaign' =>  isset( $_GET['sq_campaign'] )? $_GET['sq_campaign'] : '',
		);
		$identity_form = $this->helper->get_identity_form(
			apply_filters( 'wc_sequra_pumbaa_options', $options, $order, $this->settings ),
			$order
		);
		require SequraHelper::template_loader( 'payment-identification' );
	}

	/**
	 * Undocumented function
	 *
	 * @return mixed
	 */
	public function check_response() {
		if ( ! isset( $_GET['order'] ) ) {
			return;
		}
		$order = new WC_Order( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) );
		if ( isset( $_POST['event'] ) ) {
			return $this->check_webhook( $order );
		}
		if ( isset( $_REQUEST['signature'] ) ) {
			return $this->check_ipn( $order );
		}
		$url = $this->get_return_url( $order );
		if ( ! $order->is_paid() ) {
			wc_add_notice(
				__(
					'<p>SeQura está procesando tu solicitud.</p>' .
					'<p>En unos minutos <b>recibirás un email con respuesta a tu solicitud</b>. Es posible que SeQura contacte contigo antes para validar algunos datos.</p>' .
					'<p><b>Gracias por comprar con SeQura</b>',
					'wc_sequra'
				),
				'notice'
			);
		}
		wp_safe_redirect( $url, 302 );
	}

	function order_received_text( $str, $order ) {
		$ret = wc_print_notices(true);
		return $ret.$str;
	}

//PRIVATE METHODS.
	/**
	 * Undocumented function
	 *
	 * @param WC_Order $order check if ipn is correct.
	 * @return mixed
	 */
	protected function check_ipn( WC_Order $order ) {
		$product_code = isset( $_POST['product_code'] ) ? wp_unslash( $_POST['product_code'] ) : '';
		do_action( 'woocommerce_' . $this->id . '_process_payment', $order, $this );
		$sq_state = isset( $_POST['sq_state'] )? $_POST['sq_state'] : 'approved' ;
		switch ($sq_state) {
			case 'needs_review':
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
						sprintf( __( 'Payment is in review by Sequra (%s)', 'wc_sequra' ), $title )
					);
				}
			break;
			case 'approved':
				$approval = apply_filters(
					'woocommerce_' . $this->id . '_process_payment',
					$this->helper->get_approval( $order ),
					$order,
					$this
				);
				if ( $approval ) {
					// Payment completed.
					$title = get_post_meta( (int) $order->get_id(), '_sq_method_title', true );
					$order->add_order_note( sprintf( __( 'Payment accepted by Sequra (%s)', 'wc_sequra' ), $title ) );
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
		if ( isset( $_POST['m_signature'] ) &&
			$builder->sign( 'webhook' . $order->get_id() ) !== sanitize_text_field( wp_unslash( $_POST['m_signature'] ) )
		) {
			http_response_code( 498 );
			die( 'Not valid signature' );
		}

		$event = isset( $_POST['event'] )? $_POST['event'] : 'approved' ;
		$title = get_post_meta( (int) $order->get_id(), '_sq_method_title', true );
		switch ($event) {
			case 'cancelled':
				$order->add_order_note( sprintf(
					__( 'The payment was NOT approved (%s)', 'wc_sequra' ),
					$title
				));
				$order->set_status( 'failed' );
				$order->save();
				break;
			case 'risk_assessment':
				$this->setRiskLevel( $order );
				break;
			default:
				//No implemented should cancel the order in sequra and then if not sent
				http_response_code( 409 );
				die( 'Not implemented' );
		}
		do_action( 'woocommerce_' . $this->id . '_process_webhook', $order, $this );
		exit();
	}

	private function setRiskLevel( $order )
	{
		$risk_level = isset( $_POST['risk_level'] )? $_POST['risk_level'] : '' ;
		switch ($risk_level) {
			case 'low_risk':
				$order->add_order_note( __( 'The risk assesment for this order is low', 'wc_sequra' ) );
				break;
			case 'high_risk':
				$order->add_order_note( __( 'The risk assesment for this order is HIGH!!', 'wc_sequra' ) );
				break;
			case 'unknown_risk':
				$order->add_order_note( __( 'The risk assesment for this order in progress', 'wc_sequra' ) );
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

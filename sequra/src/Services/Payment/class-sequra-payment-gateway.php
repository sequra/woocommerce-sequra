<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use WC_Order;
use WC_Payment_Gateway;

/**
 * Handle use cases related to payments
 */
class Sequra_Payment_Gateway extends WC_Payment_Gateway {

	private const FORM_FIELD_ENABLED          = 'enabled';
	private const FORM_FIELD_TITLE            = 'title';
	private const FORM_FIELD_DESC             = 'description';
	private const POST_SQ_PAYMENT_METHOD_DATA = 'sequra_payment_method_data';
	
	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Service
	 */
	private $payment_service;

	/**
	 * Payment service
	 *
	 * @var Interface_Payment_Method_Service
	 */
	private $payment_method_service;

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * Cart service
	 *
	 * @var Interface_Cart_Service
	 */
	private $cart_service;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	private $templates_path;

	/**
	 * Logger
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct() {

		/**
		 * Action hook to allow plugins to run when the class is loaded.
		 * TODO: is this still needed?
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_sequra_before_load', $this );

		$this->payment_service        = ServiceRegister::getService( Interface_Payment_Service::class );
		$this->cart_service           = ServiceRegister::getService( Interface_Cart_Service::class );
		$this->order_service          = ServiceRegister::getService( Interface_Order_Service::class );
		$this->payment_method_service = ServiceRegister::getService( Interface_Payment_Method_Service::class );
		$this->templates_path         = ServiceRegister::getService( 'plugin.templates_path' );
		$this->logger                 = ServiceRegister::getService( Interface_Logger_Service::class );
		$this->id                     = $this->payment_service->get_payment_gateway_id(); // @phpstan-ignore-line
		// TODO: URL of the icon that will be displayed on checkout page near your gateway name.
		$this->icon               = 'https://cdn.prod.website-files.com/62b803c519da726951bd71c2/62b803c519da72c35fbd72a2_Logo.svg'; 
		$this->has_fields         = true;
		$this->method_title       = __( 'seQura', 'sequra' );
		$this->method_description = __( 'seQura payment method\'s configuration', 'sequra' );

		$this->supports = array(
			'products',
		);

		$this->init_form_fields();
		$this->init_settings();
		$this->enabled     = $this->get_form_field_value( self::FORM_FIELD_ENABLED );
		$this->title       = $this->get_form_field_value( self::FORM_FIELD_TITLE ); // Title of the payment method shown on the checkout page.
		$this->description = $this->get_form_field_value( self::FORM_FIELD_DESC ); // Description of the payment method shown on the checkout page.

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'redirect_to_payment' ) );

		// TODO: Declare webhooks for IPN and other events. Use POST allways.
		// add_action( 'woocommerce_api_sequra_webhook', array( $this, 'webhook' ) );.

		/**
		 * Action hook to allow plugins to run when the class is loaded.
		 * TODO: is this still needed?
		 * 
		 * @since 2.0.0 
		 */
		do_action( 'woocommerce_sequra_loaded', $this );
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = parent::is_available();
		if ( ! $is_available ) {
			return false;
		}
		if ( ! $this->cart_service->is_available_in_checkout() ) {
			$this->logger->log_debug( 'Payment gateway is not available in checkout', __FUNCTION__, __CLASS__ );
			return false;
		}
		
		return ! empty( $this->payment_method_service->get_payment_methods() );
	}

	/**
	 * Helper to read the value of a field from the configuration form
	 * or the default value if not set
	 */
	private function get_form_field_value( string $field_name ) {
		$default = isset( $this->form_fields[ $field_name ]['default'] ) ? $this->form_fields[ $field_name ]['default'] : null;
		return $this->get_option( $field_name, $default );
	}
	
	/**
	 * Declare fields for the configuration form
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			self::FORM_FIELD_ENABLED => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable seQura',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			self::FORM_FIELD_TITLE   => array(
				'title'       => __( 'Title', 'sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'sequra' ),
				'default'     => __( 'Flexible payment with seQura', 'sequra' ),
			),
			self::FORM_FIELD_DESC    => array(
				'title'       => __( 'Description', 'sequra' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'sequra' ),
				'default'     => __( 'Please, select the payment method you want to use', 'sequra' ),
			),
		);
	}

	/**
	 * Declare fields for the payment method in the checkout page
	 */
	public function payment_fields() {
		$args = array(
			'description'     => $this->description,
			'payment_methods' => $this->payment_method_service->get_payment_methods(),
		);
		wc_get_template( 'front/payment_fields.php', $args, '', $this->templates_path );
	}

	/**
	 * Validate fields for the payment method in the checkout page
	 *
	 * @return bool
	 */
	public function validate_fields() {
		if ( ! $this->payment_method_service->is_payment_method_data_valid( $this->get_posted_data() ) ) {
			wc_add_notice( __( 'Please select a valid <strong>seQura payment method</strong>', 'sequra' ), 'error' );
			return false;
		}
		return true;
	}
	
	/**
	 * Process payment
	 * 
	 * @param int $order_id
	 * 
	 * @return array
	 */
	public function process_payment( $order_id ) {  
		$order              = wc_get_order( $order_id );
		$payment_method_dto = $this->get_posted_data();
		$cart_info_dto      = $this->cart_service->get_cart_info_from_session();

		if ( ! $order instanceof WC_Order
		|| ! $this->payment_method_service->is_payment_method_data_valid( $payment_method_dto )
		|| ! $this->order_service->set_order_metadata( $order, $payment_method_dto, $cart_info_dto )
		) {
			return array(
				'result'   => 'failure',
				'redirect' => wc_get_checkout_url(),
			);
		}

		// clear session data.
		$this->cart_service->clear_cart_info_from_session();

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Get payment method data from POST.
	 */
	private function get_posted_data(): ?Payment_Method_Data {
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ self::POST_SQ_PAYMENT_METHOD_DATA ] ) || ! is_string( $_POST[ self::POST_SQ_PAYMENT_METHOD_DATA ] ) ) {
			return null;
		}
		
		return Payment_Method_Data::decode( sanitize_text_field( $_POST[ self::POST_SQ_PAYMENT_METHOD_DATA ] ) );
	}

	/**
	 * Webhook
	 */
	public function webhook() {
		// TODO: Implement webhook() method.
	}

	/**
	 * This method is called on order's receipt page to show seQura's payment form
	 */
	public function redirect_to_payment( int $order_id ) {
		$this->logger->log_debug( 'Hook executed', __FUNCTION__, __CLASS__ );

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			$this->logger->log_error( 'Order not found', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order_id ) ) );
			wc_print_notice( __( 'Order not found', 'sequra' ), 'error' );
			return;
		}

		$response = $this->payment_method_service->get_identification_form( $order );

		if ( ! $response ) {
			wc_print_notice( __( 'Sorry, something went wrong. Please contact the merchant.', 'sequra' ), 'error' );
			return;
		}

		$args = array(
			'form' => $response->getForm(),
		);
		wc_get_template( 'front/receipt_page.php', $args, '', $this->templates_path );
	}
}

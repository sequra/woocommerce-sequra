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

use SeQura\Core\BusinessLogic\WebhookAPI\WebhookAPI;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use Throwable;
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
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_sequra_before_load', $this );

		/**
		 * Payment service
		 *
		 * @var Interface_Payment_Service $payment_service
		 */
		$payment_service              = ServiceRegister::getService( Interface_Payment_Service::class );
		$this->payment_service        = $payment_service;
		$this->cart_service           = ServiceRegister::getService( Interface_Cart_Service::class );
		$this->order_service          = ServiceRegister::getService( Interface_Order_Service::class );
		$this->payment_method_service = ServiceRegister::getService( Interface_Payment_Method_Service::class );
		$this->templates_path         = ServiceRegister::getService( 'plugin.templates_path' );
		$this->logger                 = ServiceRegister::getService( Interface_Logger_Service::class );
		$this->id                     = $this->payment_service->get_payment_gateway_id(); // @phpstan-ignore-line
		$this->has_fields             = true;
		$this->method_title           = __( 'seQura', 'sequra' );
		$this->method_description     = __( 'seQura payment method\'s configuration', 'sequra' );

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
		add_action( 'woocommerce_api_' . $this->payment_service->get_ipn_webhook(), array( $this, 'process_ipn' ) );
		add_action( 'woocommerce_api_' . $this->payment_service->get_event_webhook(), array( $this, 'process_event' ) );
		add_action( 'woocommerce_api_' . $this->payment_service->get_return_webhook(), array( $this, 'handle_return' ) );

		/**
		 * Action hook to allow plugins to run when the class is loaded.
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
		//phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Webhook to process IPN callback
	 */
	public function process_ipn() {
		$this->handle_webhook( $this->payment_service->get_ipn_webhook() );
	}

	/**
	 * Webhook to process handle return url
	 */
	public function handle_return() {
		
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$return_url = wc_get_checkout_url();
		if ( ! isset( $_GET['order'] ) ) {
			wp_safe_redirect( $return_url, 302 );
			exit;
		}

		$order = wc_get_order( absint( $_GET['order'] ) );
		if ( $order instanceof WC_Order ) {
			if ( in_array( $order->get_status(), array( 'on-hold', 'processing' ), true ) ) {
				$return_url = $order->get_checkout_order_received_url();
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
			} else {
				wc_add_notice( __( 'Error has occurred, please try again.', 'sequra' ), 'error' );
				$return_url = $order->get_checkout_payment_url();
			}
		}
		//phpcs:enable WordPress.Security.NonceVerification.Recommended

		/**
		 * Filter hook to allow plugins to modify the return URL.
		 *
		 * @since 2.0.0
		 */
		$url = apply_filters( 'woocommerce_get_return_url', $return_url, $order );

		wp_safe_redirect( $url, 302 );
		exit;
	}

	/**
	 * Prepare payload for the webhook request
	 */
	private function prepare_payload(): array {

		$payload = array(
			'signature'          => null,
			'order_ref'          => '',
			'product_code'       => '',
			'sq_state'           => '',
			'order_ref_1'        => '',
			'approved_since'     => null,
			'needs_review_since' => null,
			// Add this fields to simplify the code, but they are not used in the current implementation.
			'storeId'            => null,
			'order'              => null,
		);

		$prefix = 'm_';

		foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$payload_key = 'event' === $key ? 'sq_state' : ( str_starts_with( $key, $prefix ) ? substr( $key, strlen( $prefix ) ) : $key );
			if ( ! array_key_exists( $payload_key, $payload ) ) {
				continue;
			}
			$payload[ $payload_key ] = in_array( $payload_key, array( 'approved_since', 'needs_review_since', 'order' ), true ) ? 
			intval( wp_unslash( $value ) ) :
			sanitize_text_field( wp_unslash( $value ) );
		}

		return $payload;
	}

	/**
	 * Validate payload and exit if it is invalid, giving a proper response
	 */
	private function die_on_invalid_payload( array $payload ): void {
		if ( null === $payload['order'] 
			|| null === $payload['signature'] 
			|| null === $payload['storeId']
			|| $this->payment_service->sign( $payload['order'] ) !== $payload['signature'] ) {
			$this->logger->log_error( 'Bad signature', __FUNCTION__, __CLASS__, array( new LogContextData( 'payload', $payload ) ) );
			status_header( 498 );
			die( 'Bad signature' );
		}

		$order = wc_get_order( $payload['order'] );
		if ( ! $order instanceof WC_Order ) {
			$this->logger->log_error( 'No order found', __FUNCTION__, __CLASS__, array( new LogContextData( 'payload', $payload ) ) );
			status_header( 404 );
			die( 'No order found id:' . esc_html( $payload['order'] ) );
		}
	}

	/**
	 * Handle IPN and Event webhooks requests
	 */
	private function handle_webhook( string $webhook_identifier ) {
		$payload = $this->prepare_payload();
		$this->die_on_invalid_payload( $payload );
		try {
			$response = WebhookAPI::webhookHandler( $payload['storeId'] )->handleRequest( $payload );
			if ( ! $response->isSuccessful() ) {
				$error = $response->toArray();

				$this->logger->log_error(
					'Webhook request failed',
					__FUNCTION__,
					__CLASS__,
					array( 
						new LogContextData( 'webhook', $webhook_identifier ),
						new LogContextData( 'payload', $payload ),
						new LogContextData( 'response', $error ),
					) 
				);

				$msg = isset( $error['errorMessage'] ) ? $error['errorMessage'] : 'Request failed';
				
				$order = wc_get_order( $payload['order'] );
				if ( $order instanceof WC_Order ) {
					$order->set_status( 'pending', $msg );
					$order->save();
				}
				
				status_header( 410 ); // Set 410 status to trigger a refund for the order.
				die( esc_html( $msg ) );
			}

			/**
			* Action hook fired when a webhook is processed.
			* 
			* @since 2.0.0
			*/
			do_action( $webhook_identifier . '_process_webhook', wc_get_order( $payload['order'] ), $this );
			exit();
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			status_header( 500 );
			die( 'Internal error' );
		}
	}

	/**
	 * Webhook to process events
	 */
	public function process_event() {
		$this->handle_webhook( $this->payment_service->get_event_webhook() );
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

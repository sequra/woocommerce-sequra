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

use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
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
use WP_Error;

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
		$this->method_description     = sprintf(
			'%1$s <a href="%2$s">%3$s</a>',
			esc_html__( 'seQura payment method\'s configuration.', 'sequra' ),
			/**
			 * Must return the URL to the settings page.
			 *
			 * @since 3.0.0
			 */
			esc_url( strval( apply_filters( 'sequra_settings_page_url', '' ) ) ),
			esc_html__( 'View more configuration options.', 'sequra' )
		);

		
		// __( 'seQura payment method\'s configuration', 'sequra' );

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_settings();
		$this->enabled     = $this->get_form_field_value( self::FORM_FIELD_ENABLED );
		$this->title       = __( 'Flexible payment with seQura', 'sequra' ); // Title of the payment method shown on the checkout page.
		$this->description = __( 'Please, select the payment method you want to use', 'sequra' ); // Description of the payment method shown on the checkout page.

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'redirect_to_payment' ) );
		add_action( 'woocommerce_api_' . $this->payment_service->get_ipn_webhook(), array( $this, 'process_ipn' ) );
		add_action( 'woocommerce_api_' . $this->payment_service->get_event_webhook(), array( $this, 'process_event' ) );
		add_action( 'woocommerce_api_' . $this->payment_service->get_return_webhook(), array( $this, 'handle_return' ) );

		add_action( 'load-woocommerce_page_wc-settings', array( $this, 'redirect_to_settings_page' ) );

		/**
		 * Action hook to allow plugins to run when the class is loaded.
		 * 
		 * @since 2.0.0 
		 */
		do_action( 'woocommerce_sequra_loaded', $this );
	}

	/**
	 * Redirect to settings page if needed.
	 */
	public function redirect_to_settings_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tab'], $_GET['section'] ) && 'checkout' === $_GET['tab'] && $this->id === $_GET['section'] ) {
			/**
			 * Must return the URL to the settings page.
			 *
			 * @since 3.0.0
			 */
			wp_safe_redirect( esc_url( strval( apply_filters( 'sequra_settings_page_url', '' ) ) ) );
			exit;
		}
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = parent::is_available();
		$order        = $this->try_to_get_order_from_context();
		if ( $is_available && ! $this->cart_service->is_available_in_checkout( $order ) ) {
			$this->logger->log_debug( 'Payment gateway is not available in checkout. Conditions are not met', __FUNCTION__, __CLASS__ );
			$is_available = false;
		} elseif ( $is_available && empty( $this->payment_method_service->get_payment_methods( $order ) ) ) {
			$this->logger->log_debug( 'Payment gateway is not available in checkout. No payment methods available', __FUNCTION__, __CLASS__ );
			$is_available = false;
		}
		/**
		 * Filter hook to allow plugins to modify the return value for sequra availability.
		 * 
		 * @since 2.0.0
		 */
		return (bool) apply_filters( 'woocommerce_sequra_is_available', $is_available );
	}

	/**
	 * Try to get the order from the current context
	 */
	private function try_to_get_order_from_context(): ?WC_Order {
		$order = null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['pay_for_order'], $_GET['key'] ) ) {
			$order = wc_get_order( absint( strval( get_query_var( 'order-pay' ) ) ) );
		}
		return $order instanceof WC_Order ? $order : null;
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
		);
	}

	/**
	 * Declare fields for the payment method in the checkout page
	 */
	public function payment_fields() {
		$payment_methods = $this->payment_method_service->get_payment_methods(
			$this->try_to_get_order_from_context()
		);
		if ( empty( $payment_methods ) ) {
			$this->logger->log_debug( 'No payment methods available', __FUNCTION__, __CLASS__ );
			return '';
		}

		$args = array(
			'description'     => $this->description,
			'payment_methods' => $payment_methods,
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
		$cart_info_dto      = null;
		if ( $order instanceof WC_Order ) {
			// Try to recover cart info from the order if it is already set.
			$this->logger->log_debug( 'Trying to recover cart info from the order', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order_id ) ) );
			$cart_info_dto = $this->order_service->get_cart_info( $order );
		}
		if ( ! $cart_info_dto || ! $cart_info_dto->ref ) {
			// Fetch cart info from session if it is not set.
			$this->logger->log_debug( 'Trying to recover from cart session data', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order_id ) ) );
			$cart_info_dto = $this->cart_service->get_cart_info_from_session();
		}

		$result = null;

		if ( ! $order instanceof WC_Order
		|| ! $this->payment_method_service->is_payment_method_data_valid( $payment_method_dto )
		|| ! $this->order_service->set_order_metadata( $order, $payment_method_dto, $cart_info_dto )
		) {
			$result = array(
				'result'   => 'failure',
				'redirect' => wc_get_checkout_url(),
			);
		} else {
			// clear session data.
			$this->cart_service->clear_cart_info_from_session();

			// TODO: Check if we are keeping this action hook.
			/**
			 * Action hook to allow plugins to process payment.
			 * 
			 * @since 2.0.0
			 */
			do_action( 'woocommerce_sequracheckout_process_payment', $order, $this );
			
			$result = array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			);
		}
		/**
		 * Filter hook to allow plugins to modify the return array.
		 * 
		 * @since 2.0.0
		 */
		$result = apply_filters_deprecated( 'woocommerce_sequracheckout_process_payment_return', array( $result, null ), '3.0.0', 'sequra_checkout_process_payment_return' );
		
		/**
		 * Filter hook to allow plugins to modify the return array.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'sequra_checkout_process_payment_return', $result, $order, $this ); 
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
			$this->logger->log_debug( 'Bad signature', __FUNCTION__, __CLASS__, array( new LogContextData( 'payload', $payload ) ) );
			status_header( 498 );
			die( 'Bad signature' );
		}

		// Check if 'sq_state' is one of the expected values.
		if ( ! in_array( $payload['sq_state'], OrderStates::toArray(), true ) ) {
			$this->logger->log_error( 'Invalid sq_state', __FUNCTION__, __CLASS__, array( new LogContextData( 'payload', $payload ) ) );
			status_header( 400 );
			die( 'Invalid state' );
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['event'] ) && 'cancelled' !== sanitize_text_field( wp_unslash( $_POST['event'] ) ) ) {
			return;
		}
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

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return bool|\WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$amount = (float) $amount;
		if ( $amount <= 0 ) {
			$this->logger->log_debug( 'Invalid refund amount: ' . $amount, __FUNCTION__, __CLASS__ );
			return new WP_Error( 'empty_refund_amount', __( 'Refund amount cannot be empty', 'sequra' ) );
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			$this->logger->log_error( 'Order not found', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order_id ) ) );
			return new WP_Error( 'order_not_found', __( 'Order not found', 'sequra' ) );
		}
		try {
			$this->order_service->handle_refund( $order, $amount );
			return true;
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return new WP_Error( 'refund_failed', __( 'An error occurred while refunding the order in seQura.', 'sequra' ) ); // TODO: improve message.
		}
	}
}

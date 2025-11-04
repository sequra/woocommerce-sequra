<?php
/**
 * Payment method service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\Core\BusinessLogic\AdminAPI\Response\Response;
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\Solicitation\Response\IdentificationFormResponse;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraForm;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders\Interface_Create_Order_Request_Builder;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Dto\Payment_Method_Option;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Current_Order_Provider;
use SeQura\WC\Services\Order\Interface_Order_Service;
use WC_Order;

/**
 * Handle use cases related to payment methods
 */
class Payment_Method_Service implements Interface_Payment_Method_Service {

	/**
	 * Payment methods
	 *
	 * @var Payment_Method_Option[]
	 */
	private $payment_methods;

	/**
	 * Create order request builder
	 *
	 * @var Interface_Create_Order_Request_Builder
	 */
	private $create_order_request_builder;

	/**
	 * Order service
	 *
	 * @var Interface_Order_Service
	 */
	private $order_service;

	/**
	 * Logger
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Current order provider
	 *
	 * @var Interface_Current_Order_Provider
	 */
	private $current_order_provider;

	/**
	 * Store context
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Create_Order_Request_Builder $create_order_request_builder,
		Interface_Order_Service $order_service,
		Interface_Logger_Service $logger,
		Interface_Current_Order_Provider $current_order_provider,
		StoreContext $store_context
	) {
		$this->create_order_request_builder = $create_order_request_builder;
		$this->order_service                = $order_service;
		$this->logger                       = $logger;
		$this->current_order_provider       = $current_order_provider;
		$this->store_context                = $store_context;
	}

	/**
	 * Request solicitation
	 */
	private function request_solicitation( ?WC_Order $order = null ): ?Response {
		$this->current_order_provider->set( $order );
		
		if ( ! $this->create_order_request_builder->is_allowed() ) {
			$ctx = $order ? array( new LogContextData( 'order_id', $order->get_id() ) ) : array();
			$this->logger->log_debug( 'Order is not allowed for solicitation', __FUNCTION__, __CLASS__, $ctx );
			return null;
		}
	
		/**
		 * Response
		 *
		 * @var Response $response */
		$response = CheckoutAPI::get()->solicitation( $this->store_context->getStoreId() )->solicitFor( $this->create_order_request_builder );
		if ( ! $response->isSuccessful() ) {
			$ctx   = $order ? array( new LogContextData( 'order_id', $order->get_id() ) ) : array();
			$ctx[] = new LogContextData( 'response', $response->toArray() );
			$this->logger->log_error( 'Solicitation response is not successful', __FUNCTION__, __CLASS__, $ctx );
		}

		return $response;
	}

	/**
	 * Get identification form
	 */
	public function get_identification_form( WC_Order $order ): ?SeQuraForm {
		
		$response = $this->request_solicitation( $order );

		if ( ! $response || ! $response->isSuccessful() ) {
			return null;
		}

		/**
		 * Filter the options to be sent to seQura if needed
		 * 
		 * @since 2.0.0
		 * */
		$opts = \apply_filters(
			'wc_sequra_pumbaa_options', 
			array(
				'product'  => $this->order_service->get_product( $order ),
				'campaign' => $this->order_service->get_campaign( $order ),
			), 
			$order,
			array() 
		);

		$cart_info = $this->order_service->get_cart_info( $order );

		if ( ! $cart_info ) {
			$this->logger->log_error( 'Cart info is null', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order->get_id() ) ) );
			return null;
		}

		/**
		 * Response
		 *
		 * @var IdentificationFormResponse $response */
		$response = CheckoutAPI::get()
		->solicitation( $this->store_context->getStoreId() )
		->getIdentificationForm( 
			$cart_info->ref, 
			isset( $opts['product'] ) && '' !== $opts['product'] ? $opts['product'] : null,
			isset( $opts['campaign'] ) && '' !== $opts['campaign'] ? $opts['campaign'] : null
		);

		if ( ! $response ) {
			$this->logger->log_error( 'Identification form response is null', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order->get_id() ) ) );
			return null;
		}

		if ( ! $response->isSuccessful() ) {
			$this->logger->log_error(
				'Identification form response is not successful',
				__FUNCTION__,
				__CLASS__,
				array( 
					new LogContextData( 'response', $response->toArray() ),
					new LogContextData( 'order_id', $order->get_id() ),
				)
			);
			return null;
		}

		return $response->getIdentificationForm();
	}

	/**
	 * Get payment methods
	 * 
	 * @return Payment_Method_Option[]
	 */
	public function get_payment_methods( ?WC_Order $order = null ) {
		if ( null !== $this->payment_methods ) {
			return $this->payment_methods;
		}
		// TODO: Implement a cache system to avoid multiple requests with the same payload.
		$response = $this->request_solicitation( $order );

		if ( ! $response || ! $response->isSuccessful() ) {
			return array();
		}
		$this->payment_methods = array();
		foreach ( (array) ( $response->toArray()['availablePaymentMethods'] ?? array() ) as $raw ) {
			$this->payment_methods[] = Payment_Method_Option::from_array( $raw );
		}
		return $this->payment_methods;
	}

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
	public function is_payment_method_data_valid( ?Payment_Method_Data $data ): bool {
		if ( null !== $data ) {
			foreach ( $this->get_payment_methods() as $pm ) {
				if ( $pm->match( $data ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if the current page is the order pay page
	 */
	public function is_order_pay_page(): bool {
		return ( (int) strval( \get_query_var( 'order-pay' ) ) ) > 0;
	}

	/**
	 * Check if the current page is the checkout page
	 */
	public function is_checkout(): bool {
		/**
		 * Check if current page is checkout
		 * 
		 * @since 3.0.0
		 */
		return (bool) \apply_filters( 'sequra_is_checkout', \is_checkout() || $this->is_order_pay_page() || $this->is_store_api_request() );
	}

	/**
	 * Returns true if the request is a store REST API request.
	 *
	 * @return bool
	 */
	private function is_store_api_request() {
		// @phpstan-ignore-next-line
		if ( method_exists( 'WooCommerce', 'is_store_api_request' ) ) {
			return WC()->is_store_api_request();
		}
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return false !== strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) . 'wc/store/' );
	}
}

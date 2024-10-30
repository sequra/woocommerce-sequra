<?php
/**
 * Payment method service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\Solicitation\Response\SolicitationResponse;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraForm;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders\Interface_Create_Order_Request_Builder;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use Throwable;
use WC_Order;

/**
 * Handle use cases related to payment methods
 */
class Payment_Method_Service implements Interface_Payment_Method_Service {

	/**
	 * Part payment
	 */
	const PART_PAYMENT = 'part_payment';
	
	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Payment methods
	 *
	 * @var array<string, string>[]
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
	 * Constructor
	 */
	public function __construct( 
		Configuration $configuration,
		Interface_Create_Order_Request_Builder $create_order_request_builder,
		Interface_Order_Service $order_service,
		Interface_Logger_Service $logger
	) {
		$this->configuration                = $configuration;
		$this->create_order_request_builder = $create_order_request_builder;
		$this->order_service                = $order_service;
		$this->logger                       = $logger;
	}

	/**
	 * Request solicitation
	 */
	private function request_solicitation( ?WC_Order $order = null ): ?SolicitationResponse {
		try {
			$this->create_order_request_builder->set_current_order( $order );
			
			if ( ! $this->create_order_request_builder->is_allowed() ) {
				$ctx = $order ? array( new LogContextData( 'order_id', $order->get_id() ) ) : array();
				$this->logger->log_debug( 'Order is not allowed for solicitation', __FUNCTION__, __CLASS__, $ctx );
				return null;
			}
	
			$response = CheckoutAPI::get()
			->solicitation( $this->configuration->get_store_id() )
			->solicitFor( $this->create_order_request_builder );
			
			if ( ! $response->isSuccessful() ) {
				$ctx   = $order ? array( new LogContextData( 'order_id', $order->get_id() ) ) : array();
				$ctx[] = new LogContextData( 'response', $response->toArray() );
				$this->logger->log_error( 'Solicitation response is not successful', __FUNCTION__, __CLASS__, $ctx );
			}

			return $response;
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
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
		$opts = apply_filters(
			'wc_sequra_pumbaa_options', 
			array(
				'product'  => $this->order_service->get_product( $order ),
				'campaign' => $this->order_service->get_campaign( $order ),
			), 
			$order,
			array() 
		);

		try {
			$cart_info = $this->order_service->get_cart_info( $order );

			if ( ! $cart_info ) {
				$this->logger->log_error( 'Cart info is null', __FUNCTION__, __CLASS__, array( new LogContextData( 'order_id', $order->get_id() ) ) );
				return null;
			}

			$response = CheckoutAPI::get()
			->solicitation( $this->configuration->get_store_id() )
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
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return null;
		}
	}

	/**
	 * Get payment methods
	 * 
	 * @return array<string, string>[]
	 */
	public function get_payment_methods( ?WC_Order $order = null ): array {

		if ( null !== $this->payment_methods ) {
			return $this->payment_methods;
		}
	
		$response = $this->request_solicitation( $order );

		if ( ! $response || ! $response->isSuccessful() ) {
			return array();
		}
			
		$this->payment_methods = $response->toArray()['availablePaymentMethods'] ?? array();
		return $this->payment_methods;
	}

	/**
	 * Get a list of all payment methods defined for store and merchant
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_all_payment_methods( ?string $store_id, ?string $merchant ): array {
		if ( ! $store_id || ! $merchant ) {
			return array();
		}
		try {
			$response = AdminAPI::get()
			->paymentMethods( $store_id )
			->getPaymentMethods( strval( $merchant ) );
		
			if ( ! $response->isSuccessful() ) {
				return array();
			}
			$payment_methods = $response->toArray();

			foreach ( $payment_methods as &$method ) {
				$method['supportsWidgets']             = $this->supports_widgets( $method );
				$method['supportsInstallmentPayments'] = $this->supports_installment_payments( $method );
			}

			return $payment_methods;
		} catch ( Throwable $e ) {
			$this->logger->log_throwable( $e, __FUNCTION__, __CLASS__ );
			return array();
		}
	}

	/**
	 * Look for available payment methods which can be used with the widget
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_all_widget_compatible_payment_methods( string $store_id, ?string $merchant ): array {
		$compatible_payment_methods = array();
		foreach ( $this->get_all_payment_methods( $store_id, $merchant ) as $method ) {
			if ( $method['supportsWidgets'] ) {
				$compatible_payment_methods[] = $method;
			}
		}
		return $compatible_payment_methods;
	}

	/**
	 * Look for available payment methods that can be used with part payments
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_all_mini_widget_compatible_payment_methods( string $store_id, ?string $merchant ): array {
		$compatible_payment_methods = array();
		foreach ( $this->get_all_payment_methods( $store_id, $merchant ) as $method ) {
			if ( $method['supportsInstallmentPayments'] ) {
				$compatible_payment_methods[] = $method;
			}
		}
		return $compatible_payment_methods;
	}

	/**
	 * Look for available payment methods which can be used with the widget
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, string>[]
	 */
	public function get_cart_widget( string $store_id, ?string $merchant ): array {
		$compatible_payment_methods = array();
		foreach ( $this->get_all_payment_methods( $store_id, $merchant ) as $method ) {
			if ( $method['supportsWidgets'] ) {
				$compatible_payment_methods[] = $method;
			}
		}
		return $compatible_payment_methods;
	}

	/**
	 * Check if the payment method can be displayed in the widget
	 * 
	 * @param array<string, string> $method the payment method. Must contain 'product' at least.
	 */
	private function supports_widgets( array $method ): bool {
		return isset( $method['product'] ) && in_array( $method['product'], array( 'i1', 'pp5', 'pp3', 'pp6', 'pp9', 'sp1' ), true );
	}

	/**
	 * Check if the payment method represents a part payment
	 * 
	 * @param array<string, string> $method the payment method. Must contain 'product' at least.
	 */
	private function supports_installment_payments( array $method ): bool {
		// phpcs:ignore
		// return isset( $method['product'] ) && in_array( $method['product'], array( 'pp5', 'pp3', 'pp6', 'pp9', 'sp1' ), true );
		return isset( $method['product'] ) && in_array( $method['product'], array( 'pp3' ), true );
	}

	/**
	 * Check if the payment method data matches a valid payment method.
	 */
	public function is_payment_method_data_valid( Payment_Method_Data $data ): bool {
		foreach ( $this->get_payment_methods() as $pm ) {
			if ( $pm['product'] === $data->product && $pm['campaign'] === $data->campaign ) {
				return true;
			}
		}
		return false;
	}
}

<?php
/**
 * Shop Order service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Webhook\Services;

use DateTime;
use Exception;
use SeQura\Core\BusinessLogic\Domain\Order\Exceptions\OrderNotFoundException;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\Order\OrderRequestStatusMapping;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders\Interface_Create_Order_Request_Builder;

use WC_Order;

/**
 * Shop Order service
 */
class Shop_Order_Service implements ShopOrderService {

	/**
	 * Shop order repository
	 * 
	 * @var SeQuraOrderRepositoryInterface
	 */
	private $sequra_order_repository;

	/**
	 * Logger
	 * 
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Create order request builder
	 *
	 * @var Interface_Create_Order_Request_Builder
	 */
	private $create_order_request_builder;

	/**
	 * Constructor
	 */
	public function __construct(
		SeQuraOrderRepositoryInterface $sequra_order_repository,
		Interface_Logger_Service $logger,
		Interface_Create_Order_Request_Builder $create_order_request_builder
	) {
		$this->sequra_order_repository      = $sequra_order_repository;
		$this->logger                       = $logger;
		$this->create_order_request_builder = $create_order_request_builder;
	}

	/**
	 * Gets the CreateOrderRequest for the order.
	 * 
	 * @param string $orderReference Order reference.
	 * @throws Exception
	 * @return CreateOrderRequest
	 */
	public function getCreateOrderRequest( string $orderReference ): CreateOrderRequest {

		$sq_order = $this->sequra_order_repository->getByOrderReference( $orderReference );
		if ( ! $sq_order ) {
			throw new Exception( 'SeQura order not found. Reference: ' . \esc_html( $orderReference ) );
		}
		// TODO: Add unit test for this.
		$wc_order = $this->get_wc_order( $sq_order );
		if ( ! $wc_order instanceof \WC_Order ) {
			throw new Exception( \esc_html( "WC order with ID '{$this->get_wc_order_id($sq_order)}' not found" ) );
		}

		$this->create_order_request_builder->set_current_order( $wc_order );
		return $this->create_order_request_builder->build();
	}
	
	/**
	 * Updates status of the order in the shop system based on the provided status.
	 *
	 * @return mixed
	 */
	public function updateStatus( Webhook $webhook, string $wc_status, ?int $reason_code = null, ?string $message = null ) {    
		switch ( $webhook->getSqState() ) {
			case OrderStates::STATE_CANCELLED:
				$this->cancel_order( $webhook, $wc_status );
				break;
			default:
				// OrderStates::STATE_APPROVED, OrderStates::STATE_NEEDS_REVIEW. Others will throw an exception.
				$this->update_order_to_status( $webhook, $wc_status );
				break;
		}
	}

	/**
	 * Provides ids of orders that should be included in the delivery report.
	 *
	 * @param int $page
	 * @param int $limit
	 *
	 * @return string[] | int[]
	 */
	public function getReportOrderIds( int $page, int $limit = 5000 ): array {
		return array();
	}

	/**
	 * Provides ids of orders that should be included in the statistical report.
	 *
	 * @return string[] | int[]
	 */
	public function getStatisticsOrderIds( int $page, int $limit = 5000 ): array {
		$to_date   = new DateTime();
		$from_date = clone $to_date;
		$from_date->modify( '-7 days' );
		$from_date_str = $from_date->format( 'Y-m-d H:i:s' );
		$to_date_str   = $to_date->format( 'Y-m-d H:i:s' );

		$args = array(
			'date_created' => $from_date_str . '...' . $to_date_str,
			'limit'        => $limit,
			'return'       => 'ids',
		);
		if ( -1 !== $limit ) {
			$args['paged'] = $page + 1;
		}
		return \wc_get_orders( $args );
	}

	/**
	 * Get the URL of the order in the shop system.
	 *
	 * @return string
	 */
	public function getOrderUrl( string $merchant_reference ): string {
		$order = \wc_get_order( \absint( $merchant_reference ) );
		return $order instanceof WC_Order ? $order->get_view_order_url() : '';
	}

	/**
	 * Updates the WC order and SeQuraOrder statuses.
	 *
	 * @throws Exception
	 */
	private function update_order_to_status( Webhook $webhook, string $status ): void {
		$order = $this->get_order( $webhook );
		if ( ! $order ) {
			throw new Exception( 'WC Order not found' );
		}

		$this->update_sequra_order_status( $webhook );
		// translators: %1$d: WooCommerce Order ID.
		$order->add_order_note( sprintf( esc_html__( 'Order ref sent to seQura: %1$d', 'sequra' ), $order->get_id() ) );
		
		switch ( $webhook->getSqState() ) {
			case OrderStates::STATE_APPROVED:
				$order->add_order_note( esc_html__( 'Payment accepted by seQura', 'sequra' ) );
				$order->payment_complete( $webhook->getOrderRef() );
				break;
			case OrderStates::STATE_NEEDS_REVIEW:
				$order->set_transaction_id( $webhook->getOrderRef() );
				$order->update_status( $status, esc_html__( 'Payment is in review by seQura', 'sequra' ) );        
				break;
		}
	}

	/**
	 * Updates the SeQuraOrder status.
	 *
	 * @throws OrderNotFoundException
	 * @throws InvalidOrderStateException
	 */
	private function update_sequra_order_status( Webhook $webhook ): void {
		$sq_order = $this->get_sequra_order( $webhook->getOrderRef() );
		$sq_order->setState( OrderRequestStatusMapping::mapOrderRequestStatus( $webhook->getSqState() ) );
		$this->sequra_order_repository->setSeQuraOrder( $sq_order );
	}

	/**
	 * Gets the SeQura order.
	 *
	 * @throws OrderNotFoundException
	 */
	private function get_sequra_order( string $order_reference ): SeQuraOrder {
		$sq_order = $this->sequra_order_repository->getByOrderReference( $order_reference );
		if ( ! $sq_order ) {
			throw new OrderNotFoundException( \esc_html( "SeQura order with reference $order_reference is not found." ), 404 );
		}

		return $sq_order;
	}

	/**
	 * Gets the WC order.
	 *
	 * @throws OrderNotFoundException
	 */
	private function get_order( Webhook $webhook ): ?WC_Order {
		$sq_order = $this->get_sequra_order( $webhook->getOrderRef() );
		$order    = $this->get_wc_order( $sq_order );
		
		if ( ! $order instanceof WC_Order ) {
			$this->logger->log_debug(
				'WC Order not found for orderRef',
				__FUNCTION__,
				__CLASS__,
				array( 
					new LogContextData( 'orderRef', $webhook->getOrderRef() ),
					new LogContextData( 'orderID', $this->get_wc_order_id( $sq_order ) ),
				) 
			);
			return null;
		}

		return $order;
	}

	/**
	 * Gets the WC order.
	 * 
	 * @param SeQuraOrder $sq_order SeQura order.
	 */
	private function get_wc_order( $sq_order ): ?WC_Order {
		return \wc_get_order( $this->get_wc_order_id( $sq_order ) );
	}

	/**
	 * Gets the WC order ID.
	 * 
	 * @param SeQuraOrder $sq_order SeQura order.
	 */
	private function get_wc_order_id( $sq_order ): int {
		$params   = $sq_order->getMerchant()->getNotificationParameters();
		$order_id = $params['order'] ?? 0;
		return absint( $order_id );
	}

	/**
	 * Cancels the order in WC and updates the SeQuraOrder status.
	 *
	 * @throws InvalidOrderStateException|OrderNotFoundException
	 */
	private function cancel_order( Webhook $webhook, string $status ): void {
		$order = $this->get_order( $webhook );
		if ( ! $order ) {
			throw new OrderNotFoundException( esc_html( "WC order with ID {$webhook->getOrderRef1()} not found." ), 404 );
		}

		$sq_order = $this->get_sequra_order( $webhook->getOrderRef() );
		$this->sequra_order_repository->deleteOrder( $sq_order );

		$order->update_status( $status, esc_html__( 'Order cancelled by seQura.', 'sequra' ) );
		$order->save();
	}
}

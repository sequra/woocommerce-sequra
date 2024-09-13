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
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\Order\OrderRequestStatusMapping;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Order_Service;
use Throwable;
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
	 * Order status service
	 * 
	 * @var Order_Status_Settings_Service
	 */
	private $order_status_service;

	/**
	 * Logger
	 * 
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Order service
	 * 
	 * @var Order_Service
	 */
	private $order_service;

	/**
	 * Constructor
	 */
	public function __construct(
		Order_Status_Settings_Service $order_status_service,
		SeQuraOrderRepositoryInterface $sequra_order_repository,
		Interface_Logger_Service $logger,
		Order_Service $order_service
	) {
		$this->order_status_service    = $order_status_service;
		$this->sequra_order_repository = $sequra_order_repository;
		$this->logger                  = $logger;
		$this->order_service           = $order_service;
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
		// TODO: check if we need to fill this or keep it empty.
		// Now an 409 error is thrown when the delivery report is sent.
		// Message is: {"errors":["orders.0.cart.items.0 is not allowed per your contract"]}.
		return array();
	
		//phpcs:disable
		// $args = array(
		// 	'payment_method' => 'sequra',
		// 	$this->order_service->get_sent_to_sequra_meta_key() => array( 'compare' => 'NOT EXISTS' ),
		// 	/**
		// 	 * Filter the order statuses to consider as shipped.
		// 	 *
		// 	 * @since 2.0.0
		// 	 */
		// 	'status'         => apply_filters( 'woocommerce_sequracheckout_sent_statuses', array( $this->order_status_service->get_shop_status_completed() ) ),
		// 	'limit'          => $limit,
		// 	'return'         => 'ids',
		// );
		// if ( -1 !== $limit ) {
		// 	$args['paged'] = $page + 1;
		// }
		// return wc_get_orders( $args );
		//phpcs:enable
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
		return wc_get_orders( $args );
	}

	/**
	 * Get the URL of the order in the shop system.
	 *
	 * @return string
	 */
	public function getOrderUrl( string $merchant_reference ): string {
		$order = wc_get_order( absint( $merchant_reference ) );
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
		$order->set_status( $status );
		

		switch ( $webhook->getSqState() ) {
			case OrderStates::STATE_APPROVED:
				$order->add_order_note( esc_html__( 'Payment accepted by seQura', 'sequra' ) );
				$order->payment_complete(); // If all items are virtual, mark as complete. Else, remain pending.
				break;
			case OrderStates::STATE_NEEDS_REVIEW:
				$order->add_order_note( esc_html__( 'Payment is in review by seQura', 'sequra' ) );
				$order->set_status( $status );
				break;
		}
		
		$order->save();
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
			throw new OrderNotFoundException( esc_html( "SeQura order with reference $order_reference is not found." ), 404 );
		}

		return $sq_order;
	}

	/**
	 * Gets the WC order.
	 *
	 * @throws OrderNotFoundException
	 */
	private function get_order( Webhook $webhook ): ?WC_Order {
		$order_ref1 = $webhook->getOrderRef1();
		if ( empty( $order_ref1 ) ) {
			$order_ref1 = $this->get_sequra_order( $webhook->getOrderRef() )
			->getMerchantReference()
			->getOrderRef1();
		}
		
		// TODO: change to store_id blog in a multisite environment. Or maybe it is already set via URL?
		$order = wc_get_order( absint( $order_ref1 ) );
		
		if ( ! $order instanceof WC_Order ) {
			$this->logger->log_debug( 'WC Order not found for orderRef1', array( 'orderRef1' => $order_ref1 ) );
			return null;
		}

		return $order;
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

		$this->update_sequra_order_status( $webhook );

		$order->update_status( $status, esc_html__( 'Order cancelled by seQura.', 'sequra' ) );
		$order->save();
	}
}

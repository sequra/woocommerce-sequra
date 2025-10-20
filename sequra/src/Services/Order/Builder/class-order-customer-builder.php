<?php
/**
 * Order Customer Builder
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order\Builder;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\PreviousOrder;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WC_DateTime;
use WC_Order;

/**
 * Order Customer Builder
 */
class Order_Customer_Builder implements Interface_Order_Customer_Builder {

	/**
	 * Shopper service
	 * 
	 * @var Interface_Shopper_Service
	 */
	private $shopper_service;

	/**
	 * Order status settings service
	 * 
	 * @var Order_Status_Settings_Service
	 */
	private $order_status_service;

	/**
	 * Pricing service
	 * 
	 * @var Interface_Pricing_Service
	 */
	private $pricing_service;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Shopper_Service $shopper_service,
		Order_Status_Settings_Service $order_status_service,
		Interface_Pricing_Service $pricing_service
	) {
		$this->shopper_service      = $shopper_service;
		$this->order_status_service = $order_status_service;
		$this->pricing_service      = $pricing_service;
	}
	
	/**
	 * Get customer
	 */
	public function build( ?WC_Order $order, string $lang, int $fallback_user_id = 0, string $fallback_ip = '', string $fallback_user_agent = '' ): Customer {
		$current_user_id = $order ? $order->get_customer_id() : $fallback_user_id;
		$logged_in       = $current_user_id > 0;
		$ref             = $logged_in ? $current_user_id : null;
		$ip              = $order ? $order->get_customer_ip_address( 'edit' ) : $fallback_ip;
		$user_agent      = $order ? $order->get_customer_user_agent( 'edit' ) : $fallback_user_agent;

		return new Customer(
			$this->shopper_service->get_email( $order ),
			$lang,
			$ip,
			$user_agent,
			$this->shopper_service->get_first_name( $order, true ),
			$this->shopper_service->get_last_name( $order, true ),
			$this->shopper_service->get_shopper_title( $order ), // title.
			$ref,
			$this->shopper_service->get_dob( $order ), // dateOfBirth.
			$this->shopper_service->get_nin( $order ), // nin.
			$this->shopper_service->get_company( $order, true ),
			$this->shopper_service->get_vat( $order, true ), // vatNumber.
			$this->shopper_service->get_shopper_created_at( $order ), // createdAt.
			$this->shopper_service->get_shopper_updated_at( $order ), // updatedAt.
			$this->shopper_service->get_shopper_rating( $order ), // rating.
			null, // ninControl.
			$this->get_previous_orders( $current_user_id ),
			null, // vehicle.
			$logged_in
		);
	}

	/**
	 * Get previous orders
	 * 
	 * @return PreviousOrder[]
	 */
	private function get_previous_orders( int $customer_id ): array {
		$previous_orders = array();

		$order_statuses = $this->order_status_service->getOrderStatusSettings();

		if ( ! $order_statuses ) {
			return $previous_orders;
		}

		$statuses = $this->order_status_service->get_shop_status_completed();
		foreach ( $order_statuses as $order_status ) {
			if ( $order_status->getSequraStatus() === OrderStates::STATE_APPROVED ) {
				// Use the default status if the shop status is not set.
				$statuses[] = empty( $order_status->getShopStatus() ) ? 'wc-processing' : $order_status->getShopStatus();
			}
		}

		$orders = array();
		if ( $customer_id ) {
			/**
			 * Get previous orders
			 *
			 * @var WC_Order[] $previous_orders
			 */
			$orders = \wc_get_orders(
				array(
					'limit'    => -1,
					'customer' => $customer_id,
					'status'   => $statuses,
				) 
			);
		}

		if ( is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$previous_orders[] = $this->get_previous_order( $order );
			}
		}
		
		return $previous_orders;
	}

	/**
	 * Build previous order from WC_Order
	 */
	private function get_previous_order( WC_Order $order ): PreviousOrder {
		/**
		 * Order date
		 *
		 * @var WC_DateTime $date
		 */
		$date     = $order->get_date_created( 'edit' );
		$postcode = $this->shopper_service->get_postcode( $order );
		$country  = $this->shopper_service->get_country( $order );

		return new PreviousOrder(
			$date ? $date->date( 'c' ) : '',
			$this->pricing_service->to_cents( (float) $order->get_total( 'edit' ) ),
			$order->get_currency(),
			$order->get_status( 'edit' ),
			\wc_get_order_status_name( $order->get_status( 'edit' ) ),
			$order->get_payment_method( 'edit' ),
			$order->get_payment_method_title( 'edit' ),
			$postcode ? $postcode : null,
			$country ? $country : null
		);
	}
}

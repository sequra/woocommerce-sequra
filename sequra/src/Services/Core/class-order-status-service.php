<?php
/**
 * Order Status Service
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\OrderStatus\Models\OrderStatus;

/**
 * Order Status Service
 */
class Order_Status_Service implements ShopOrderStatusesServiceInterface {
	
	/**
	 * Returns all order statuses of the shop system.
	 *
	 * @return OrderStatus[]
	 */
	public function getShopOrderStatuses(): array {
		$shop_order_statuses = array();
		foreach ( wc_get_order_statuses() as $key => $value ) {
			$shop_order_statuses[] = new OrderStatus( $key, $value );
		}
		return $shop_order_statuses;
	}
}

<?php
/**
 * Shop Order Status Service
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\ShopOrderStatuses;

use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\OrderStatus\Models\OrderStatus;

/**
 * Shop Order Status Service
 */
class Shop_Order_Status_Service implements ShopOrderStatusesServiceInterface {
	
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

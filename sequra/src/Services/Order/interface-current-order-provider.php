<?php
/**
 * Current Order Provider Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use WC_Order;

/**
 * Store and provide the current order data
 */
interface Interface_Current_Order_Provider {

	/**
	 * Set the current order
	 * 
	 * @param WC_Order|null $order The order to set, or null to clear the current order
	 */
	public function set( ?WC_Order $order );

	/**
	 * Get the current order
	 * 
	 * @return WC_Order|null The current order, or null if none is set
	 */
	public function get(): ?WC_Order;
}

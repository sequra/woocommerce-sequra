<?php
/**
 * Order Delivery Method Builder Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\DeliveryMethod;
use WC_Order;

/**
 * Order Delivery Method Builder Interface
 */
interface Interface_Order_Delivery_Method_Builder {

	/**
	 * Get Delivery Method
	 */
	public function build( ?WC_Order $order ): DeliveryMethod;
}

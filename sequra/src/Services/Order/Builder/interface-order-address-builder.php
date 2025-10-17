<?php
/**
 * Order Address Builder Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order\Builder;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Address;
use WC_Order;

/**
 * Order Address Builder Interface
 */
interface Interface_Order_Address_Builder {

	/**
	 * Get address
	 */
	public function build( ?WC_Order $order, bool $is_delivery ): Address;
}

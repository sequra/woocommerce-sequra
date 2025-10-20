<?php
/**
 * Order Customer Builder Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Order\Builder;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Customer;
use WC_Order;

/**
 * Order Customer Builder Interface
 */
interface Interface_Order_Customer_Builder {

	/**
	 * Get customer
	 */
	public function build( ?WC_Order $order, string $lang, int $fallback_user_id = 0, string $fallback_ip = '', string $fallback_user_agent = '' ): Customer;
}

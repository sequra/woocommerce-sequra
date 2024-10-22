<?php
/**
 * Order controller interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers
 */

namespace SeQura\WC\NoAddress\Controller\Hooks\Order;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;

/**
 * Handle hooks related to order management
 */
interface Interface_Order_Controller {
/**
	 * Add no address to the merchant options.
	 * 
	 * @param ?Options $options 
	 * @return ?Options
	 */
	public function add_no_address($options);
}

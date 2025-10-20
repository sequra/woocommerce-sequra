<?php
/**
 * Implementation of OrderCreationInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Order;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Integration\Order\OrderCreationInterface;

/**
 * Implementation of the OrderCreationInterface.
 */
class Order_Creation implements OrderCreationInterface {


	/**
	 * Creates shop order and returns shop order reference.
	 *
	 * @throws Exception
	 */
	public function createOrder( string $cartId ): string {
		throw new Exception( 'This is handled in WooCommerce by the payment gateway and should not be called.' );
	}
}

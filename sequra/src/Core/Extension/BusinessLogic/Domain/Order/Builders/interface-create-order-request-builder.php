<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Builders;

use SeQura\Core\BusinessLogic\Domain\Order\Builders\CreateOrderRequestBuilder;

/**
 * Wrapper to ease the read and write of configuration values.
 */
interface Interface_Create_Order_Request_Builder extends CreateOrderRequestBuilder {

	/**
	 * Check if the builder is allowed for the current settings 
	 */
	public function is_allowed(): bool;
}

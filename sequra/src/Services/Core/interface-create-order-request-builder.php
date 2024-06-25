<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\Core\BusinessLogic\Domain\Order\Builders\CreateOrderRequestBuilder;
use WC_Order;

/**
 * Wrapper to ease the read and write of configuration values.
 */
interface Interface_Create_Order_Request_Builder extends CreateOrderRequestBuilder {

	/**
	 * Set current order
	 */
	public function set_current_order( ?WC_Order $order ): void;

	/**
	 * Check if the builder is allowed for the current settings 
	 */
	public function is_allowed_for( GeneralSettingsResponse $general_settings_response ): bool;
}

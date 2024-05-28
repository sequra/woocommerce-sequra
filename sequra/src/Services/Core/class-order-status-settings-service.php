<?php
/**
 * Order Status Service
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Models\OrderStatusMapping;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Services\OrderStatusSettingsService;

/**
 * Order Status Service
 */
class Order_Status_Settings_Service extends OrderStatusSettingsService {
	
	/**
	 * Returns default status mappings.
	 *
	 * @return OrderStatusMapping[]
	 */
	protected function getDefaultStatusMappings(): array {
		return array(
			new OrderStatusMapping( OrderStates::STATE_APPROVED, 'wc-completed' ),
			new OrderStatusMapping( OrderStates::STATE_NEEDS_REVIEW, 'wc-on-hold' ),
			new OrderStatusMapping( OrderStates::STATE_CANCELLED, 'wc-cancelled' ),
		);
	}
}

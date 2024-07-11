<?php
/**
 * Class Options
 *
 * @package SeQura\WC\Core\BusinessLogic\Domain\Order\Models\OrderRequest
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services;

use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Models\OrderStatusMapping;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Services\OrderStatusSettingsService;

/**
 * Class OrderStatusSettingsService
 *
 * @package SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Services
 */
class Order_Status_Settings_Service extends OrderStatusSettingsService {

	/**
	 * Returns WooCommerce status for approved orders.
	 */
	public function get_shop_status_approved(): string {
		return 'wc-processing';
	}

	/**
	 * Returns WooCommerce status for orders that need review.
	 */
	public function get_shop_status_needs_review(): string {
		return 'wc-on-hold';
	}

	/**
	 * Returns WooCommerce status for cancelled orders.
	 */
	public function get_shop_status_cancelled(): string {
		return 'wc-cancelled';
	}

	/**
	 * Translate the order status from WC to SeQura using the current configuration.
	 */
	public function map_status_from_shop_to_sequra( string $shop_status ): ?string {
		$status_mappings = $this->getOrderStatusSettings();
		if ( is_array( $status_mappings ) ) {
			foreach ( $status_mappings as $status_mapping ) {
				if ( $status_mapping->getShopStatus() === $shop_status ) {
					return $status_mapping->getSequraStatus();
				}
			}
		}
		return null;
	}

	/**
	 * Returns default status mappings.
	 *
	 * @return OrderStatusMapping[]
	 */
	protected function getDefaultStatusMappings(): array {
		return array(
			new OrderStatusMapping( OrderStates::STATE_APPROVED, $this->get_shop_status_approved() ),
			new OrderStatusMapping( OrderStates::STATE_NEEDS_REVIEW, $this->get_shop_status_needs_review() ),
			new OrderStatusMapping( OrderStates::STATE_CANCELLED, $this->get_shop_status_cancelled() ),
		);
	}
}

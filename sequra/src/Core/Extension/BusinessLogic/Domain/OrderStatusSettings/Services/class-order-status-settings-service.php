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
	 * Cached order status mappings for current request lifecycle.
	 *
	 * @var OrderStatusMapping[]|null
	 */
	private $cached_order_status_settings;

	/**
	 * Returns WooCommerce status for completed orders.
	 */
	public function get_shop_status_completed( bool $unprefixed = false ): array {
		$order_status_settings = $this->get_order_status_settings_cached();
		$find_status_callback  = function ( OrderStatusMapping $mapping ) {
			return $mapping->getSequraStatus() === OrderStates::STATE_APPROVED;
		};
		$mapping               = array_find( $order_status_settings, $find_status_callback );
		if ( null === $mapping ) {
			// Guard: use the default value if not set in repository.
			$mapping = array_find( $this->getDefaultStatusMappings(), $find_status_callback );
		}
		return array( $unprefixed ? $this->unprefixed_shop_status( $mapping->getShopStatus() ) : $mapping->getShopStatus() );
	}

	/**
	 * Retrieves order status settings from cache or repository/default mappings.
	 *
	 * @return OrderStatusMapping[]
	 */
	private function get_order_status_settings_cached(): array {
		if ( ! $this->cached_order_status_settings ) {
			$this->cached_order_status_settings = parent::getOrderStatusSettings();
		}

		return $this->cached_order_status_settings;
	}

	/**
	 * Saves order status settings and refreshes cache.
	 *
	 * @param OrderStatusMapping[] $orderStatusMappings
	 */
	public function saveOrderStatusSettings( array $orderStatusMappings ): void {
		parent::saveOrderStatusSettings( $orderStatusMappings );
		$this->cached_order_status_settings = $orderStatusMappings;
	}

	/**
	 * Remove prefix from WooCommerce status.
	 */
	public function unprefixed_shop_status( string $shop_status ): string {
		return str_replace( 'wc-', '', $shop_status );
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
			new OrderStatusMapping( OrderStates::STATE_APPROVED, 'wc-processing' ),
			new OrderStatusMapping( OrderStates::STATE_NEEDS_REVIEW, 'wc-on-hold' ),
			new OrderStatusMapping( OrderStates::STATE_CANCELLED, 'wc-cancelled' ),
			new OrderStatusMapping( OrderStates::STATE_SHIPPED, 'wc-completed' ),
		);
	}
}

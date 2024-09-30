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
	public function get_shop_status_cancelled( bool $unprefixed = false ): string {
		$status = 'wc-cancelled';
		return $unprefixed ? $this->unprefixed_shop_status( $status ) : $status;
	}

	/**
	 * Returns WooCommerce status for completed orders.
	 */
	public function get_shop_status_completed( bool $unprefixed = false ): array {

		$status = (array) apply_filters_deprecated( 'woocommerce_sequracheckout_sent_statuses', array( array( 'wc-completed' ) ), '3.0.0', 'sequra_shop_status_completed' );
		/**
		 * Filter the WooCommerce status for completed.
		 *
		 * @since 3.0.0
		 */
		$status = apply_filters( 'sequra_shop_status_completed', $status );

		if ( $unprefixed ) {
			$status = array_map( array( $this, 'unprefixed_shop_status' ), $status );
		}

		return $status;
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
			new OrderStatusMapping( OrderStates::STATE_APPROVED, $this->get_shop_status_approved() ),
			new OrderStatusMapping( OrderStates::STATE_NEEDS_REVIEW, $this->get_shop_status_needs_review() ),
			new OrderStatusMapping( OrderStates::STATE_CANCELLED, $this->get_shop_status_cancelled() ),
		);
	}
}

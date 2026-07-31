<?php
/**
 * Tests for the Order_Status_Settings_Service mapping.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core;

use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\RepositoryContracts\OrderStatusSettingsRepositoryInterface;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use WP_UnitTestCase;

class OrderStatusSettingsServiceTest extends WP_UnitTestCase {

	/**
	 * Service with no stored mapping, so it falls back to the default (wc- prefixed) mappings.
	 */
	private function service_with_default_mappings(): Order_Status_Settings_Service {
		$repo = $this->createMock( OrderStatusSettingsRepositoryInterface::class );
		$repo->method( 'getOrderStatusMapping' )->willReturn( null );
		$shop_statuses = $this->createMock( ShopOrderStatusesServiceInterface::class );
		return new Order_Status_Settings_Service( $repo, $shop_statuses );
	}

	public function testMapsUnprefixedShopStatusToSequraState(): void {
		// WooCommerce hooks and $order->get_status() return unprefixed slugs, while the default
		// mappings store the wc- prefixed form. The mapping must match across the prefix, otherwise
		// the affiliate conversion/cancellation trigger silently never fires.
		$service = $this->service_with_default_mappings();

		$this->assertSame( OrderStates::STATE_APPROVED, $service->map_status_from_shop_to_sequra( 'processing' ) );
		$this->assertSame( OrderStates::STATE_CANCELLED, $service->map_status_from_shop_to_sequra( 'cancelled' ) );
	}

	public function testMapsPrefixedShopStatusToo(): void {
		$service = $this->service_with_default_mappings();

		$this->assertSame( OrderStates::STATE_APPROVED, $service->map_status_from_shop_to_sequra( 'wc-processing' ) );
	}

	public function testReturnsNullForUnmappedStatus(): void {
		$service = $this->service_with_default_mappings();

		$this->assertNull( $service->map_status_from_shop_to_sequra( 'does-not-exist' ) );
	}
}

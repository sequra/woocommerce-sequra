<?php
/**
 * Tests for the Shopper service country resolution.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Shopper;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Services\Shopper\Shopper_Service;
use WC_Customer;
use WC_Order;
use WP_UnitTestCase;

class ShopperServiceTest extends WP_UnitTestCase {

	/** @var Shopper_Service */
	private $shopper_service;

	private $original_customer;

	public function set_up(): void {
		$this->original_customer = isset( WC()->customer ) ? WC()->customer : null;
		$this->shopper_service   = new Shopper_Service( $this->createMock( StoreContext::class ) );
	}

	public function tear_down(): void {
		remove_all_filters( 'sequra_shopper_country' );
		WC()->customer = $this->original_customer;
	}

	public function testGetCountry_orderWithShippingCountry_returnsShipping(): void {
		$order = new WC_Order();
		$order->set_shipping_country( 'PT' );
		$order->set_billing_country( 'ES' );

		$this->assertSame( 'PT', $this->shopper_service->get_country( $order ) );
	}

	public function testGetCountry_orderShippingEmpty_fallsBackToBilling(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'ES' );

		$this->assertSame( 'ES', $this->shopper_service->get_country( $order ) );
	}

	public function testGetCountry_sessionWithShippingCountry_returnsShipping(): void {
		$customer = new WC_Customer();
		$customer->set_shipping_country( 'FR' );
		$customer->set_billing_country( 'ES' );
		WC()->customer = $customer;

		$this->assertSame( 'FR', $this->shopper_service->get_country( null ) );
	}

	public function testGetCountry_sessionShippingEmpty_fallsBackToBilling(): void {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'IT' );
		WC()->customer = $customer;

		$this->assertSame( 'IT', $this->shopper_service->get_country( null ) );
	}

	public function testGetCountry_noCountryAnywhere_returnsEmpty(): void {
		$order = new WC_Order();

		$this->assertSame( '', $this->shopper_service->get_country( $order ) );
	}

	public function testGetCountry_filterOverridesResolvedCountry(): void {
		$order = new WC_Order();
		$order->set_shipping_country( 'ES' );

		add_filter(
			'sequra_shopper_country',
			function () {
				return 'PT';
			}
		);

		$this->assertSame( 'PT', $this->shopper_service->get_country( $order ) );
	}

	public function testGetCountry_filterValueIsUppercased(): void {
		$order = new WC_Order();
		$order->set_shipping_country( 'ES' );

		add_filter(
			'sequra_shopper_country',
			function () {
				return 'pt';
			}
		);

		$this->assertSame( 'PT', $this->shopper_service->get_country( $order ) );
	}

	public function testGetCountry_filterAppliesWhenNoCountryResolved(): void {
		$order = new WC_Order();

		add_filter(
			'sequra_shopper_country',
			function () {
				return 'FR';
			}
		);

		$this->assertSame( 'FR', $this->shopper_service->get_country( $order ) );
	}
}

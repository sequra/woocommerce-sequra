<?php
/**
 * Tests for the Order Address Builder country handling.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Order\Builder;

use SeQura\WC\Services\Order\Builder\Order_Address_Builder;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WP_UnitTestCase;

class OrderAddressBuilderTest extends WP_UnitTestCase {

	/** @var Interface_Shopper_Service */
	private $shopper_service;

	/** @var Order_Address_Builder */
	private $builder;

	public function set_up(): void {
		$this->shopper_service = $this->createMock( Interface_Shopper_Service::class );
		$this->builder         = new Order_Address_Builder( $this->shopper_service );
	}

	public function testBuild_emptyCountry_isNotDefaultedToSpain(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( '' );

		$address = $this->builder->build( null, true );

		$this->assertSame( '', $address->getCountryCode() );
	}

	public function testBuild_resolvedCountry_isUsed(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( 'PT' );

		$address = $this->builder->build( null, true );

		$this->assertSame( 'PT', $address->getCountryCode() );
	}

	public function testBuild_emptyCountry_usesFallbackCountry(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( '' );

		$address = $this->builder->build( null, true, 'IT' );

		$this->assertSame( 'IT', $address->getCountryCode() );
	}

	public function testBuild_resolvedCountry_ignoresFallback(): void {
		$this->shopper_service->method( 'get_country' )->willReturn( 'PT' );

		$address = $this->builder->build( null, true, 'IT' );

		$this->assertSame( 'PT', $address->getCountryCode() );
	}
}

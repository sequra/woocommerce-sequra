<?php
/**
 * Tests for the Cart_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Cart;

use SeQura\WC\Services\Cart\Cart_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use WP_UnitTestCase;
use WC_Order;
use WC_Order_Item_Fee;

class CartServiceTest extends WP_UnitTestCase {

	/** @var Cart_Service */
	private $cart_service;

	public function set_up(): void {
		$product_service = $this->createMock( Interface_Product_Service::class );
		$pricing_service = $this->createMock( Interface_Pricing_Service::class );
		$logger          = $this->createMock( Interface_Logger_Service::class );
		$shopper_service = $this->createMock( Interface_Shopper_Service::class );

		$pricing_service->method( 'to_cents' )->willReturnCallback(
			static function ( $amount ) {
				return (int) round( $amount * 100 );
			}
		);

		$this->cart_service = new Cart_Service( $product_service, $pricing_service, $logger, $shopper_service );
	}

	public function testHandlingItemIncludesFeeTax(): void {
		$order = $this->make_order( array( $this->make_fee( '10.00', '2.10' ) ) );

		$items = $this->cart_service->get_handling_items( $order );

		$this->assertCount( 1, $items );
		$this->assertSame( 1210, $items[0]->getTotalWithTax() );
	}

	public function testDiscountItemIncludesFeeTax(): void {
		$order = $this->make_order( array( $this->make_fee( '-10.00', '-2.10' ) ) );

		$items = $this->cart_service->get_discount_items( $order );

		$this->assertCount( 1, $items );
		$this->assertSame( -1210, $items[0]->getTotalWithTax() );
	}

	public function testZeroTaxFeeIsUnchanged(): void {
		$order = $this->make_order( array( $this->make_fee( '10.00', '0' ) ) );

		$items = $this->cart_service->get_handling_items( $order );

		$this->assertCount( 1, $items );
		$this->assertSame( 1000, $items[0]->getTotalWithTax() );
	}

	/**
	 * @param string $total Fee total without tax.
	 * @param string $tax   Fee tax.
	 * @return WC_Order_Item_Fee&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function make_fee( string $total, string $tax ) {
		$fee = $this->createMock( WC_Order_Item_Fee::class );
		$fee->method( 'get_total' )->willReturn( $total );
		$fee->method( 'get_total_tax' )->willReturn( $tax );
		$fee->method( 'get_name' )->willReturn( 'fee' );
		return $fee;
	}

	/**
	 * @param array<WC_Order_Item_Fee> $fees Fee items returned for the 'fee' item type.
	 * @return WC_Order&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function make_order( array $fees ) {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_items' )->willReturnCallback(
			static function ( $type = '' ) use ( $fees ) {
				return 'fee' === $type ? $fees : array();
			}
		);
		return $order;
	}
}

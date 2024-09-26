<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Report;

// Load required classes.
require_once __DIR__ . '/../../Core/BusinessLogic/Domain/Multistore/StoreContextMock.php';
require_once __DIR__ . '/../../Core/BusinessLogic/Domain/Multistore/StoreContext.php';

use Exception;
use WP_UnitTestCase;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\Core\BusinessLogic\Domain\Order\Service\OrderService;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Order\Order_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Tests\Core\Extension\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Tests\Core\Extension\BusinessLogic\Domain\Multistore\StoreContextMock;
use WC_DateTime;
use WC_Order;

class OrderServiceTest extends WP_UnitTestCase {

	private $order_service;
	private $payment_service;
	private $pricing_service;
	private $order_status_service;
	private $configuration;
	private $core_order_service;
	private $cart_service;
	private $store_context_mock;

	public function set_up(): void {        
		$this->payment_service      = $this->createMock( Interface_Payment_Service::class );
		$this->pricing_service      = $this->createMock( Interface_Pricing_Service::class );
		$this->order_status_service = $this->createMock( Order_Status_Settings_Service::class );
		$this->configuration        = $this->createMock( Configuration::class );
		$this->core_order_service   = $this->createMock( OrderService::class );
		$this->cart_service         = $this->createMock( Interface_Cart_Service::class );
		$this->store_context_mock   = $this->createMock( StoreContextMock::class );
		
		$this->order_service = new Order_Service(
			$this->payment_service,
			$this->pricing_service,
			$this->order_status_service,
			$this->configuration,
			$this->core_order_service,
			$this->cart_service,
			new StoreContext( $this->store_context_mock )
		);
	}

	public function tear_down() {
	}

	public function testUpdateSequraOrderStatus_onException_throwException() {
		// Setup.
		$date  = new WC_DateTime();
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );
		$order->method( 'get_currency' )->willReturn( 'EUR' );
		$order->method( 'get_date_completed' )->willReturn( $date );
		$order->method( 'get_payment_method' )->willReturn( 'sequra' );
		$order->method( 'get_meta' )
		->with( 
			$this->logicalOr( $this->equalTo( '_sq_cart_ref' ), $this->equalTo( '_sq_cart_created_at' ) )
		)
		->will(
			$this->returnCallback(
				function ( $arg ) {
					if ( '_sq_cart_ref' === $arg ) {
						return 'cart_ref';
					} 
					if ( '_sq_cart_created_at' === $arg ) {
						return 'cart_created_at';
					}
				} 
			) 
		);

		$this->payment_service->method( 'get_payment_gateway_id' )->willReturn( 'sequra' );

		$this->cart_service->expects( $this->once() )
		->method( 'get_items' )
		->with( $order )
		->willReturn( array() );
		
		$this->cart_service->expects( $this->once() )
		->method( 'get_handling_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->once() )
		->method( 'get_discount_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->once() )
		->method( 'get_refund_items' )
		->with( $order )
		->willReturn( array() );

		$this->order_status_service->expects( $this->once() )
		->method( 'get_shop_status_completed' )
		->with( true )
		->willReturn( 'completed' );
		
		$this->configuration->expects( $this->once() )
		->method( 'get_store_id' )
		->willThrowException( new Exception( 'Test exception' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Test exception' );

		// Execute.
		$this->order_service->update_sequra_order_status( $order, 'processing', 'completed' );
	}

	public function testUpdateSequraOrderStatus_orderCompleted_sendOrderUpdate() {
		// Setup.
		$date  = new WC_DateTime();
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );
		$order->method( 'get_currency' )->willReturn( 'EUR' );
		$order->method( 'get_date_completed' )->willReturn( $date );
		$order->method( 'get_payment_method' )->willReturn( 'sequra' );
		$order->method( 'get_meta' )
		->with( 
			$this->logicalOr( $this->equalTo( '_sq_cart_ref' ), $this->equalTo( '_sq_cart_created_at' ) )
		)
		->will(
			$this->returnCallback(
				function ( $arg ) {
					if ( '_sq_cart_ref' === $arg ) {
						return 'cart_ref';
					} 
					if ( '_sq_cart_created_at' === $arg ) {
						return 'cart_created_at';
					}
				} 
			) 
		);

		$this->payment_service->method( 'get_payment_gateway_id' )->willReturn( 'sequra' );

		$this->cart_service->expects( $this->once() )
		->method( 'get_items' )
		->with( $order )
		->willReturn( array() );
		
		$this->cart_service->expects( $this->once() )
		->method( 'get_handling_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->once() )
		->method( 'get_discount_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->once() )
		->method( 'get_refund_items' )
		->with( $order )
		->willReturn( array() );

		$this->order_status_service->expects( $this->once() )
		->method( 'get_shop_status_completed' )
		->with( true )
		->willReturn( 'completed' );
		
		$store_id = '1';
		$this->configuration->expects( $this->once() )
		->method( 'get_store_id' )
		->willReturn( $store_id );

		$this->store_context_mock->expects( $this->once() )
		->method( 'do_with_store' )
		->with( $store_id, array( $this->core_order_service, 'updateOrder' ) );

		// Execute.
		$this->order_service->update_sequra_order_status( $order, 'processing', 'completed' );
	}

	/**
	 * @dataProvider dataProvider_UpdateSequraOrderStatus
	 */
	public function testUpdateSequraOrderStatus_notCompletedOrder_skipExecution( $old_status, $new_status ) {
		// Setup.
		$order = $this->createMock( WC_Order::class );
		$order->expects( $this->never() )->method( 'get_meta' );
		$order->expects( $this->never() )->method( 'get_currency' );

		$this->order_status_service->expects( $this->once() )
		->method( 'get_shop_status_completed' )
		->with( true )
		->willReturn( 'completed' );

		// Execute.
		$this->order_service->update_sequra_order_status( $order, $old_status, $new_status );
	}

	public function dataProvider_UpdateSequraOrderStatus() {
		return array(
			array( 'processing', 'cancelled' ),
			array( 'processing', 'pending' ),
			array( 'processing', 'on-hold' ),
		);
	}
}

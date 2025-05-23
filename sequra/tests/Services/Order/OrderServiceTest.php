<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Order;

// Load required classes.
require_once __DIR__ . '/../../Core/BusinessLogic/Domain/Multistore/StoreContextMock.php';
require_once __DIR__ . '/../../Core/BusinessLogic/Domain/Multistore/StoreContext.php';

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\OtherPaymentItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ProductItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderUpdateData;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use WP_UnitTestCase;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\Core\BusinessLogic\Domain\Order\Service\OrderService;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Interface_Logger_Service;
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
	private $logger;
	private $sequra_order_repository;

	public function set_up(): void {        
		$this->payment_service                   = $this->createMock( Interface_Payment_Service::class );
		$this->pricing_service                   = $this->createMock( Interface_Pricing_Service::class );
		$this->order_status_service              = $this->createMock( Order_Status_Settings_Service::class );
		$this->configuration                     = $this->createMock( Configuration::class );
		$this->core_order_service                = $this->createMock( OrderService::class );
		$this->cart_service                      = $this->createMock( Interface_Cart_Service::class );
		$this->store_context_mock                = $this->createMock( StoreContextMock::class );
		$this->logger                            = $this->createMock( Interface_Logger_Service::class );
		$this->sequra_order_repository           = $this->createMock( SeQuraOrderRepositoryInterface::class );
		
		$this->order_service = new Order_Service(
			$this->sequra_order_repository,
			$this->payment_service,
			$this->pricing_service,
			$this->order_status_service,
			$this->configuration,
			$this->cart_service,
			new StoreContext( $this->store_context_mock ),
			$this->logger
		);
	}

	public function getOrderService() {
		return $this->order_service;
	}

	private function setupOrderMock( $date = new WC_DateTime(), $needs_processing = true, $payment_method = 'sequra' ): MockObject {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );
		$order->method( 'get_currency' )->willReturn( 'EUR' );
		$order->method( 'get_date_completed' )->willReturn( $date );
		$order->method( 'get_payment_method' )->willReturn( $payment_method );
		$order->method( 'needs_processing' )->willReturn( $needs_processing );
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
		return $order;
	}

	private function setupCartServiceMock( $items = array(), $handling_items = array(), $discount_items = array(), $refund_items = array() ) {
		$this->cart_service->expects( $this->once() )
		->method( 'get_items' )
		->with( $this->anything() )
		->willReturn( $items );
		
		$this->cart_service->expects( $this->once() )
		->method( 'get_handling_items' )
		->with( $this->anything() )
		->willReturn( $handling_items );

		$this->cart_service->expects( $this->once() )
		->method( 'get_discount_items' )
		->with( $this->anything() )
		->willReturn( $discount_items );

		$this->cart_service->expects( $this->once() )
		->method( 'get_refund_items' )
		->with( $this->anything() )
		->willReturn( $refund_items );
	}

	private function setupPaymentServiceMock( $gateway_id = 'sequra' ) {
		$this->payment_service->method( 'get_payment_gateway_id' )->willReturn( $gateway_id );
	}

	private function setupOrderStatusServiceMock( $completed_status = array( 'completed' ) ) {
		$this->order_status_service->expects( $this->once() )
		->method( 'get_shop_status_completed' )
		->with( true )
		->willReturn( $completed_status );
	}

	public function testUpdateSequraOrderStatus_onException_throwException() {
		// Setup.
		$order = $this->setupOrderMock();
		$this->setupPaymentServiceMock();
		$this->setupCartServiceMock();
		$this->setupOrderStatusServiceMock();
		
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
		$order = $this->setupOrderMock( $date );
		$this->setupPaymentServiceMock();
		$items = array(
			new ProductItem( 'reference', 'Product', 100, 1, 100, false ),
		);
		$this->setupCartServiceMock( $items );
		$this->setupOrderStatusServiceMock();
		
		$store_id = '1';
		$this->configuration->expects( $this->once() )
		->method( 'get_store_id' )
		->willReturn( $store_id );

		$invoked_count      = $this->exactly( 2 );
		$core_order_service = $this->core_order_service;
		$this->store_context_mock->expects( $invoked_count )
		->method( 'do_with_store' )
		->willReturnCallback(
			function ( $storeId, $callback, $params ) use ( $core_order_service, $store_id, $invoked_count, $items, $date ) {
				$this->assertEquals( $store_id, $storeId );
				
				if ( 1 === $invoked_count->getInvocationCount() ) {
					return $core_order_service;
				}
		
				if ( 2 === $invoked_count->getInvocationCount() ) {
					/**
					* @var OrderUpdateData $order_data
					*/
					$order_data = $params[0];
					$this->assertTrue( $order_data instanceof OrderUpdateData );
					$this->assertFalse( $order_data->getOrderShopReference() !== '1' || $order_data->getDeliveryAddress() !== null || $order_data->getInvoiceAddress() !== null );
					$shipped_cart = $order_data->getShippedCart();
					$this->assertFalse(
						$shipped_cart->getCurrency() !== 'EUR'
						|| $shipped_cart->isGift() !== false
						|| $shipped_cart->getItems() !== $items
						|| $shipped_cart->getCartRef() !== 'cart_ref'
						|| $shipped_cart->getCreatedAt() !== 'cart_created_at'
						|| $shipped_cart->getUpdatedAt() !== $date->format( 'Y-m-d H:i:s' )
					);
					$unshipped_cart = $order_data->getUnshippedCart();
					$this->assertFalse( $unshipped_cart->getCurrency() !== 'EUR' || ! empty( $unshipped_cart->getItems() ) );
				}
			}
		);

		// Execute.
		$this->order_service->update_sequra_order_status( $order, 'processing', 'completed' );
	}

	public function testUpdateSequraOrderStatus_notPaidWithSeQura_skipExecution() {
		// Setup.
		$order = $this->setupOrderMock( new WC_DateTime(), true, 'other_payment_method' );
		$this->setupPaymentServiceMock();
		$this->store_context_mock->expects( $this->never() )->method( 'do_with_store' );

		// Execute.
		$this->order_service->update_sequra_order_status( $order, 'processing', 'completed' );
	}

	public function testUpdateSequraOrderStatus_notNeedsProcessing_skipExecution() {
		// Setup.
		$order = $this->setupOrderMock( new WC_DateTime(), false );
		$this->setupOrderStatusServiceMock();
		$this->setupPaymentServiceMock();
		
		$this->store_context_mock->expects( $this->never() )->method( 'do_with_store' );

		// Execute.
		$this->order_service->update_sequra_order_status( $order, 'processing', 'completed' );
	}

	/**
	 * @dataProvider dataProvider_UpdateSequraOrderStatus
	 */
	public function testUpdateSequraOrderStatus_notCompletedOrder_skipExecution( $old_status, $new_status ) {
		// Setup.
		$order = $this->setupOrderMock();
		
		$this->store_context_mock->expects( $this->never() )->method( 'do_with_store' );

		// Execute.
		$this->order_service->update_sequra_order_status( $order, $old_status, $new_status );
	}

	public function dataProvider_UpdateSequraOrderStatus() {
		return array(
			array( 'pending', 'on-hold' ),
			array( 'pending', 'failed' ),
			array( 'failed', 'cancelled' ),
			array( 'pending', 'processing' ),
			array( 'on-hold', 'processing' ),
			array( 'processing', 'cancelled' ),
		);
	}

	public function testHandleRefund_onException_throwException() {
		// Setup.
		$order = $this->setupOrderMock();
		$this->setupPaymentServiceMock();
		// $this->setupCartServiceMock();
		
		$this->configuration->expects( $this->once() )
		->method( 'get_store_id' )
		->willThrowException( new Exception( 'Test exception' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Test exception' );

		// Execute.
		$this->order_service->handle_refund( $order, 10 );
	}

	public function dataProvider_HandleRefund() {
		$items         = array(
			new ProductItem( 'reference', 'Product', 10000, 1, 10000, false ),
		);
		$refund_items  = array(
			new OtherPaymentItem( 'r_100', 'Refund', -1000 ),
		);
		$shipped_items = array_merge( $items, $refund_items );
		return array(
			array( 10, 100, $items, $refund_items, $shipped_items, 1 ),
			array( 100, 100, array(), array(), array(), 0 ),
		);
	}

	/**
	 * @dataProvider dataProvider_HandleRefund
	 */
	public function testHandleRefund( $amount, $order_total, $items, $refund_items, $shipped_items, $expected_get_items_calls ) {
		// Setup.
		$date  = new WC_DateTime();
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );
		$order->method( 'get_currency' )->willReturn( 'EUR' );
		$order->method( 'get_date_completed' )->willReturn( $date );
		$order->method( 'get_payment_method' )->with( $this->anything() )->willReturn( 'sequra' );
		$order->method( 'get_total' )->willReturn( $order_total );
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

		$this->cart_service->expects( $this->exactly( $expected_get_items_calls ) )
		->method( 'get_items' )
		->with( $order )
		->willReturn( $items );
		
		$this->cart_service->expects( $this->exactly( $expected_get_items_calls ) )
		->method( 'get_handling_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->exactly( $expected_get_items_calls ) )
		->method( 'get_discount_items' )
		->with( $order )
		->willReturn( array() );

		$this->cart_service->expects( $this->exactly( $expected_get_items_calls ) )
		->method( 'get_refund_items' )
		->with( $order )
		->willReturn( $refund_items );
		
		$store_id = '1';
		$this->configuration->expects( $this->once() )
		->method( 'get_store_id' )
		->willReturn( $store_id );

		$invoked_count      = $this->exactly( 2 );
		$core_order_service = $this->core_order_service;
		$this->store_context_mock->expects( $invoked_count )
		->method( 'do_with_store' )
		->willReturnCallback(
			function ( $storeId, $callback, $params ) use ( $core_order_service, $store_id, $invoked_count, $shipped_items, $date ) {
				$this->assertEquals( $store_id, $storeId );
				if ( 1 === $invoked_count->getInvocationCount() ) {
					return $core_order_service;
				}
		
				if ( 2 === $invoked_count->getInvocationCount() ) {
					/**
					 * @var OrderUpdateData $order_data
					 */
					$order_data = $params[0];
					$this->assertTrue( $order_data instanceof OrderUpdateData );
					$this->assertFalse( $order_data->getOrderShopReference() !== '1' || $order_data->getDeliveryAddress() !== null || $order_data->getInvoiceAddress() !== null );
					$shipped_cart = $order_data->getShippedCart();
					$this->assertFalse(
						$shipped_cart->getCurrency() !== 'EUR'
						|| $shipped_cart->isGift() !== false
						|| $shipped_cart->getItems() !== $shipped_items
						|| $shipped_cart->getCartRef() !== 'cart_ref'
						|| $shipped_cart->getCreatedAt() !== 'cart_created_at'
						|| $shipped_cart->getUpdatedAt() !== $date->format( 'Y-m-d H:i:s' )
					);
					$unshipped_cart = $order_data->getUnshippedCart();
					$this->assertFalse( $unshipped_cart->getCurrency() !== 'EUR' || ! empty( $unshipped_cart->getItems() ) );
					return;
				}
			}
		);

		// Execute.
		$this->order_service->handle_refund( $order, $amount );
	}
}

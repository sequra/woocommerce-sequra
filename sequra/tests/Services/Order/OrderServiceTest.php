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
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\OtherPaymentItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ProductItem;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderUpdateData;
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

		$items = array(
			new ProductItem( 'reference', 'Product', 100, 1, 100, false ),
		);
		$this->cart_service->expects( $this->once() )
		->method( 'get_items' )
		->with( $order )
		->willReturn( $items );
		
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
		->with(
			$store_id,
			array( $this->core_order_service, 'updateOrder' ),
			$this->callback(
				function ( $args ) use ( $items, $date ) {
					if ( ! isset( $args[0] ) || ! $args[0] instanceof OrderUpdateData ) {
						return false;
					}
					/**
					 * @var OrderUpdateData $order_data
					 */
					$order_data = $args[0];
					if ( $order_data->getOrderShopReference() !== '1' || $order_data->getDeliveryAddress() !== null || $order_data->getInvoiceAddress() !== null ) {
						return false;
					}
					$shipped_cart = $order_data->getShippedCart();
					if ( $shipped_cart->getCurrency() !== 'EUR'
						|| $shipped_cart->isGift() !== false
						|| $shipped_cart->getItems() !== $items
						|| $shipped_cart->getCartRef() !== 'cart_ref'
						|| $shipped_cart->getCreatedAt() !== 'cart_created_at'
						|| $shipped_cart->getUpdatedAt() !== $date->format( 'Y-m-d H:i:s' )
						) {
						return false;
					}
					$unshipped_cart = $order_data->getUnshippedCart();
					if ( $unshipped_cart->getCurrency() !== 'EUR' || ! empty( $unshipped_cart->getItems() ) ) {
						return false;
					}
					return true;
				}
			) 
		);

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

	public function testHandleRefund_onException_throwException() {
		// Setup.
		$date  = new WC_DateTime();
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );
		$order->method( 'get_currency' )->willReturn( 'EUR' );
		$order->method( 'get_date_completed' )->willReturn( $date );
		$order->method( 'get_payment_method' )->willReturn( 'sequra' );
		$order->method( 'get_total' )->willReturn( 100 );
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
		$order->method( 'get_payment_method' )->willReturn( 'sequra' );
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

		$this->store_context_mock->expects( $this->once() )
		->method( 'do_with_store' )
		->with(
			$store_id,
			array( $this->core_order_service, 'updateOrder' ),
			$this->callback(
				function ( $args ) use ( $shipped_items, $date ) {
					if ( ! isset( $args[0] ) || ! $args[0] instanceof OrderUpdateData ) {
						return false;
					}
					/**
					 * @var OrderUpdateData $order_data
					 */
					$order_data = $args[0];
					if ( $order_data->getOrderShopReference() !== '1' || $order_data->getDeliveryAddress() !== null || $order_data->getInvoiceAddress() !== null ) {
						return false;
					}
					$shipped_cart = $order_data->getShippedCart();
					if ( $shipped_cart->getCurrency() !== 'EUR'
						|| $shipped_cart->isGift() !== false
						|| $shipped_cart->getItems() !== $shipped_items
						|| $shipped_cart->getCartRef() !== 'cart_ref'
						|| $shipped_cart->getCreatedAt() !== 'cart_created_at'
						|| $shipped_cart->getUpdatedAt() !== $date->format( 'Y-m-d H:i:s' )
						) {
						return false;
					}
					$unshipped_cart = $order_data->getUnshippedCart();
					if ( $unshipped_cart->getCurrency() !== 'EUR' || ! empty( $unshipped_cart->getItems() ) ) {
						return false;
					}
					return true;
				}
			) 
		);

		// Execute.
		$this->order_service->handle_refund( $order, $amount );
	}
}

<?php
/**
 * Tests for the Payment gateway class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Payment;

require_once __DIR__ . '/../../Fixtures/Store.php';

use Exception;
use WP_UnitTestCase;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway;
use SeQura\WC\Tests\Fixtures\Store;
use WP_Error;

class SequraPaymentGatewayTest extends WP_UnitTestCase {

	private $payment_gateway;
	private $payment_service;
	private $payment_method_service;
	private $cart_service;
	private $order_service;
	private $templates_path;
	private $logger;
	private $store;


	public function set_up(): void {        
		$payment_service = $this->createMock( Interface_Payment_Service::class );
		$payment_service->method( 'get_payment_gateway_id' )->willReturn( 'sequra' );
		$payment_service->method( 'get_ipn_webhook' )->willReturn( 'woocommerce_sequra_ipn' );
		$payment_service->method( 'get_event_webhook' )->willReturn( 'woocommerce_sequra' );
		$payment_service->method( 'get_return_webhook' )->willReturn( 'woocommerce_sequra_return' );
		$this->payment_service = $payment_service;

		ServiceRegister::registerService(
			Interface_Payment_Service::class,
			function () use ( $payment_service ) {
				return $payment_service;
			} 
		);
		
		$cart_service       = $this->createMock( Interface_Cart_Service::class );
		$this->cart_service = $cart_service;
		ServiceRegister::registerService(
			Interface_Cart_Service::class,
			function () use ( $cart_service ) {
				return $cart_service;
			} 
		);

		$order_service       = $this->createMock( Interface_Order_Service::class );
		$this->order_service = $order_service;
		ServiceRegister::registerService(
			Interface_Order_Service::class,
			function () use ( $order_service ) {
				return $order_service;
			} 
		);

		$payment_method_service       = $this->createMock( Interface_Payment_Method_Service::class );
		$this->payment_method_service = $payment_method_service;
		ServiceRegister::registerService(
			Interface_Payment_Method_Service::class,
			function () use ( $payment_method_service ) {
				return $payment_method_service;
			} 
		);
		
		$templates_path       = 'path/to/templates';
		$this->templates_path = $templates_path;
		ServiceRegister::registerService(
			'plugin.templates_path',
			function () use ( $templates_path ) {
				return $templates_path;
			} 
		);
		
		$logger       = $this->createMock( Interface_Logger_Service::class );
		$this->logger = $logger;
		ServiceRegister::registerService(
			Interface_Logger_Service::class,
			function () use ( $logger ) {
				return $logger;
			} 
		);

		$this->store = new Store();
		
		$this->payment_gateway = new Sequra_Payment_Gateway();
	}

	public function tear_down(): void {
		$this->store->tear_down();
	}

	public function testConstructor() {
		$this->assertGreaterThanOrEqual( 1, did_action( 'woocommerce_sequra_before_load' ) );
		$this->assertEquals( 'sequra', $this->payment_gateway->id );
		$this->assertTrue( $this->payment_gateway->has_fields );
		$this->assertEquals( 'seQura', $this->payment_gateway->method_title );
		$this->assertEquals( 'Flexible payment with seQura', $this->payment_gateway->title );
		$this->assertEquals( 'seQura payment method\'s configuration', $this->payment_gateway->method_description );
		$this->assertEquals( 'Please, select the payment method you want to use', $this->payment_gateway->description );
		$this->assertEquals( array( 'products', 'refunds' ), $this->payment_gateway->supports );
		
		$this->assertEquals( 'no', $this->payment_gateway->enabled );
		
		$this->assertEquals( 10, has_action( 'woocommerce_update_options_payment_gateways_sequra', array( $this->payment_gateway, 'process_admin_options' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_receipt_sequra', array( $this->payment_gateway, 'redirect_to_payment' ) ) );
		
		$this->assertEquals( 10, has_action( 'woocommerce_api_woocommerce_sequra_ipn', array( $this->payment_gateway, 'process_ipn' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_api_woocommerce_sequra', array( $this->payment_gateway, 'process_event' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_api_woocommerce_sequra_return', array( $this->payment_gateway, 'handle_return' ) ) );
		
		$this->assertGreaterThanOrEqual( 1, did_action( 'woocommerce_sequra_loaded' ) );
	}

	/**
	 * @dataProvider dataProvider_ProcessRefund_amountIsEmpty_returnError
	 */
	public function testProcessRefund_amountIsEmpty_returnError( $amount ) {
		$this->logger->expects( $this->once() )
			->method( 'log_debug' )
			->with( 'Invalid refund amount: ' . $amount, 'process_refund', 'SeQura\WC\Services\Payment\Sequra_Payment_Gateway' );
		
		/**
		 * @var WP_Error $result
		 */
		$result = $this->payment_gateway->process_refund( 1, $amount );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'Refund amount cannot be empty', $result->get_error_message( 'empty_refund_amount' ) );
	}

	public function dataProvider_ProcessRefund_amountIsEmpty_returnError() {
		return array(
			array( 0 ),
			array( -1 ),
		);
	}

	public function testProcessRefund_orderNotFound_returnError() {
		$this->logger->expects( $this->once() )
			->method( 'log_error' )
			->with(
				'Order not found', 
				'process_refund', 
				'SeQura\WC\Services\Payment\Sequra_Payment_Gateway',
				$this->callback(
					function ( $context ) {
						return 'order_id' === $context[0]->getName() && 0 === $context[0]->getValue();
					} 
				) 
			);
		
		/**
		 * @var WP_Error $result
		 */
		$result = $this->payment_gateway->process_refund( 0, 100 );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'Order not found', $result->get_error_message( 'order_not_found' ) );
	}

	public function testProcessRefund_onException_returnError() {
		$this->store->set_up();
		$order  = $this->store->get_orders()[0];
		$amount = $order->get_total();
		$e      = new Exception( 'Test exception' );
		
		$this->logger->expects( $this->once() )
			->method( 'log_throwable' )
			->with( $e, 'process_refund', 'SeQura\WC\Services\Payment\Sequra_Payment_Gateway' );
		
		$this->order_service->expects( $this->once() )
			->method( 'handle_refund' )
			->with( $order, $amount )
			->willThrowException( $e );
		
		/**
		 * @var WP_Error $result
		 */
		$result = $this->payment_gateway->process_refund( $order->get_id(), $amount );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'An error occurred while refunding the order in seQura.', $result->get_error_message( 'refund_failed' ) );
	}

	/**
	 * @dataProvider dataProvider_ProcessRefund
	 */
	public function testProcessRefund( $amount ) {
		$this->store->set_up();
		$order = $this->store->get_orders()[0];
		
		$this->order_service->expects( $this->once() )
			->method( 'handle_refund' )
			->with( $order, $amount );
		
		$result = $this->payment_gateway->process_refund( $order->get_id(), $amount );
		$this->assertTrue( $result );
	}

	public function dataProvider_ProcessRefund() {
		return array(
			array( 10 ),
			array( 100 ),
		);
	}
}

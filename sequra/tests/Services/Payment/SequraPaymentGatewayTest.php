<?php
/**
 * Tests for the Payment gateway class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Services\Payment;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WP_UnitTestCase;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Dto\Cart_Info;
use SeQura\WC\Dto\Payment_Method_Data;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Constants\Interface_Constants;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway;
use SeQura\WC\Tests\Fixtures\Store;
use WP_Error;

class SequraPaymentGatewayTest extends WP_UnitTestCase {

	private $payment_gateway;
	private $order_service;
	private $logger;
	private $store;
	private $settings_url;
	/** @var Interface_Payment_Method_Service&MockObject */
	private $payment_method_service;
	/** @var Interface_Cart_Service&MockObject */
	private $cart_service;

	public function set_up(): void {        
		$constants = $this->createMock( Interface_Constants::class );
		$constants->method( 'get_payment_gateway_id' )->willReturn( 'sequra' );
		$constants->method( 'get_ipn_webhook' )->willReturn( 'woocommerce_sequra_ipn' );
		$constants->method( 'get_event_webhook' )->willReturn( 'woocommerce_sequra' );
		$constants->method( 'get_return_webhook' )->willReturn( 'woocommerce_sequra_return' );
		$constants->method( 'get_plugin_templates_path' )->willReturn( 'path/to/templates' );
		ServiceRegister::registerService(
			Interface_Constants::class,
			function () use ( $constants ) {
				return $constants;
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
		
		$logger       = $this->createMock( Interface_Logger_Service::class );
		$this->logger = $logger;
		ServiceRegister::registerService(
			Interface_Logger_Service::class,
			function () use ( $logger ) {
				return $logger;
			} 
		);

		$order_status_settings_service = $this->createMock( Order_Status_Settings_Service::class );
		ServiceRegister::registerService(
			Order_Status_Settings_Service::class,
			function () use ( $order_status_settings_service ) {
				return $order_status_settings_service;
			} 
		);

		$this->store = new Store();

		$settings_url       = 'https://example.com/wp-admin/admin.php?page=sequra';
		$this->settings_url = $settings_url;
		add_filter(
			'sequra_settings_page_url',
			function ( $url ) use ( $settings_url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				return $settings_url;
			},
			99999
		);
		
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
		$this->assertEquals( 'seQura payment method&#039;s configuration. <a href="' . $this->settings_url . '">View more configuration options.</a>', $this->payment_gateway->method_description );
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
		$this->store->set_up();
		$order = $this->store->get_orders()[0];
		$this->logger->expects( $this->once() )
			->method( 'log_debug' )
			->with( 'Invalid refund amount: ' . $amount, 'process_refund', 'SeQura\WC\Services\Payment\Sequra_Payment_Gateway' );
		
		/**
		 * @var WP_Error $result
		 */
		$result = $this->payment_gateway->process_refund( $order->get_id(), $amount );
		$this->assertInstanceOf( WP_Error::class, $result );
		$note = 'Refund amount must be greater than 0';
		$this->assertEquals( $note, $result->get_error_message( 'empty_refund_amount' ) );
		$this->assertContains( $note, $this->store->get_order_notes( $order->get_id() ) );
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
		$order             = $this->store->get_orders()[0];
		$amount            = $order->get_total();
		$exception_message = 'Test exception';
		$note              = 'Refund failed in seQura: ' . $exception_message;
		$e                 = new Exception( $exception_message );
		
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
		$this->assertEquals( $note, $result->get_error_message( 'refund_failed' ) );
		$this->assertContains( $note, $this->store->get_order_notes( $order->get_id() ) );
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

	public function testGetIcon_notDelegated_returnsParentDefault(): void {
		$this->payment_method_service
			->method( 'is_delegated_payment_selection' )
			->willReturn( false );

		$this->assertSame( '', $this->payment_gateway->get_icon() );
	}

	public function testGetIcon_delegated_returnsImgWithCdnUrl(): void {
		$this->payment_method_service
			->method( 'is_delegated_payment_selection' )
			->willReturn( true );

		$icon = $this->payment_gateway->get_icon();
		$this->assertStringContainsString( 'live.sequracdn.com/assets/images/internal/brand/SeQura_logo.svg', $icon );
		$this->assertStringContainsString( '<img', $icon );
	}

	public function testPaymentFields_delegated_rendersHiddenInput(): void {
		$this->payment_method_service
			->method( 'is_delegated_payment_selection' )
			->willReturn( true );

		ob_start();
		$this->payment_gateway->payment_fields();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="hidden"', $output );
		$this->assertStringContainsString( 'name="sequra_payment_method_data"', $output );

		// Decode the value and verify it encodes product=tbs.
		preg_match( '/value="([^"]+)"/', $output, $matches );
		$this->assertNotEmpty( $matches[1] );
		$dto = Payment_Method_Data::decode( $matches[1] );
		$this->assertInstanceOf( Payment_Method_Data::class, $dto );
		$this->assertSame( 'tbs', $dto->product );
	}

	public function testValidateFields_delegated_returnsTrueWithoutValidation(): void {
		$this->payment_method_service
			->method( 'is_delegated_payment_selection' )
			->willReturn( true );

		$this->payment_method_service
			->expects( $this->never() )
			->method( 'is_payment_method_data_valid' );

		$this->assertTrue( $this->payment_gateway->validate_fields() );
	}

	public function testProcessPayment_delegated_usesTbsDtoAndSkipsValidation(): void {
		$this->store->set_up();
		$order = $this->store->get_orders()[0];

		$this->payment_method_service
			->method( 'is_delegated_payment_selection' )
			->willReturn( true );

		$this->payment_method_service
			->expects( $this->never() )
			->method( 'is_payment_method_data_valid' );

		$cart_info = new Cart_Info( 'test-ref' );
		$this->order_service
			->method( 'get_cart_info' )
			->willReturn( $cart_info );

		$this->order_service
			->expects( $this->once() )
			->method( 'set_order_metadata' )
			->with(
				$order,
				$this->callback(
					function ( $dto ) {
						return $dto instanceof Payment_Method_Data && 'tbs' === $dto->product;
					}
				),
				$cart_info
			)
			->willReturn( true );

		$result = $this->payment_gateway->process_payment( $order->get_id() );
		$this->assertSame( 'success', $result['result'] );
	}
}

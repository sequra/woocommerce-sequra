<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Hooks\Process;

require_once __DIR__ . '/../../../Fixtures/Store.php';

use Exception;
use SeQura\Core\Infrastructure\Logger\LogContextData;
use SeQura\WC\Controllers\Hooks\Order\Order_Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Tests\Fixtures\Store;
use WP_UnitTestCase;

class OrderControllerTest extends WP_UnitTestCase {

	private $controller;
	private $order_service;
	private $logger;
	private $store;
	
	public function set_up() {
		$this->logger        = $this->createMock( Interface_Logger_Service::class );
		$this->order_service = $this->createMock( Interface_Order_Service::class );
		$this->controller    = new Order_Controller( $this->logger, 'path/to/templates', $this->order_service );
		$this->store         = new Store();
		$this->store->set_up();
	}

	public function tear_down() {
		// clean up database.
		$this->store->tear_down();
	}

	public function testConstructor() {
		$this->assertEquals( 10, has_action( 'admin_notices', array( $this->controller, 'display_notices' ) ) );
	}

	public function testHandleOrderStatusChanged_onException_showErrorMessage() {
		// Setup.
		$order      = $this->store->get_orders()[0];
		$old_status = 'old_status';
		$new_status = 'new_status';

		$this->logger->expects( $this->once() )
			->method( 'log_debug' )
			->with( 'Hook executed', 'handle_order_status_changed', 'SeQura\WC\Controllers\Hooks\Order\Order_Controller' );
		
		$e       = new Exception( 'Test exception' );
		$context = array(
			new LogContextData( 'order_id', $order->get_id() ),
			new LogContextData( 'old_status', $old_status ),
			new LogContextData( 'new_status', $new_status ),
		);
		$this->logger->expects( $this->once() )
			->method( 'log_throwable' )
			->with(
				$e, 
				'handle_order_status_changed',
				'SeQura\WC\Controllers\Hooks\Order\Order_Controller',
				$context
			);
		
		$this->order_service->expects( $this->once() )
			->method( 'update_sequra_order_status' )
			->with( $order, $old_status, $new_status )
			->will( $this->throwException( $e ) );

		// Execute.
		$this->controller->handle_order_status_changed( $order->get_id(), $old_status, $new_status, $order );

		// Assert.
		$transient = get_transient( 'sequra_notices_order_' . $order->get_id() );
		$this->assertEquals(
			$transient,
			array(
				array(
					'notice'      => __( 'An error occurred while updating the order data in seQura.', 'sequra' ),
					'type'        => 'error',
					'dismissible' => true,
				),
			)
		);
	}

	/**
	 * @dataProvider dataProvider_HandleOrderStatusChanged
	 */
	public function testHandleOrderStatusChanged( $old_status, $new_status ) {
		// Setup.
		$order = $this->store->get_orders()[0];

		$this->logger->expects( $this->once() )
			->method( 'log_debug' )
			->with( 'Hook executed', 'handle_order_status_changed', 'SeQura\WC\Controllers\Hooks\Order\Order_Controller' );
	
		$this->order_service->expects( $this->once() )
			->method( 'update_sequra_order_status' )
			->with( $order, $old_status, $new_status );

		// Execute.
		$this->controller->handle_order_status_changed( $order->get_id(), $old_status, $new_status, $order );
	}

	public function dataProvider_HandleOrderStatusChanged() {
		return array(
			array( 'processing', 'completed' ),
			array( 'processing', 'cancelled' ),
			array( 'pending', 'processing' ),
		);
	}
}

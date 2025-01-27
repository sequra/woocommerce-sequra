<?php
/**
 * Tests for the Async_Process_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Hooks\Process;

use Exception;
use SeQura\WC\Controllers\Hooks\Process\Async_Process_Controller;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Report\Interface_Report_Service;
use WP_UnitTestCase;

class AsyncProcessControllerTest extends WP_UnitTestCase {

	private $controller;
	private $report_service;
	private $logger;
	
	public function set_up() {
		$this->logger         = $this->createMock( Interface_Logger_Service::class );
		$this->report_service = $this->createMock( Interface_Report_Service::class );
		$this->controller     = new Async_Process_Controller( $this->logger, 'path/to/templates', $this->report_service );
	}

	public function testSendDeliveryReport_onException_logError() {
		$this->logger->expects( $this->once() )
			->method( 'log_debug' )
			->with( 'Hook executed', 'send_delivery_report', 'SeQura\WC\Controllers\Hooks\Process\Async_Process_Controller' );

		$e = new Exception( 'Test exception' );

		$this->report_service->expects( $this->once() )
			->method( 'send_delivery_report_for_current_store' )
			->will( $this->throwException( $e ) );
		
		$this->logger->expects( $this->once() )
			->method( 'log_throwable' )
			->with( $e, 'send_delivery_report', 'SeQura\WC\Controllers\Hooks\Process\Async_Process_Controller' );

		$this->controller->send_delivery_report();
	}

	public function testSendDeliveryReport_happyPath() {
		$this->logger->expects( $this->once() )
			->method( 'log_debug' )
			->with( 'Hook executed', 'send_delivery_report', 'SeQura\WC\Controllers\Hooks\Process\Async_Process_Controller' );

		$this->report_service->expects( $this->once() )
			->method( 'send_delivery_report_for_current_store' );

		$this->controller->send_delivery_report();
	}
}

<?php
/**
 * Tests for the Log_REST_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Rest;

use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Models\AdvancedSettings;
use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Services\AdvancedSettingsService;
use SeQura\Core\Infrastructure\Logger\Logger;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Controllers\Rest\Log_REST_Controller;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_Error;
use WP_REST_Request;
use WP_UnitTestCase;

class LogRestControllerTest extends WP_UnitTestCase {

	/**
	 * @var Interface_Logger_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * @var RegexProvider&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $regex;

	/**
	 * @var AdvancedSettingsService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $advanced_settings_service;

	/**
	 * @var Log_REST_Controller
	 */
	private $controller;

	public function set_up(): void {
		parent::set_up();

		$this->logger = $this->createMock( Interface_Logger_Service::class );
		$this->regex  = $this->getMockBuilder( RegexProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->advanced_settings_service = $this->getMockBuilder( AdvancedSettingsService::class )
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new Log_REST_Controller(
			'sequra/v1',
			$this->logger,
			$this->regex,
			$this->advanced_settings_service
		);
	}

	public function testGetConfiguration_withExistingSettings_returnsAdvancedSettingsResponse(): void {
		$settings = new AdvancedSettings( true, Logger::DEBUG );
		$this->advanced_settings_service->method( 'getAdvancedSettings' )->willReturn( $settings );

		$response = $this->controller->get_configuration();
		$data     = \rest_ensure_response( $response )->get_data();

		$this->assertIsArray( $data );
		$this->assertTrue( $data['isEnabled'] );
		$this->assertSame( Logger::DEBUG, $data['level'] );
	}

	public function testGetConfiguration_withNullSettings_returnsDefaultValues(): void {
		$this->advanced_settings_service->method( 'getAdvancedSettings' )->willReturn( null );

		$response = $this->controller->get_configuration();
		$data     = \rest_ensure_response( $response )->get_data();

		$this->assertIsArray( $data );
		$this->assertFalse( $data['isEnabled'] );
		$this->assertSame( Logger::DEBUG, $data['level'] );
	}

	public function testSaveConfiguration_validRequest_savesAndReturnsSettings(): void {
		$request = new WP_REST_Request( 'POST', '/sequra/v1/log/settings/1' );
		$request->set_param( 'isEnabled', true );
		$request->set_param( 'level', Logger::INFO );

		$captured_settings = null;
		$this->advanced_settings_service
			->expects( $this->once() )
			->method( 'setAdvancedSettings' )
			->willReturnCallback(
				function ( AdvancedSettings $settings ) use ( &$captured_settings ) {
					$captured_settings = $settings;
				}
			);

		$response = $this->controller->save_configuration( $request );

		// Verify setAdvancedSettings was called with correct values.
		$this->assertNotNull( $captured_settings );
		$this->assertTrue( $captured_settings->isEnabled() );
		$this->assertSame( Logger::INFO, $captured_settings->getLevel() );

		// Verify the response is not a WP_Error (i.e. operation succeeded).
		$this->assertNotInstanceOf( WP_Error::class, $response );
	}

	public function testSaveConfiguration_throwsException_returnsError(): void {
		$request = new WP_REST_Request( 'POST', '/sequra/v1/log/settings/1' );
		$request->set_param( 'isEnabled', false );
		$request->set_param( 'level', 0 );

		$this->advanced_settings_service
			->method( 'setAdvancedSettings' )
			->willThrowException( new \RuntimeException( 'Save failed' ) );

		$response = $this->controller->save_configuration( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'error', $response->get_error_code() );
	}
}

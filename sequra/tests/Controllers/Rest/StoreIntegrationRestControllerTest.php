<?php
/**
 * Tests for the Store_Integration_REST_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Rest;

use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Controllers\Rest\Store_Integration_REST_Controller;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration\Interface_Store_Integration_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_UnitTestCase;

class StoreIntegrationRestControllerTest extends WP_UnitTestCase {

	/**
	 * @var Interface_Logger_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * @var RegexProvider&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $regex;

	/**
	 * @var Interface_Store_Integration_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_integration_service;

	/**
	 * @var StoreContext&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_context;

	/**
	 * @var Store_Integration_REST_Controller
	 */
	private $controller;

	public function set_up(): void {
		parent::set_up();

		$this->logger = $this->createMock( Interface_Logger_Service::class );
		$this->regex  = $this->getMockBuilder( RegexProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store_integration_service = $this->createMock( Interface_Store_Integration_Service::class );
		$this->store_context             = $this->getMockBuilder( StoreContext::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store_integration_service->method( 'get_rest_base' )->willReturn( '/webhook' );

		$this->controller = new Store_Integration_REST_Controller(
			'sequra/v1',
			$this->logger,
			$this->regex,
			$this->store_integration_service,
			$this->store_context
		);
	}

	public function testCheckPermissions_alwaysReturnsTrue(): void {
		$this->assertTrue( $this->controller->check_permissions() );
	}

	public function testConstructor_setsRestBaseFromService(): void {
		$reflection = new \ReflectionProperty( Store_Integration_REST_Controller::class, 'rest_base' );
		$reflection->setAccessible( true );

		$rest_base = $reflection->getValue( $this->controller );

		$this->assertSame( '/webhook', $rest_base );
	}

	public function testConstructor_setsNamespace(): void {
		$reflection = new \ReflectionProperty( Store_Integration_REST_Controller::class, 'namespace' );
		$reflection->setAccessible( true );

		$namespace = $reflection->getValue( $this->controller );

		$this->assertSame( 'sequra/v1', $namespace );
	}
}

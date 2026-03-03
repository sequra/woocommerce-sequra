<?php
/**
 * Tests for the Log_Service class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Core\BusinessLogic\Domain\Integration\Log;

use SeQura\Core\BusinessLogic\Domain\Log\Model\Log;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Log\Log_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_UnitTestCase;

class LogServiceTest extends WP_UnitTestCase {

	/**
	 * @var Interface_Logger_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * @var StoreContext&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_context;

	/**
	 * @var Log_Service
	 */
	private $log_service;

	public function set_up(): void {
		parent::set_up();

		$this->logger        = $this->createMock( Interface_Logger_Service::class );
		$this->store_context = $this->getMockBuilder( StoreContext::class )
			->disableOriginalConstructor()
			->getMock();

		$this->log_service = new Log_Service( $this->logger, $this->store_context );
	}

	public function testGetLog_returnsLogModel(): void {
		$content = array( 'line 1', 'line 2' );

		$this->store_context->method( 'getStoreId' )->willReturn( '1' );
		$this->logger->method( 'get_content' )->willReturn( $content );

		$log = $this->log_service->getLog();

		$this->assertInstanceOf( Log::class, $log );
		$this->assertSame( $content, $log->getContent() );
	}

	public function testGetLog_usesCorrectStoreId(): void {
		$store_id = 'store-42';

		$this->store_context->method( 'getStoreId' )->willReturn( $store_id );

		$this->logger->expects( $this->once() )
			->method( 'get_content' )
			->with( $store_id )
			->willReturn( array() );

		$this->log_service->getLog();
	}

	public function testRemoveLog_callsClearOnLogger(): void {
		$store_id = 'store-99';

		$this->store_context->method( 'getStoreId' )->willReturn( $store_id );

		$this->logger->expects( $this->once() )
			->method( 'clear' )
			->with( $store_id );

		$this->log_service->removeLog();
	}
}

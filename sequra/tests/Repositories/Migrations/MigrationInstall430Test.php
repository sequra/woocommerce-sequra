<?php
/**
 * Tests for the Migration_Install_430 class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use Exception;
use RuntimeException;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\ConnectionData;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\StoreIntegration;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\RepositoryContracts\StoreIntegrationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Services\StoreIntegrationService;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Cache_Repository;
use SeQura\WC\Repositories\Migrations\Migration_Install_430;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_UnitTestCase;

class MigrationInstall430Test extends WP_UnitTestCase {

	/**
	 * Migration instance.
	 *
	 * @var Migration_Install_430
	 */
	private $migration;

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Connection service mock.
	 *
	 * @var ConnectionService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $connection_service;

	/**
	 * Store integration service mock.
	 *
	 * @var StoreIntegrationService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_integration_service;

	/**
	 * Store integration repository mock.
	 *
	 * @var StoreIntegrationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_integration_repository;

	/**
	 * Store service mock.
	 *
	 * @var StoreService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_service;

	/**
	 * Logger service mock.
	 *
	 * @var Interface_Logger_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->connection_service           = $this->createMock( ConnectionService::class );
		$this->store_integration_service    = $this->createMock( StoreIntegrationService::class );
		$this->store_integration_repository = $this->createMock( StoreIntegrationRepositoryInterface::class );
		$this->store_service                = $this->createMock( StoreService::class );
		$this->logger                       = $this->createMock( Interface_Logger_Service::class );

		$connection_service           = $this->connection_service;
		$store_integration_service    = $this->store_integration_service;
		$store_integration_repository = $this->store_integration_repository;

		ServiceRegister::registerService(
			ConnectionService::class,
			static function () use ( $connection_service ) {
				return $connection_service;
			}
		);
		ServiceRegister::registerService(
			StoreIntegrationService::class,
			static function () use ( $store_integration_service ) {
				return $store_integration_service;
			}
		);
		ServiceRegister::registerService(
			StoreIntegrationRepositoryInterface::class,
			static function () use ( $store_integration_repository ) {
				return $store_integration_repository;
			}
		);

		$this->migration = new Migration_Install_430(
			$this->wpdb,
			new Cache_Repository(),
			$this->store_service,
			$this->logger
		);
	}

	public function testGetVersion_returns430(): void {
		$this->assertSame( '4.3.0', $this->migration->get_version() );
	}

	public function testRun_withNoConnectedStores_doesNotCallAnyService(): void {
		$this->store_service->method( 'getConnectedStores' )->willReturn( array() );
		$this->store_integration_service->expects( $this->never() )->method( 'createStoreIntegration' );
		$this->connection_service->expects( $this->never() )->method( 'getAllConnectionData' );

		$this->migration->run();
	}

	public function testRun_withExistingStoreIntegration_skipsCreateStoreIntegration(): void {
		$connection_data = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );
		$this->store_integration_repository->method( 'getStoreIntegration' )
			->willReturn( $this->createMock( StoreIntegration::class ) );
		$this->store_integration_service->expects( $this->never() )->method( 'createStoreIntegration' );

		$this->migration->run();
	}

	public function testRun_withNoStoreIntegration_callsCreateStoreIntegrationWithConnectionData(): void {
		$connection_data = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );
		$this->store_integration_repository->method( 'getStoreIntegration' )->willReturn( null );
		$this->store_integration_service->expects( $this->once() )
			->method( 'createStoreIntegration' )
			->with( $connection_data );

		$this->migration->run();
	}

	public function testRun_withOneConnectionFailing_continuesLoopAndThrowsException(): void {
		$connection_data_1 = $this->createMock( ConnectionData::class );
		$connection_data_2 = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )
			->willReturn( array( $connection_data_1, $connection_data_2 ) );
		$this->store_integration_repository->method( 'getStoreIntegration' )->willReturn( null );
		$this->store_integration_service->expects( $this->exactly( 2 ) )
			->method( 'createStoreIntegration' )
			->willReturnCallback(
				function ( $data ) use ( $connection_data_1 ) {
					if ( $data === $connection_data_1 ) {
						throw new RuntimeException( 'API error' );
					}
				}
			);

		$this->expectException( Exception::class );
		$this->migration->run();
	}

	public function testRun_withAllConnectionsSucceeding_throwsNoException(): void {
		$connection_data = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1', 'store2' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );
		$this->store_integration_repository->method( 'getStoreIntegration' )->willReturn( null );
		$this->store_integration_service->expects( $this->exactly( 2 ) )
			->method( 'createStoreIntegration' );

		$this->migration->run();
		$this->assertTrue( true );
	}
}

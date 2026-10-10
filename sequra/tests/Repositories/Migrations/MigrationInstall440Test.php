<?php
/**
 * Tests for the Migration_Install_440 class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use RuntimeException;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\ConnectionData;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\Configuration\ConfigurationManager;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Cache_Repository;
use SeQura\WC\Repositories\Migrations\Migration_Install_440;
use SeQura\WC\Services\Migration\Migration_Manager;
use WP_UnitTestCase;

class MigrationInstall440Test extends WP_UnitTestCase {

	/**
	 * Migration instance.
	 *
	 * @var Migration_Install_440
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
	 * Store service mock.
	 *
	 * @var StoreService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_service;

	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->connection_service = $this->createMock( ConnectionService::class );
		$this->store_service      = $this->createMock( StoreService::class );

		$connection_service = $this->connection_service;
		ServiceRegister::registerService(
			ConnectionService::class,
			static function () use ( $connection_service ) {
				return $connection_service;
			}
		);

		$this->migration = new Migration_Install_440(
			$this->wpdb,
			new Cache_Repository(),
			$this->store_service
		);
	}

	public function testGetVersion_returns440(): void {
		$this->assertSame( '4.4.0', $this->migration->get_version() );
	}

	public function dataProvider_MigrationManager() {
		return array(
			array( '4.3.3', 1 ),
			array( '4.3.4', 1 ),
			array( '4.4.0', 0 ),
		);
	}

	/**
	 * @dataProvider dataProvider_MigrationManager
	 */
	public function testMigrationManager_runsMigrationOnlyForStoresBelow440( string $db_version, int $expected_runs ): void {
		$configuration_manager = $this->createMock( ConfigurationManager::class );
		$configuration_manager->method( 'getConfigValue' )->with( 'version', '' )->willReturn( $db_version );
		$this->store_service->expects( $this->exactly( $expected_runs ) )
			->method( 'getConnectedStores' )
			->willReturn( array() );

		$manager = new Migration_Manager( 'sequra/sequra.php', $configuration_manager, '4.4.0', array( $this->migration ) );
		$manager->run_install_migrations();
	}

	public function testRun_withNoConnectedStores_doesNotValidateAnyConnection(): void {
		$this->store_service->method( 'getConnectedStores' )->willReturn( array() );
		$this->connection_service->expects( $this->never() )->method( 'getAllConnectionData' );
		$this->connection_service->expects( $this->never() )->method( 'isConnectionDataValid' );

		$this->migration->run();
	}

	public function testRun_revalidatesEachConnectionToBackfillAffiliate(): void {
		$connection_data = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );
		// Re-validating the connection is what makes the core re-fetch the merchant config
		// (now carrying the affiliate block) and persist it via the connect-time consumer.
		$this->connection_service->expects( $this->once() )
			->method( 'isConnectionDataValid' )
			->with( $connection_data );

		$this->migration->run();
	}

	public function testRun_withOneConnectionFailing_continuesWithoutThrowing(): void {
		// Stale credentials or a transient seQura failure on one connection must not abort the
		// backfill for the remaining stores/connections.
		$connection_data_1 = $this->createMock( ConnectionData::class );
		$connection_data_2 = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )
			->willReturn( array( $connection_data_1, $connection_data_2 ) );
		$this->connection_service->expects( $this->exactly( 2 ) )
			->method( 'isConnectionDataValid' )
			->willReturnCallback(
				function ( $data ) use ( $connection_data_1 ) {
					if ( $data === $connection_data_1 ) {
						throw new RuntimeException( 'stale credentials' );
					}
					return true;
				}
			);

		$this->migration->run();
		$this->assertTrue( true );
	}
}

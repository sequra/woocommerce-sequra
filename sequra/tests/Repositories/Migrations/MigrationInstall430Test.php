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
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\DeleteStoreIntegrationRequest;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\ProxyContracts\StoreIntegrationsProxyInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Services\StoreIntegrationService;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Cache_Repository;
use SeQura\WC\Repositories\Migrations\Migration_Install_430;
use WP_UnitTestCase;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

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
	 * Entity table name.
	 *
	 * @var string
	 */
	private $entity_table;

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
	 * Store integrations proxy mock.
	 *
	 * @var StoreIntegrationsProxyInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_integrations_proxy;

	/**
	 * Store service mock.
	 *
	 * @var StoreService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_service;

	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$this->wpdb         = $wpdb;
		$this->entity_table = $wpdb->prefix . 'sequra_entity';

		$this->connection_service        = $this->createMock( ConnectionService::class );
		$this->store_integration_service = $this->createMock( StoreIntegrationService::class );
		$this->store_integrations_proxy  = $this->createMock( StoreIntegrationsProxyInterface::class );
		$this->store_service             = $this->createMock( StoreService::class );

		$connection_service        = $this->connection_service;
		$store_integration_service = $this->store_integration_service;
		$store_integrations_proxy  = $this->store_integrations_proxy;

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
			StoreIntegrationsProxyInterface::class,
			static function () use ( $store_integrations_proxy ) {
				return $store_integrations_proxy;
			}
		);

		$this->migration = new Migration_Install_430(
			$this->wpdb,
			new Cache_Repository(),
			$this->store_service
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

	public function testRun_callsCreateStoreIntegrationForEachConnection(): void {
		$connection_data = $this->createMock( ConnectionData::class );

		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );
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
		$this->store_integration_service->expects( $this->exactly( 2 ) )
			->method( 'createStoreIntegration' );

		$this->migration->run();
		$this->assertTrue( true );
	}

	public function testRun_withOldStoreIntegrationInDb_callsDeleteStoreIntegrationWithOldUrl(): void {
		$old_webhook_url = 'https://example.com/webhook?storeId=store1&signature=old_random_sig';
		$this->insert_store_integration_entity( 'store1', $old_webhook_url );

		$connection_data = $this->createMock( ConnectionData::class );
		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );

		$this->store_integrations_proxy->expects( $this->once() )
			->method( 'deleteStoreIntegration' )
			->with(
				$this->callback(
					function ( DeleteStoreIntegrationRequest $request ) use ( $old_webhook_url ) {
						return $request->getWebhookUrl() === $old_webhook_url;
					}
				)
			);

		$this->migration->run();
	}

	public function testRun_withNoOldStoreIntegrationInDb_doesNotCallDeleteStoreIntegration(): void {
		$connection_data = $this->createMock( ConnectionData::class );
		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );

		$this->store_integrations_proxy->expects( $this->never() )->method( 'deleteStoreIntegration' );

		$this->migration->run();
	}

	public function testRun_withSuccessfulRegistration_deletesOldStoreIntegrationEntityFromDb(): void {
		$this->insert_store_integration_entity( 'store1', 'https://example.com/webhook?storeId=store1&signature=old' );

		$connection_data = $this->createMock( ConnectionData::class );
		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );

		$this->migration->run();

		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->entity_table} WHERE type = %s AND index_1 = %s",
				'StoreIntegration',
				'store1'
			)
		);
		$this->assertSame( '0', $count );
	}

	public function testRun_withFailingRegistration_doesNotDeleteOldDbRow(): void {
		$this->insert_store_integration_entity( 'store1', 'https://example.com/webhook?storeId=store1&signature=old' );

		$connection_data = $this->createMock( ConnectionData::class );
		$this->store_service->method( 'getConnectedStores' )->willReturn( array( 'store1' ) );
		$this->connection_service->method( 'getAllConnectionData' )->willReturn( array( $connection_data ) );
		$this->store_integration_service->method( 'createStoreIntegration' )
			->willThrowException( new RuntimeException( 'API error' ) );

		try {
			$this->migration->run();
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Expected.
		}

		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->entity_table} WHERE type = %s AND index_1 = %s",
				'StoreIntegration',
				'store1'
			)
		);
		$this->assertSame( '1', $count );
	}

	/**
	 * Insert a StoreIntegration entity row into the DB.
	 *
	 * @param string $store_id    Store identifier (index_1).
	 * @param string $webhook_url Old webhook URL stored in the entity data.
	 */
	private function insert_store_integration_entity( string $store_id, string $webhook_url ): void {
		$this->wpdb->insert(
			$this->entity_table,
			array(
				'type'    => 'StoreIntegration',
				'index_1' => $store_id,
				'data'    => wp_json_encode(
					array(
						'storeIntegration' => array(
							'webhookUrl' => $webhook_url,
						),
					)
				),
			)
		);
	}
}

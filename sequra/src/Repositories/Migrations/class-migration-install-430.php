<?php
/**
 * Post install migration for version 4.3.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Services\StoreIntegrationService;
use SeQura\WC\Repositories\Interface_Cache_Repository;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use Throwable;

/**
 * Post install migration for version 4.3.0 of the plugin.
 */
class Migration_Install_430 extends Migration {

	/**
	 * Connection service.
	 *
	 * @var ConnectionService
	 */
	private $connection_service;

	/**
	 * Store integration service.
	 *
	 * @var StoreIntegrationService
	 */
	private $store_integration_service;

	/**
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '4.3.0';
	}

	/**
	 * Constructor
	 *
	 * @param \wpdb                    $wpdb                      Database instance.
	 * @param Interface_Cache_Repository $cache                   Cache repository.
	 * @param ConnectionService        $connection_service        Connection service.
	 * @param StoreIntegrationService  $store_integration_service Store integration service.
	 * @param Interface_Logger_Service $logger                    Logger service.
	 */
	public function __construct(
		\wpdb $wpdb,
		Interface_Cache_Repository $cache,
		ConnectionService $connection_service,
		StoreIntegrationService $store_integration_service,
		Interface_Logger_Service $logger
	) {
		parent::__construct( $wpdb, $cache );
		$this->connection_service        = $connection_service;
		$this->store_integration_service = $store_integration_service;
		$this->logger                    = $logger;
	}

	/**
	 * Execute the migration logic.
	 *
	 * @throws Throwable
	 */
	protected function execute(): void {
		$connections = $this->connection_service->getAllConnectionData();

		if ( empty( $connections ) ) {
			return;
		}

		foreach ( $connections as $connection_data ) {
			try {
				$this->store_integration_service->createStoreIntegration( $connection_data );
			} catch ( Throwable $e ) {
				$this->logger->log_error( $e->getMessage(), __FUNCTION__, __CLASS__ );
			}
		}
	}
}

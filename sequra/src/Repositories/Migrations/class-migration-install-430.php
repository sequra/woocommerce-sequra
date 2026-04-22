<?php
/**
 * Post install migration for version 4.3.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\RepositoryContracts\StoreIntegrationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Services\StoreIntegrationService;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\ServiceRegister;
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
	 * @throws Exception When one or more store integration registrations failed (triggers retry).
	 */
	protected function execute(): void {
		/**
		 * Store service.
		 *
		 * @var StoreService $store_service
		 */
		$store_service    = ServiceRegister::getService( StoreService::class );
		$connected_stores = $store_service->getConnectedStores();

		if ( empty( $connected_stores ) ) {
			return;
		}

		$has_failures = false;

		foreach ( $connected_stores as $store_id ) {
			StoreContext::doWithStore(
				$store_id,
				function () use ( &$has_failures ) {
					/**
					 * Store integration repository.
					 *
					 * @var StoreIntegrationRepositoryInterface $store_integration_repository
					 */
					$store_integration_repository = ServiceRegister::getService( StoreIntegrationRepositoryInterface::class );
					$connections                  = $this->connection_service->getAllConnectionData();

					foreach ( $connections as $connection_data ) {
						try {
							if ( null !== $store_integration_repository->getStoreIntegration() ) {
								continue;
							}

							$this->store_integration_service->createStoreIntegration( $connection_data );
						} catch ( Throwable $e ) {
							$this->logger->log_error( $e->getMessage(), __FUNCTION__, __CLASS__ );
							$has_failures = true;
						}
					}
				}
			);
		}

		if ( $has_failures ) {
			throw new Exception( 'One or more store integration registrations failed. Migration will retry on next plugin execution.' );
		}
	}
}

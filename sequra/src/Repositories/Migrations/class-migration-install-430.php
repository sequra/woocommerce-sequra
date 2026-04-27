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
	 * Logger service.
	 *
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Store Service
	 *
	 * @var StoreService
	 */
	private $store_service;

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
	 * @param Interface_Logger_Service $logger                    Logger service.
	 */
	public function __construct(
		\wpdb $wpdb,
		Interface_Cache_Repository $cache,
		StoreService $store_service,
		Interface_Logger_Service $logger
	) {
		parent::__construct( $wpdb, $cache );
		$this->store_service = $store_service;
		$this->logger        = $logger;
	}

	/**
	 * Execute the migration logic.
	 *
	 * @throws Exception When one or more store integration registrations failed (triggers retry).
	 */
	protected function execute(): void {
		$store_ids = $this->store_service->getConnectedStores();

		if ( empty( $store_ids ) ) {
			return;
		}

		$has_failures = false;

		foreach ( $store_ids as $store_id ) {
			StoreContext::doWithStore(
				$store_id,
				function () use ( &$has_failures ) {
					foreach ( $this->get_connection_service()->getAllConnectionData() as $connection_data ) {
						try {
							if ( null !== $this->get_store_integration_repository()->getStoreIntegration() ) {
								// Store integration already exists for this store, skipping.   
								continue;
							}

							$this->get_store_integration_service()->createStoreIntegration( $connection_data );
						} catch ( Throwable $e ) {
							$this->logger->log_throwable( $e );
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

	/**
	 * Get store integration repository instance.
	 */
	private function get_store_integration_repository(): StoreIntegrationRepositoryInterface {
		/**
		 * Store integration repository.
		 *
		 * @var StoreIntegrationRepositoryInterface $repo
		 */
		$repo = ServiceRegister::getService( StoreIntegrationRepositoryInterface::class );
		return $repo;
	}

	/**
	 * Get connection service instance.
	 */
	private function get_connection_service(): ConnectionService {
		/**
		 * Connection service.
		 *
		 * @var ConnectionService $service
		 */
		$service = ServiceRegister::getService( ConnectionService::class );
		return $service;
	}

	/**
	 * Get store integration service instance.
	 */
	private function get_store_integration_service(): StoreIntegrationService {
		/**
		 * Store integration service.
		 *
		 * @var StoreIntegrationService $service
		 */
		$service = ServiceRegister::getService( StoreIntegrationService::class );
		return $service;
	}
}

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
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\DeleteStoreIntegrationRequest;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\ProxyContracts\StoreIntegrationsProxyInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Services\StoreIntegrationService;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Interface_Cache_Repository;
use Throwable;

/**
 * Post install migration for version 4.3.0 of the plugin.
 */
class Migration_Install_430 extends Migration {

	/**
	 * Store Service
	 *
	 * @var StoreService
	 */
	private $store_service;

	/**
	 * Entity table.
	 *
	 * @var string
	 */
	private $entity_table;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '4.3.0';
	}

	/**
	 * Constructor
	 *
	 * @param \wpdb                      $wpdb          Database instance.
	 * @param Interface_Cache_Repository $cache         Cache repository.
	 * @param StoreService               $store_service Store service.
	 */
	public function __construct(
		\wpdb $wpdb,
		Interface_Cache_Repository $cache,
		StoreService $store_service
	) {
		parent::__construct( $wpdb, $cache );
		$this->store_service = $store_service;
		$this->entity_table  = $this->db->prefix . 'sequra_entity';
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
				function () use ( $store_id, &$has_failures ) {
					$old_webhook_url = $this->get_old_webhook_url( $store_id );
					$store_failed    = false;

					foreach ( $this->get_connection_service()->getAllConnectionData() as $connection_data ) {
						if ( null !== $old_webhook_url ) {
							$this->deregister_old_store_integration( $connection_data, $old_webhook_url );
						}

						try {
							$this->get_store_integration_service()->createStoreIntegration( $connection_data );
						} catch ( Throwable $e ) {
							$has_failures = true;
							$store_failed = true;
						}
					}

					if ( ! $store_failed ) {
						$this->delete_old_store_integration_entity( $store_id );
					}
				}
			);
		}

		if ( $has_failures ) {
			throw new Exception( 'One or more store integration registrations failed. Migration will retry on next plugin execution.' );
		}
	}

	/**
	 * Attempt to deregister the old store integration from the seQura API.
	 * Non-fatal: exceptions are silently swallowed.
	 *
	 * @param \SeQura\Core\BusinessLogic\Domain\Connection\Models\ConnectionData $connection_data
	 * @param string $old_webhook_url
	 */
	private function deregister_old_store_integration( $connection_data, string $old_webhook_url ): void {
		try {
			$this->get_store_integrations_proxy()->deleteStoreIntegration(
				new DeleteStoreIntegrationRequest( $connection_data, $old_webhook_url )
			);
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Best-effort: old integration may already be gone on the seQura side.
		}
	}

	/**
	 * Remove the old StoreIntegration entity row from the DB.
	 * Non-fatal: missing rows (0 affected) are silently ignored.
	 *
	 * @param string $store_id
	 */
	private function delete_old_store_integration_entity( string $store_id ): void {
		$this->db->delete(
			$this->entity_table,
			array(
				'type'    => 'StoreIntegration',
				'index_1' => $store_id,
			)
		);
	}

	/**
	 * Read the old webhook URL from the StoreIntegration entity row in the DB.
	 * Returns null if no row exists or if the URL cannot be extracted.
	 *
	 * @param string $store_id Store identifier.
	 * @return string|null
	 */
	private function get_old_webhook_url( string $store_id ): ?string {
		// @phpstan-ignore-next-line
		$row = $this->db->get_row( $this->db->prepare( 'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1', 'StoreIntegration', $store_id ), ARRAY_A );

		if ( ! isset( $row['data'] ) || ! \is_string( $row['data'] ) ) {
			return null;
		}

		/**
		 * Decoded entity data.
		 *
		 * @var array{storeIntegration?: array{webhookUrl?: string}}|null $data
		 */
		$data = json_decode( $row['data'], true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		$url = $data['storeIntegration']['webhookUrl'] ?? null;
		return \is_string( $url ) && '' !== $url ? $url : null;
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

	/**
	 * Get store integrations proxy instance.
	 */
	private function get_store_integrations_proxy(): StoreIntegrationsProxyInterface {
		/**
		 * Store integrations proxy.
		 *
		 * @var StoreIntegrationsProxyInterface $proxy
		 */
		$proxy = ServiceRegister::getService( StoreIntegrationsProxyInterface::class );
		return $proxy;
	}
}

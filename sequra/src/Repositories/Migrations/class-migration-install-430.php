<?php
/**
 * Post install migration for version 4.3.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use Exception;
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\ConnectionDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\CredentialsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\PaymentMethod\RepositoryContracts\PaymentMethodRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\DeleteStoreIntegrationRequest;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\ProxyContracts\StoreIntegrationsProxyInterface;
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

		foreach ( $store_ids as $store_id ) {
			StoreContext::doWithStore(
				$store_id,
				function () use ( $store_id ) {
					$old_webhook_url = $this->get_old_webhook_url( $store_id );

					foreach ( $this->get_connection_service()->getAllConnectionData() as $connection_data ) {
						if ( null !== $old_webhook_url ) {
							$this->deregister_old_store_integration( $connection_data, $old_webhook_url );
						}

						try {
							$this->get_store_integration_service()->createStoreIntegration( $connection_data );
						} catch ( Throwable $e ) {
							$this->try_log( $e );
							if ( 401 === $e->getCode() ) {
								$this->remove_stale_deployment_data( $connection_data );
							}
						}
					}

					$this->delete_old_store_integration_entity( $store_id );
				}
			);
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
		} catch ( Throwable $e ) {
			// Best-effort: old integration may already be gone on the seQura side.
			$this->try_log( $e );
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
	 * Remove the connection data, credentials and payment methods from the database for a given deployment.
	 *
	 * @param \SeQura\Core\BusinessLogic\Domain\Connection\Models\ConnectionData $connection_data
	 */
	private function remove_stale_deployment_data( $connection_data ): void {
		try {
			$deployment_id = $connection_data->getDeployment();
			$merchant_ids  = $this->get_credentials_repository()->deleteCredentialsByDeploymentId( $deployment_id );
			foreach ( $merchant_ids as $merchant_id ) {
				$this->get_payment_method_repository()->deletePaymentMethods( $merchant_id );
			}
				
			$this->get_connection_data_repository()->deleteConnectionDataByDeploymentId( $deployment_id );
		} catch ( Throwable $e ) {
			$this->try_log( $e );
		}
	}

	/**
	 * Get connection data repository instance.
	 */
	private function get_connection_data_repository(): ConnectionDataRepositoryInterface {
		/**
		 * Connection data repository.
		 *
		 * @var ConnectionDataRepositoryInterface $repository
		 */
		$repository = ServiceRegister::getService( ConnectionDataRepositoryInterface::class );
		return $repository;
	}

	/**
	 * Get credentials repository instance.
	 */
	private function get_credentials_repository(): CredentialsRepositoryInterface {
		/**
		 * Credentials repository.
		 *
		 * @var CredentialsRepositoryInterface $repository
		 */
		$repository = ServiceRegister::getService( CredentialsRepositoryInterface::class );
		return $repository;
	}

	/**
	 * Get payment method repository instance.
	 */
	private function get_payment_method_repository(): PaymentMethodRepositoryInterface {
		/**
		 * Payment method repository.
		 *
		 * @var PaymentMethodRepositoryInterface $repository
		 */
		$repository = ServiceRegister::getService( PaymentMethodRepositoryInterface::class );
		return $repository;
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

	/**
	 * Best-effort logging via the logger service.
	 * The logger service depends on DB configuration that may not be
	 * available during migrations, so failures are silently ignored.
	 *
	 * @param Throwable $throwable The exception to log.
	 */
	private function try_log( Throwable $throwable ): void {
		try {
			/**
			 * Logger service.
			 *
			 * @var Interface_Logger_Service $logger
			 */
			$logger = ServiceRegister::getService( Interface_Logger_Service::class );
			$logger->log_throwable( $throwable, __FUNCTION__, __CLASS__ );
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Logger not available or failed — fallback to error_log if WP_DEBUG is enabled.
			if ( \defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! \defined( 'WP_DEBUG_LOG' ) || ! empty( WP_DEBUG_LOG ) ) ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log(
					print_r(
						array(
							'error' => $e->getMessage(),
							'file'  => $e->getFile(),
							'line'  => $e->getLine(),
							'trace' => $e->getTraceAsString(),
						),
						true
					) 
				);
				// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}
		}
	}
}

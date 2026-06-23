<?php
/**
 * Post install migration for version 4.3.3 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Stores\Services\StoreService;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Interface_Cache_Repository;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use Throwable;

/**
 * Post install migration for version 4.3.3 of the plugin.
 *
 * Backfills the affiliate settings for stores that are already connected and won't
 * reconnect. It re-validates each stored connection, which makes the core re-fetch the
 * merchant configuration from seQura (now carrying the `affiliate` block) and persist it
 * through the connect-time consumer. Best-effort and idempotent: a connection without an
 * affiliate block, or a transient credentials/HTTP failure, is logged and skipped without
 * aborting the backfill for the remaining stores.
 */
class Migration_Install_433 extends Migration {

	/**
	 * Store service.
	 *
	 * @var StoreService
	 */
	private $store_service;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '4.3.3';
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
	}

	/**
	 * Execute the migration logic.
	 *
	 * Re-validates each connection so the core re-fetches the merchant configuration and
	 * persists the affiliate block for the already-connected fleet.
	 */
	protected function execute(): void {
		$store_ids = $this->store_service->getConnectedStores();

		if ( empty( $store_ids ) ) {
			return;
		}

		foreach ( $store_ids as $store_id ) {
			StoreContext::doWithStore(
				$store_id,
				function () {
					foreach ( $this->get_connection_service()->getAllConnectionData() as $connection_data ) {
						try {
							$this->get_connection_service()->isConnectionDataValid( $connection_data );
						} catch ( Throwable $e ) {
							// Best-effort: stale credentials or a transient seQura failure must not
							// abort the affiliate backfill for the remaining stores/connections.
							$this->try_log( $e );
						}
					}
				}
			);
		}
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

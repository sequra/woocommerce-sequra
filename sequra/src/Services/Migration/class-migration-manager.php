<?php
/**
 * Run migrations to make changes to the database.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Migration;

use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Repositories\Migrations\Critical_Migration_Exception;
use SeQura\WC\Repositories\Migrations\Migration;
use Throwable;

/**
 * Run migrations to make changes to the database.
 */
class Migration_Manager implements Interface_Migration_Manager {

	/**
	 * Migration list.
	 * 
	 * @var Migration[]
	 */
	private $migrations;

	/**
	 * Current version of the plugin.
	 * 
	 * @var string
	 */
	private $current_version;

	/**
	 * Plugin basename.
	 * 
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Configuration service.
	 * 
	 * @var Configuration
	 */
	private $configuration;

	private const MIGRATION_LOCK = 'sequra_migration_lock';

	/**
	 * Constructor
	 *
	 * @param Migration[]         $migrations Migration list.
	 */
	public function __construct( string $plugin_basename, Configuration $configuration, string $current_version, array $migrations ) {
		$this->plugin_basename = $plugin_basename;
		$this->migrations      = $migrations;
		$this->current_version = $current_version;
		$this->configuration   = $configuration;
	}

	/**
	 * Migration to run when the plugin was updated.
	 */
	public function run_install_migrations(): void {
		$db_module_version = $this->configuration->get_module_version();
		if ( -1 !== version_compare( $db_module_version, $this->current_version ) ) {
			return;
		}

		if ( (bool) get_transient( self::MIGRATION_LOCK ) ) {
			return;
		}

		set_transient( self::MIGRATION_LOCK, 1, 10 * 60 ); // Set 10 minutes as the maximum time to run the migrations.
		$deactivate = false;
		
		try {
			foreach ( $this->migrations as $migration ) {
				if ( -1 === version_compare( $db_module_version, $migration->get_version() ) ) {
					$migration->run();
					$this->configuration->set_module_version( $migration->get_version() );
				}
			}
		} catch ( Critical_Migration_Exception $e ) {
			// ! Critical migration failed and the plugin cannot work properly. Deactivate the plugin and log the error.
			$deactivate = true;
			$this->log_error( $e );
		} catch ( Throwable $e ) {
			// ! Non-critical migration failed. Stop the process and log the error.
			$this->log_error( $e );
		} finally {
			delete_transient( self::MIGRATION_LOCK );
			if ( $deactivate ) {
				deactivate_plugins( $this->plugin_basename );
			}
		}
	}

	/**
	 * Migrations to run when the plugin is uninstalled.
	 */
	public function run_uninstall_migrations(): void {
		// Implement run_uninstall_migrations() method.
	}

	/**
	 * Log an error using the default logger instead of the 
	 * existing logger service that relies on the database
	 * configuration that may not be available during the
	 * migration process.
	 */
	private function log_error( Throwable $error ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_LOG' ) || ! empty( WP_DEBUG_LOG ) ) ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
			error_log(
				print_r(
					array(
						'error' => $error->getMessage(),
						'file'  => $error->getFile(),
						'line'  => $error->getLine(),
						'trace' => $error->getTraceAsString(),
					),
					true
				) 
			);
			// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}
}

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
use SeQura\WC\Services\Interface_Logger_Service;
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

	/**
	 * Logger service.
	 * 
	 * @var Interface_Logger_Service
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param Migration[]         $migrations Migration list.
	 */
	public function __construct( string $plugin_basename, Interface_Logger_Service $logger, Configuration $configuration, string $current_version, array $migrations ) {
		$this->plugin_basename = $plugin_basename;
		$this->migrations      = $migrations;
		$this->current_version = $current_version;
		$this->configuration   = $configuration;
		$this->logger          = $logger;
	}

	/**
	 * Migration to run when the plugin was updated.
	 */
	public function run_install_migrations(): void {
		$db_module_version = $this->configuration->get_module_version();
		if ( -1 !== version_compare( $db_module_version, $this->current_version ) ) {
			return;
		}

		foreach ( $this->migrations as $migration ) {
			if ( -1 === version_compare( $db_module_version, $migration->get_version() ) ) {
				try {
					$migration->run();
					$this->configuration->set_module_version( $migration->get_version() );
				} catch ( Critical_Migration_Exception $e ) {
					// Critical migration failed and the plugin cannot work properly. 
					// Stop the process, deactivate the plugin and log the error.
					$this->logger->log_throwable( $e );
					deactivate_plugins( $this->plugin_basename, true );
					return;
				} catch ( Throwable $e ) {
					// Non-critical migration failed. Stop the process and log the error.
					$this->logger->log_throwable( $e );
					return;
				}
			}
		}
	}

	/**
	 * Migrations to run when the plugin is uninstalled.
	 */
	public function run_uninstall_migrations(): void {
		// Implement run_uninstall_migrations() method.
	}
}

<?php
/**
 * Run migrations to make changes to the database.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Migration;

use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use SeQura\WC\Repositories\Migrations\Migration;

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
	 * Configuration service.
	 * 
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * Constructor
	 * 
	 * @param Configuration $configuration Configuration service.
	 * @param string        $current_version Current version of the plugin.
	 * @param Migration[]         $migrations Migration list.
	 */
	public function __construct( Configuration $configuration, $current_version, array $migrations ) {
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

		foreach ( $this->migrations as $migration ) {
			if ( -1 === version_compare( $db_module_version, $migration->get_version() ) ) {
				$migration->run();
				
				// Update the module version in the database.
				$this->configuration->set_module_version( $migration->get_version() );
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

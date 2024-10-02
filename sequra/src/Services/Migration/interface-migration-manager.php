<?php
/**
 * Run migrations to make changes to the database.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Migration;

/**
 * Run migrations to make changes to the database.
 */
interface Interface_Migration_Manager {

	/**
	 * Migration to run when the plugin was updated.
	 */
	public function run_install_migrations(): void;

	/**
	 * Migrations to run when the plugin is uninstalled.
	 */
	public function run_uninstall_migrations(): void;
}

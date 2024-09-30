<?php
/**
 * Database migration interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories/Migrations
 */

namespace SeQura\WC\Repositories\Migrations;

use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;

/**
 * Database migration interface
 */
abstract class Migration {

	/**
	 * Database session object.
	 *
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * Configuration service.
	 *
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * Constructor
	 *
	 * @param \wpdb $wpdb Database instance.
	 */
	public function __construct( \wpdb $wpdb, Configuration $configuration ) {
		$this->db            = $wpdb;
		$this->configuration = $configuration;
	}

	/**
	 * Get the plugin version when the changes were made.
	 */
	abstract public function get_version(): string;

	/**
	 * Run the migration.
	 * 
	 * @throws \Throwable
	 */
	abstract public function run(): void;
}

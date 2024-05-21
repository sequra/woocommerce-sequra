<?php
/**
 * Database migration interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories/Migrations
 */

namespace SeQura\WC\Repositories\Migrations;

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
	 * Constructor
	 *
	 * @param \wpdb $wpdb Database instance.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->db = $wpdb;
	}

	/**
	 * Get the plugin version when the changes were made.
	 */
	abstract public function get_version(): string;

	/**
	 * Run the migration.
	 */
	abstract public function run(): void;
}

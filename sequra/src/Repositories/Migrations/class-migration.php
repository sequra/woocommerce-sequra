<?php
/**
 * Database migration interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories/Migrations
 */

namespace SeQura\WC\Repositories\Migrations;

use SeQura\WC\Repositories\Interface_Cache_Repository;
use SeQura\WC\Repositories\Repository;

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
	 * Cache repository.
	 *
	 * @var Interface_Cache_Repository
	 */
	protected $cache;

	/**
	 * Constructor
	 *
	 * @param \wpdb                    $wpdb  Database instance.
	 * @param Interface_Cache_Repository $cache Cache repository.
	 */
	public function __construct( \wpdb $wpdb, Interface_Cache_Repository $cache ) {
		$this->db    = $wpdb;
		$this->cache = $cache;
	}

	/**
	 * Get the plugin version when the changes were made.
	 */
	abstract public function get_version(): string;

	/**
	 * Run the migration with the repository cache temporarily disabled.
	 *
	 * Migrations mix raw SQL operations (which bypass the Repository cache) with
	 * AdminAPI saves (which go through the cached Repository). Disabling the cache
	 * prevents stale cached reads from interfering with the migration process.
	 * 
	 * @throws \Throwable
	 */
	final public function run(): void {
		\add_filter( 'sequra_cache_enabled', array( $this, 'sequra_cache_enabled_callback' ), 999 );
		Repository::$cache_enabled = null;

		try {
			$this->execute();
		} finally {
			\remove_filter( 'sequra_cache_enabled', array( $this, 'sequra_cache_enabled_callback' ), 999 );
			Repository::$cache_enabled = null;
			// Flush caches so the plugin reads fresh data after the migration.
			$this->cache->flush();
		}
	}

	/**
	 * Disable the cache
	 *
	 * @return bool
	 */
	public function sequra_cache_enabled_callback(): bool {
		return false;
	}

	/**
	 * Execute the migration logic.
	 * 
	 * @throws \Throwable
	 */
	abstract protected function execute(): void;
}

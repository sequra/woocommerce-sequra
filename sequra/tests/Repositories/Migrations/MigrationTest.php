<?php
/**
 * Tests for the Migration base class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use Exception;
use SeQura\WC\Repositories\Cache_Repository;
use SeQura\WC\Repositories\Repository;
use WP_UnitTestCase;

require_once __DIR__ . '/Stub_Migration.php';

// phpcs:disable WordPress.WP.AlternativeFunctions.wp_cache

class MigrationTest extends WP_UnitTestCase {

	/**
	 * @var Stub_Migration
	 */
	private $migration;

	/**
	 * @var Cache_Repository
	 */
	private $cache;

	public function set_up() {
		parent::set_up();
		$this->cache                    = new Cache_Repository();
		$this->migration                = new Stub_Migration( $GLOBALS['wpdb'], $this->cache );
		Repository::$cache_enabled      = null;
		Cache_Repository::$static_cache = array();
		wp_cache_flush();
	}

	public function tear_down() {
		Repository::$cache_enabled      = null;
		Cache_Repository::$static_cache = array();
		parent::tear_down();
	}

	public function testRun_disablesCacheDuringExecution() {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		$this->assertTrue( (bool) apply_filters( 'sequra_cache_enabled', true ) );

		$this->migration->run();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		$this->assertTrue( (bool) apply_filters( 'sequra_cache_enabled', true ) );
		$this->assertTrue( $this->migration->executed );
	}

	public function testRun_flushesCacheAfterExecution() {
		// Seed caches with data that should be flushed.
		$this->cache->set( 'key', 'stale_value', Repository::DATA_CACHE_GROUP );

		$this->migration->run();

		// Both static and WP object caches should be empty.
		$this->assertSame( array(), Cache_Repository::$static_cache );
		$found = false;
		wp_cache_get( 'key', Repository::DATA_CACHE_GROUP, false, $found );
		$this->assertFalse( $found );
	}

	public function testRun_flushesCacheEvenWhenExecutionThrows() {
		$this->migration->should_throw = true;

		// Seed caches.
		$this->cache->set( 'key', 'stale_value', Repository::DATA_CACHE_GROUP );

		try {
			$this->migration->run();
			$this->fail( 'Expected exception was not thrown' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Migration failed', $e->getMessage() );
		}

		// Cache must still be flushed (finally block).
		$this->assertSame( array(), Cache_Repository::$static_cache );
		$found = false;
		wp_cache_get( 'key', Repository::DATA_CACHE_GROUP, false, $found );
		$this->assertFalse( $found );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		$this->assertTrue( (bool) apply_filters( 'sequra_cache_enabled', true ) );
	}

	public function testRun_restoresCacheEnabledFlag() {
		// Start with cache enabled (default).
		Repository::$cache_enabled = null;

		$this->migration->run();

		// After run(), the static flag should be reset to null so it re-evaluates.
		$this->assertNull( Repository::$cache_enabled );
	}
}

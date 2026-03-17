<?php
/**
 * Tests for the Cache_Repository class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories;

use SeQura\WC\Repositories\Cache_Repository;
use WP_UnitTestCase;

class CacheRepositoryTest extends WP_UnitTestCase {

	/**
	 * @var Cache_Repository
	 */
	private $cache;

	public function set_up() {
		parent::set_up();
		$this->cache = new Cache_Repository();
		$this->reset_static_cache();
	}

	public function tear_down() {
		$this->reset_static_cache();
		parent::tear_down();
	}

	// --- get() ---

	public function testGet_cacheMiss_returnsFalseAndFoundIsFalse() {
		$found  = true;
		$result = $this->cache->get( 'missing_key', 'test_group', $found );

		$this->assertFalse( $result );
		$this->assertFalse( $found );
	}

	public function testGet_afterSet_returnsCachedValueAndFoundIsTrue() {
		$this->cache->set( 'key', 'value', 'test_group' );

		$found  = false;
		$result = $this->cache->get( 'key', 'test_group', $found );

		$this->assertSame( 'value', $result );
		$this->assertTrue( $found );
	}

	public function testGet_falsyCachedValue_foundIsTrue() {
		// Ensures a cached `false` is distinguishable from a cache miss.
		$this->cache->set( 'key', false, 'test_group' );

		$found  = false;
		$result = $this->cache->get( 'key', 'test_group', $found );

		$this->assertFalse( $result );
		$this->assertTrue( $found );
	}

	public function testGet_zeroCachedValue_foundIsTrue() {
		// Ensures a cached `0` is distinguishable from a cache miss.
		$this->cache->set( 'key', 0, 'test_group' );

		$found  = false;
		$result = $this->cache->get( 'key', 'test_group', $found );

		$this->assertSame( 0, $result );
		$this->assertTrue( $found );
	}

	public function testGet_fromWpObjectCacheAfterStaticCacheCleared_promoteToStaticCache() {
		// Seed the WP object cache directly (simulates a value cached in a previous request).
		wp_cache_set( 'key', 'from_wp_cache', 'test_group' );

		// Static cache is empty (reset in set_up), so the repository will fall through to WP cache.
		$found  = false;
		$result = $this->cache->get( 'key', 'test_group', $found );

		$this->assertSame( 'from_wp_cache', $result );
		$this->assertTrue( $found );

		// A second get should now be served from static cache (promotion happened).
		wp_cache_delete( 'key', 'test_group' );
		$found_after_wp_delete = false;
		$result_static         = $this->cache->get( 'key', 'test_group', $found_after_wp_delete );
		$this->assertSame( 'from_wp_cache', $result_static );
		$this->assertTrue( $found_after_wp_delete );
	}

	// --- set() ---

	public function testSet_valueStoredInWpObjectCache() {
		$this->cache->set( 'key', 'value', 'test_group', 60 );

		$wp_found = false;
		$result   = wp_cache_get( 'key', 'test_group', false, $wp_found );

		$this->assertTrue( $wp_found );
		$this->assertSame( 'value', $result );
	}

	// --- delete() ---

	public function testDelete_afterSet_getReturnsMiss() {
		$this->cache->set( 'key', 'value', 'test_group' );
		$this->cache->delete( 'key', 'test_group' );

		$found  = true;
		$result = $this->cache->get( 'key', 'test_group', $found );

		$this->assertFalse( $result );
		$this->assertFalse( $found );
	}

	public function testDelete_removesFromWpObjectCache() {
		$this->cache->set( 'key', 'value', 'test_group' );
		$this->cache->delete( 'key', 'test_group' );

		$wp_found = false;
		wp_cache_get( 'key', 'test_group', false, $wp_found );

		$this->assertFalse( $wp_found );
	}

	// --- increment() ---

	public function testIncrement_keyNotExist_returnsOne() {
		$result = $this->cache->increment( 'new_key', 'test_group' );

		$this->assertSame( 1, $result );
	}

	public function testIncrement_existingKey_incrementsByOne() {
		$this->cache->set( 'key', 5, 'test_group' );

		$result = $this->cache->increment( 'key', 'test_group' );

		$this->assertSame( 6, $result );
	}

	public function testIncrement_multipleIncrements_returnsCorrectValue() {
		$this->cache->increment( 'key', 'test_group' );
		$this->cache->increment( 'key', 'test_group' );
		$result = $this->cache->increment( 'key', 'test_group' );

		$this->assertSame( 3, $result );
	}

	public function testIncrement_staticCacheReflectsNewValue() {
		// After increment, a subsequent get() must return the updated value (not stale 0).
		$this->cache->increment( 'key', 'test_group' );
		$this->cache->increment( 'key', 'test_group' );

		$found  = false;
		$result = $this->cache->get( 'key', 'test_group', $found );

		$this->assertTrue( $found );
		$this->assertSame( 2, $result );
	}

	// --- flush() ---

	public function testFlush_clearsStaticCacheAndWpObjectCache() {
		$this->cache->set( 'key1', 'value1', \SeQura\WC\Repositories\Repository::TABLE_EXISTS_CACHE_GROUP );
		$this->cache->set( 'key2', 'value2', \SeQura\WC\Repositories\Repository::DATA_CACHE_GROUP );

		$this->cache->flush();

		// Static cache should be empty.
		$this->assertSame( array(), Cache_Repository::$static_cache );

		// WP object cache should also be cleared.
		$wp_found = false;
		wp_cache_get( 'key1', \SeQura\WC\Repositories\Repository::TABLE_EXISTS_CACHE_GROUP, false, $wp_found );
		$this->assertFalse( $wp_found );

		$wp_found = false;
		wp_cache_get( 'key2', \SeQura\WC\Repositories\Repository::DATA_CACHE_GROUP, false, $wp_found );
		$this->assertFalse( $wp_found );
	}

	// --- Helpers ---

	/**
	 * Resets the static in-memory cache between tests.
	 */
	private function reset_static_cache(): void {
		Cache_Repository::$static_cache = array();
	}
}

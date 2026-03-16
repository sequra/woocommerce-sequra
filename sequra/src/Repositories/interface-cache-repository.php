<?php
/**
 * Cache repository interface.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

/**
 * Provides caching capabilities with a two-level strategy:
 * WordPress object cache (persistent when Redis/Memcached is configured)
 * backed by a static in-memory array for per-request fast path.
 */
interface Interface_Cache_Repository {

	/**
	 * Get a value from the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @param bool   $found Whether the key was found in the cache. Passed by reference.
	 *                      Distinguishes between a cached false/0 value and a cache miss.
	 *
	 * @return mixed Cached value, or false on miss.
	 */
	public function get( $key, $group, &$found = false );

	/**
	 * Set a value in the cache.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to store. Must be serializable.
	 * @param string $group Cache group.
	 * @param int    $ttl   Time to live in seconds. 0 means no expiration (only applies to persistent backends).
	 */
	public function set( $key, $value, $group, $ttl = 0 ): bool;

	/**
	 * Delete a value from the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 */
	public function delete( $key, $group ): bool;
}

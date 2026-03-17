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

	/**
	 * Atomically increment a numeric value in the cache.
	 *
	 * Uses wp_cache_incr() which is atomic on Redis/Memcached backends,
	 * eliminating the read-then-write race of get()+set().
	 * If the key does not exist it is initialised to 1 with the given TTL.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @param int    $ttl   TTL used only when the key is first created. 0 = no expiration.
	 *
	 * @return int New value after increment.
	 */
	public function increment( $key, $group, $ttl = 0 ): int;

	/**
	 * Flush all cached data from both the static in-memory array and the WordPress object cache.
	 */
	public function flush(): void;
}

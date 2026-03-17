<?php
/**
 * Cache repository implementation.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

/**
 * Two-level cache: static in-memory array (per-request fast path)
 * + WordPress object cache (persistent when a backend is configured).
 *
 * The static array is checked first to avoid repeated wp_cache_get() calls
 * (each of which may involve a network round-trip to Redis/Memcached).
 * On a cache hit from the WordPress object cache, the value is promoted to
 * the static array for the remainder of the request.
 */
class Cache_Repository implements Interface_Cache_Repository {

	/**
	 * Per-request in-memory cache, keyed by group then by key.
	 *
	 * Public to allow test suites to reset the static state between tests
	 * without requiring Reflection.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public static $static_cache = array();

	/**
	 * Get a value from the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @param bool   $found Whether the key was found in the cache. Passed by reference.
	 *
	 * @return mixed Cached value, or false on miss.
	 */
	public function get( $key, $group, &$found = false ) {
		// Level 1: static array — no serialization, no network.
		if ( \array_key_exists( $group, self::$static_cache )
			&& \array_key_exists( $key, self::$static_cache[ $group ] ) ) {
			$found = true;
			return self::$static_cache[ $group ][ $key ];
		}

		// Level 2: WordPress object cache (persistent if Redis/Memcached configured).
		$wp_found = false;
		$value    = \wp_cache_get( $key, $group, false, $wp_found );
		if ( $wp_found ) {
			// Promote to static cache for the remainder of the request.
			self::$static_cache[ $group ][ $key ] = $value;
			$found                                = true;
			return $value;
		}

		$found = false;
		return false;
	}

	/**
	 * Set a value in the cache.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to store. Must be serializable.
	 * @param string $group Cache group.
	 * @param int    $ttl   Time to live in seconds. 0 means no expiration.
	 */
	public function set( $key, $value, $group, $ttl = 0 ): bool {
		self::$static_cache[ $group ][ $key ] = $value;
		//phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
		return \wp_cache_set( $key, $value, $group, $ttl );
	}

	/**
	 * Delete a value from the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 */
	public function delete( $key, $group ): bool {
		unset( self::$static_cache[ $group ][ $key ] );
		return \wp_cache_delete( $key, $group );
	}

	/**
	 * Atomically increment a numeric value in the cache.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @param int    $ttl   TTL used only when the key is first created.
	 *
	 * @return int New value after increment.
	 */
	public function increment( $key, $group, $ttl = 0 ): int {
		// wp_cache_incr() is atomic on Redis/Memcached; it returns false if the key does not exist.
		$new_value = \wp_cache_incr( $key, 1, $group );
		if ( false === $new_value ) {
			// Key did not exist; use add() (a no-op if another request created it concurrently)
			// then retry the atomic incr() to avoid two requests both writing 1.
			//phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
			\wp_cache_add( $key, 0, $group, $ttl );
			$new_value = \wp_cache_incr( $key, 1, $group );
			if ( false === $new_value ) {
				// Fallback for backends where incr after add still fails (e.g. default WP array cache without the key).
				//phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
				\wp_cache_set( $key, 1, $group, $ttl );
				$new_value = 1;
			}
		}
		// Keep static cache in sync so subsequent get() calls in this request see the new value.
		self::$static_cache[ $group ][ $key ] = $new_value;
		return (int) $new_value;
	}

	/**
	 * Flush all cached data from both the static in-memory array and the WordPress object cache.
	 *
	 * Uses wp_cache_flush_group() per seQura group when available (WP 6.1+) to avoid flushing
	 * the entire object cache — which would evict entries for other sites in a multisite network.
	 * Falls back to wp_cache_flush() on older WordPress versions.
	 */
	public function flush(): void {
		self::$static_cache = array();
		if ( \function_exists( 'wp_cache_flush_group' ) ) {
			\wp_cache_flush_group( Repository::TABLE_EXISTS_CACHE_GROUP );
			\wp_cache_flush_group( Repository::DATA_CACHE_GROUP );
		} else {
			\wp_cache_flush();
		}
	}
}

<?php
/**
 * Settings interface
 *
 * @package    Sequra/WC
 * @subpackage Sequra/WC/Services
 */

namespace Sequra\WC\Services;

/**
 * Settings interface
 */
interface Interface_Settings {

	/**
	 * Get preferences. Also sets defaults if not set.
	 *
	 * @return array Array of preferences.
	 */
	public function all();

	/**
	 * Get preference by key
	 *
	 * @param string $key Preference key.
	 *
	 * @return mixed Preference value. Null if not found.
	 */
	public function get( $key );

	/**
	 * Get default preferences
	 *
	 * @return array Array of default preferences.
	 */
	public function defaults();

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled();
}

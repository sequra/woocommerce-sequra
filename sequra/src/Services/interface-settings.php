<?php
/**
 * Settings interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

/**
 * Settings interface
 */
interface Interface_Settings {

	/**
	 * Get preferences. Also sets defaults if not set.
	 *
	 * @return mixed[] Array of preferences.
	 */
	public function all();

	/**
	 * Get preference by key
	 *
	 * @param string $key Preference key.
	 *
	 * @return mixed|null Preference value. Null if not found.
	 */
	public function get( $key );

	/**
	 * Get default preferences
	 *
	 * @return mixed[] Array of default preferences.
	 */
	public function defaults();

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled();

	/**
	 * Get general settings.
	 *
	 * @param int|null $blog_id The blog ID.
	 * 
	 * @return mixed
	 */
	public function get_general_settings( $blog_id = null );

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool;
}

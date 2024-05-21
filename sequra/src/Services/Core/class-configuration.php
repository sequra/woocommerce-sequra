<?php
/**
 * Extends the Configuration class.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

/**
 * Extends the Configuration class.
 */
abstract class Configuration extends \SeQura\Core\Infrastructure\Configuration\Configuration {

	/**
	 * Gets the current version of the module/integration.
	 *
	 * @return string The version number.
	 */
	abstract public function get_module_version(): string;

	/**
	 * Gets the current version of the module/integration.
	 *
	 * @param string $version The version number.
	 */
	abstract public function set_module_version( $version ): void;

	/**
	 * Check if the current page is the settings page.
	 */
	abstract public function is_settings_page(): bool;

	/**
	 * Get the configuration page slug.
	 */
	abstract public function get_page(): string;

	/**
	 * Get the configuration page parent slug.
	 */
	abstract public function get_parent_page(): string;
}

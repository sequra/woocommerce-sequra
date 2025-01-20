<?php
/**
 * Constants Interface
 *
 * @package    SeQura/WC/NoAddress
 * @subpackage SeQura/WC/NoAddress/Services
 */

namespace SeQura\WC\NoAddress\Services;

/**
 * Provides methods access application Constants.
 */
interface Interface_Constants {

	/**
	 * Get the plugin directory path.
	 */
	public function get_plugin_dir_path(): string;

	/**
	 * Get the plugin file path.
	 */
	public function get_plugin_file_path(): string;

	/**
	 * Get the plugin basename.
	 */
	public function get_plugin_basename(): string;

	/**
	 * Get the plugin rest version.
	 */
	public function get_plugin_data(): array;
}

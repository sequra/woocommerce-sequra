<?php

/**
 * Constants
 *
 * @package    SeQura/WC/NoAddress
 * @subpackage SeQura/WC/NoAddress/Services
 */

namespace SeQura\WC\NoAddress\Services;

/**
 * Provides methods access application Constants.
 */
class Constants implements Interface_Constants {

	/**
	 * The plugin directory path.
	 *
	 * @var string
	 */
	private $plugin_dir_path;

	/**
	 * The plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file_path;

	/**
	 * The plugin basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * The plugin data.
	 *
	 * @var array<string, string>
	 */
	private $plugin_data;

	/**
	 * Constructor.
	 *
	 * @param string                $plugin_dir_path The plugin directory path.
	 * @param string                $plugin_file_path The plugin file path.
	 * @param string                $plugin_basename The plugin basename.
	 * @param array<string, string> $plugin_data The plugin data.
	 */
	public function __construct( $plugin_dir_path, $plugin_file_path, $plugin_basename, $plugin_data ) {
		$this->plugin_dir_path  = (string) $plugin_dir_path;
		$this->plugin_file_path = (string) $plugin_file_path;
		$this->plugin_basename  = (string) $plugin_basename;
		$this->plugin_data      = (array) $plugin_data;
	}

	/**
	 * Get the plugin directory path.
	 */
	public function get_plugin_dir_path(): string {
		return $this->plugin_dir_path;
	}
	/**
	 * Get the plugin file path.
	 */
	public function get_plugin_file_path(): string {
		return $this->plugin_file_path;
	}
	/**
	 * Get the plugin basename.
	 */
	public function get_plugin_basename(): string {
		return $this->plugin_basename;
	}
	
	/**
	 * Get the plugin data.
	 */
	public function get_plugin_data(): array {
		return $this->plugin_data;
	}
}

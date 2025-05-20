<?php
/**
 * Constants
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services;

/**
 * Provides methods access application Constants.
 */
class Constants implements Interface_Constants {

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private $plugin_dir_path;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file_path;

	/**
	 * Plugin log file path.
	 *
	 * @var string
	 */
	private $plugin_log_file_path;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Plugin directory URL.
	 *
	 * @var string
	 */
	private $plugin_dir_url;

	/**
	 * Plugin rest namespace.
	 *
	 * @var string
	 */
	private $plugin_rest_namespace;

	/**
	 * Plugin data.
	 *
	 * @var array
	 */
	private $plugin_data;

	/**
	 * WooCommerce data.
	 *
	 * @var array
	 */
	private $woocommerce_data;

	/**
	 * Environment data.
	 *
	 * @var array
	 */
	private $environment_data;

	/**
	 * Plugin templates path.
	 *
	 * @var string
	 */
	private $plugin_templates_path;

	/**
	 * Plugin assets path.
	 *
	 * @var string
	 */
	private $plugin_assets_path;

	/**
	 * Plugin assets URL.
	 *
	 * @var string
	 */
	private $plugin_assets_url;

	/**
	 * Constructor.
	 * 
	 * @param string $plugin_dir_path The plugin directory path.
	 * @param string $plugin_file_path The plugin file path.
	 * @param string $plugin_log_file_path The plugin log file path.
	 * @param string $plugin_basename The plugin basename.
	 * @param string $plugin_dir_url The plugin directory URL.
	 * @param string $plugin_rest_namespace The plugin rest namespace.
	 * @param array $plugin_data The plugin data.
	 * @param array $woocommerce_data The WooCommerce data.
	 * @param array $environment_data The environment data.
	 * @param string $plugin_templates_path The plugin templates path.
	 * @param string $plugin_assets_path The plugin assets path.
	 * @param string $plugin_assets_url The plugin assets URL.
	 */
	public function __construct(
		$plugin_dir_path,
		$plugin_file_path,
		$plugin_log_file_path,
		$plugin_basename,
		$plugin_dir_url,
		$plugin_rest_namespace,
		$plugin_data,
		$woocommerce_data,
		$environment_data,
		$plugin_templates_path,
		$plugin_assets_path,
		$plugin_assets_url
	) {
		$this->plugin_dir_path       = (string) $plugin_dir_path;
		$this->plugin_file_path      = (string) $plugin_file_path;
		$this->plugin_log_file_path  = (string) $plugin_log_file_path;
		$this->plugin_basename       = (string) $plugin_basename;
		$this->plugin_dir_url        = (string) $plugin_dir_url;
		$this->plugin_rest_namespace = (string) $plugin_rest_namespace;
		$this->plugin_data           = (array) $plugin_data;
		$this->woocommerce_data      = (array) $woocommerce_data;
		$this->environment_data      = (array) $environment_data;
		$this->plugin_templates_path = (string) $plugin_templates_path;
		$this->plugin_assets_path    = (string) $plugin_assets_path;
		$this->plugin_assets_url     = (string) $plugin_assets_url;
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
	 * Get the plugin log file path.
	 */
	public function get_plugin_log_file_path(): string {
		return $this->plugin_log_file_path;
	}
	/**
	 * Get the plugin basename.
	 */
	public function get_plugin_basename(): string {
		return $this->plugin_basename;
	}
	/**
	 * Get the plugin directory URL.
	 */
	public function get_plugin_dir_url(): string {
		return $this->plugin_dir_url;
	}
	/**
	 * Get the plugin rest namespace.
	 */
	public function get_plugin_rest_namespace(): string {
		return $this->plugin_rest_namespace;
	}
	/**
	 * Get the WooCommerce data.
	 */
	public function get_woocommerce_data(): array {
		return $this->woocommerce_data;
	}
	
	/**
	 * Get the plugin data.
	 */
	public function get_plugin_data(): array {
		return $this->plugin_data;
	}
	/**
	 * Get the plugin rest version.
	 */
	public function get_environment_data(): array {
		return $this->environment_data;
	}
	/**
	 * Get the plugin templates path.
	 */
	public function get_plugin_templates_path(): string {
		return $this->plugin_templates_path;
	}
	/**
	 * Get the plugin assets path.
	 */
	public function get_plugin_assets_path(): string {
		return $this->plugin_assets_path;
	}
	/**
	 * Get the plugin assets URL.
	 */
	public function get_plugin_assets_url(): string {
		return $this->plugin_assets_url;
	}

	/**
	 * Hook for adding order indexes.
	 */
	public function get_hook_add_order_indexes(): string {
		return 'sequra_add_order_indexes';
	}
}

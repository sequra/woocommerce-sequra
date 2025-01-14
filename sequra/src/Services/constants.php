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
	 * @var string
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
		$this->plugin_dir_path       = $plugin_dir_path;
		$this->plugin_file_path      = $plugin_file_path;
		$this->plugin_log_file_path  = $plugin_log_file_path;
		$this->plugin_basename       = $plugin_basename;
		$this->plugin_dir_url        = $plugin_dir_url;
		$this->plugin_rest_namespace = $plugin_rest_namespace;
		$this->plugin_data           = $plugin_data;
		$this->woocommerce_data      = $woocommerce_data;
		$this->environment_data      = $environment_data;
		$this->plugin_templates_path = $plugin_templates_path;
		$this->plugin_assets_path    = $plugin_assets_path;
		$this->plugin_assets_url     = $plugin_assets_url;
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
		return (array) $this->woocommerce_data;
	}
	
	/**
	 * Get the plugin data.
	 */
	public function get_plugin_data(): array {
		return (array) $this->plugin_data;
	}
	/**
	 * Get the plugin rest version.
	 */
	public function get_environment_data(): array {
		return (array) $this->environment_data;
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
}

<?php
/**
 * Constants Interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Constants;

/**
 * Provides methods access application Constants.
 */
interface Interface_Constants {

	/**
	 * Get the integration name.
	 */
	public function get_integration_name(): string;

	/**
	 * Get the plugin directory path.
	 */
	public function get_plugin_dir_path(): string;
	/**
	 * Get the plugin file path.
	 */
	public function get_plugin_file_path(): string;
	/**
	 * Get the plugin log file path.
	 */
	public function get_plugin_log_file_path(): string;
	/**
	 * Get the plugin basename.
	 */
	public function get_plugin_basename(): string;
	/**
	 * Get the plugin directory URL.
	 */
	public function get_plugin_dir_url(): string;
	/**
	 * Get the plugin rest namespace.
	 */
	public function get_plugin_rest_namespace(): string;
	/**
	 * Get the plugin rest version.
	 */
	public function get_woocommerce_data(): array;
	/**
	 * Get the plugin rest version.
	 */
	public function get_plugin_data(): array;
	/**
	 * Get the plugin environment data.
	 */
	public function get_environment_data(): array;
	/**
	 * Get the plugin templates path.
	 */
	public function get_plugin_templates_path(): string;
	/**
	 * Get the plugin assets path.
	 */
	public function get_plugin_assets_path(): string;
	/**
	 * Get the plugin assets URL.
	 */
	public function get_plugin_assets_url(): string;

	/**
	 * Hook for adding order indexes.
	 */
	public function get_hook_add_order_indexes(): string;

	/**
	 * Get payment gateway ID
	 * 
	 * @return string
	 */
	public function get_payment_gateway_id(): string;

	/**
	 * Get IPN webhook identifier
	 */
	public function get_ipn_webhook(): string;

	/**
	 * Get return URL webhook identifier
	 */
	public function get_return_webhook(): string;

	/**
	 * Get event webhook identifier
	 */
	public function get_event_webhook(): string;
}

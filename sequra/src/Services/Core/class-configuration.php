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

	/**
	 * Version published in the marketplace.
	 */
	abstract public function get_marketplace_version(): string;

	/**
	 * Current store. Has keys storeId and storeName.
	 *
	 * @return array<string, mixed>
	 */
	abstract public function get_current_store(): array;

	/**
	 * List of stores. Each store is an array with storeId and storeName.
	 *
	 * @return array<array<string, mixed>>
	 */
	abstract public function get_stores(): array;

	/**
	 * Get current store ID.
	 */
	abstract public function get_store_id(): string;

	/**
	 * URL to the marketplace's plugin page.
	 */
	public function get_marketplace_url(): string {
		return 'https://wordpress.org/plugins/sequra/';
	}

	/**
	 * Get password from connection settings.
	 */
	abstract public function get_password(): string;

	/**
	 * Get enabledForServices from general settings.
	 */
	abstract public function is_enabled_for_services(): bool;

	/**
	 * Get allowFirstServicePaymentDelay from general settings.
	 */
	abstract public function allow_first_service_payment_delay(): bool;

	/**
	 * Get if registration items are allowed
	 */
	abstract public function allow_service_reg_items(): bool;

	/**
	 * Get defaultServicesEndDate from general settings.
	 */
	abstract public function get_default_services_end_date(): string;

	/**
	 * Check if current IP is allowed to use the payment gateway. 
	 */
	abstract public function is_available_for_ip(): bool;

	/**
	 * Get excludedProducts from general settings.
	 * 
	 * @return array<string>
	 */
	abstract public function get_excluded_products(): array;

	/**
	 * Get excludedCategories from general settings.
	 * 
	 * @return array<int>
	 */
	abstract public function get_excluded_categories(): array;
}

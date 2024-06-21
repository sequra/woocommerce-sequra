<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use Throwable;
use WP_Site;

/**
 * Wrapper to ease the read and write of configuration values.
 */
class Configuration_Service extends Configuration {
	
	/**
	 * Retrieves the store ID.
	 */
	public function get_store_id(): string {
		return (string) get_current_blog_id();
	}

	/**
	 * Retrieves integration name.
	 *
	 * @return string Integration name.
	 */
	public function getIntegrationName() {
		return 'WooCommerce';
	}

	/**
	 * Gets the current version of the module/integration.
	 */
	public function get_module_version(): string {
		return strval( $this->getConfigurationManager()->getConfigValue( 'version', '' ) );
	}

	/**
	 * Gets the current version of the module/integration.
	 *
	 * @param string $version The version number.
	 */
	public function set_module_version( $version ): void {
		$this->getConfigurationManager()->saveConfigValue( 'version', $version );
	}

	/**
	 * Returns async process starter url, always in http.
	 *
	 * @param string $guid Process identifier.
	 *
	 * @return string Formatted URL of async process starter endpoint.
	 */
	public function getAsyncProcessUrl( $guid ) {
		return ''; // TODO: What is this?
	}

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool {
		global $pagenow;
		return $this->get_parent_page() === $pagenow && isset( $_GET['page'] ) && $this->get_page() === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get the configuration page slug.
	 */
	public function get_page(): string {
		return 'sequra';
	}

	/**
	 * Get the configuration page parent slug.
	 */
	public function get_parent_page(): string {
		return 'options-general.php';
	}

	/**
	 * Version published in the marketplace.
	 */
	public function get_marketplace_version(): string {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$response = plugins_api(
			'plugin_information',
			array(
				'slug'   => 'sequra',
				'fields' => array( 'version' => true ),
			) 
		);
		if ( is_wp_error( $response ) || empty( $response->version ) ) {
			return '';
		}

		return $response->version;
	}

	/**
	 * Current store. Has keys storeId and storeName.
	 *
	 * @return array<string, mixed>
	 */
	public function get_current_store(): array {
		return array(
			'storeId'   => get_current_blog_id(),
			'storeName' => get_bloginfo( 'name' ),
		);
	}

	/**
	 * List of stores. Each store is an array with storeId and storeName.
	 * 
	 * @return array<array<string, mixed>>
	 */
	public function get_stores(): array {
		$stores = array();
		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			/**
			 * Available sites
			 *
			 * @var WP_Site $site
			 */
			foreach ( get_sites() as $site ) {
				$stores[] = array(
					'storeId'   => $site->blog_id,
					'storeName' => $site->blogname,
				);
			}
		} else {
			$stores[] = $this->get_current_store();
		}
		return $stores;
	}

	/**
	 * Get password from connection settings.
	 */
	public function get_password(): string {

		try {
			$config = AdminAPI::get()
			->connection( $this->get_store_id() )
			->getOnboardingData()
			->toArray();

			return $config['password'] ?? '';
		} catch ( Throwable ) {
			return '';
		}
	}

	/**
	 * Get enabledForServices from general settings.
	 */
	public function is_enabled_for_services(): bool {
		try {
			$config = $this->get_general_settings();
			return ! empty( $config['enabledForServices'] );
		} catch ( Throwable ) {
			return false;
		}
	}

	/**
	 * Get allowFirstServicePaymentDelay from general settings.
	 */
	public function allow_first_service_payment_delay(): bool {
		try {
			$config = $this->get_general_settings();
			return ! empty( $config['allowFirstServicePaymentDelay'] );
		} catch ( Throwable ) {
			return false;
		}
	}

	/**
	 * Get if registration items are allowed
	 */
	public function allow_service_reg_items(): bool {
		try {
			$config = $this->get_general_settings();
			return ! empty( $config['allowServiceRegItems'] );
		} catch ( Throwable ) {
			return false;
		}
	}

	/**
	 * Get defaultServicesEndDate from general settings.
	 */
	public function get_default_services_end_date(): string {
		try {
			$config = $this->get_general_settings();
			return $config['defaultServicesEndDate'] ?? 'PY1';
		} catch ( Throwable ) {
			return 'PY1';
		}
	}
	
	/**
	 * Get general settings as array
	 * 
	 * @throws Throwable
	 */
	protected function get_general_settings(): array {
		return AdminAPI::get()
		->generalSettings( $this->get_store_id() )
		->getGeneralSettings()
		->toArray();
	}
}

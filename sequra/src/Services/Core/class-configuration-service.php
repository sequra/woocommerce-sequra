<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

/**
 * Wrapper to ease the read and write of configuration values.
 */
class Configuration_Service extends Configuration {
	
	/**
	 * Retrieves the store ID.
	 *
	 * @return int The store ID.
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
		return $this->getConfigurationManager()->getConfigValue( 'version', '' );
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
	 */
	public function get_current_store(): array {
		return array(
			'storeId'   => get_current_blog_id(),
			'storeName' => get_bloginfo( 'name' ),
		);
	}

	/**
	 * List of stores. Each store is an array with storeId and storeName.
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
					'storeName' => get_bloginfo( 'name', '', $site->blog_id ),
				);
			}
		} else {
			$stores[] = $this->get_current_store();
		}
		return $stores;
	}
}

<?php
/**
 * Extends the Configuration class.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\Infrastructure\Configuration;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\Infrastructure\Configuration\Configuration as CoreConfiguration;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
use SeQura\WC\Services\Interface_Constants;
use Throwable;
use WP_Site;

/**
 * Extends the Configuration class. Wrapper to ease the read and write of configuration values.
 */
class Configuration extends CoreConfiguration {

	private const CONF_DB_VERSION = 'dbVersion';

	/**
	 * Marketplace version.
	 *
	 * @var ?string
	 */
	private $marketplace_version;

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
		return ''; // Not used in this implementation.
	}

	/**
	 * Check if the current page is the settings page.
	 */
	public function is_settings_page(): bool {
		return is_admin() && isset( $_GET['page'] ) && $this->get_page() === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		return 'woocommerce';
	}

	/**
	 * Version published in the marketplace.
	 */
	public function get_marketplace_version(): string {

		if ( null !== $this->marketplace_version ) {
			return $this->marketplace_version;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$response = \plugins_api(
			'plugin_information',
			array(
				'slug'   => 'sequra',
				'fields' => array( 'version' => true ),
			) 
		);
		if ( \is_wp_error( $response ) || empty( $response->version ) ) {
			return '';
		}

		$this->marketplace_version = $response->version;
		return $this->marketplace_version;
	}

	/**
	 * Current store. Has keys storeId and storeName.
	 *
	 * @return array<string, mixed>
	 */
	public function get_current_store(): array {
		return array(
			'storeId'   => \get_current_blog_id(),
			'storeName' => \get_bloginfo( 'name' ),
		);
	}

	/**
	 * List of stores. Each store is an array with storeId and storeName.
	 * 
	 * @return array<array<string, mixed>>
	 */
	public function get_stores(): array {
		$stores = array();
		if ( function_exists( 'get_sites' ) ) {
			/**
			 * Available sites
			 *
			 * @var WP_Site $site
			 */
			foreach ( \get_sites() as $site ) {
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
		} catch ( Throwable $e ) {
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
		} catch ( Throwable $e ) {
			return false;
		}
	}
	
	/**
	 * Check if current IP is allowed to use the payment gateway. 
	 */
	public function is_available_for_ip(): bool {
		try {
			$config = $this->get_general_settings();
			// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
			$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

			$allowed_ip_addresses = array();
			if ( isset( $config['allowedIPAddresses'] ) && is_array( $config['allowedIPAddresses'] ) ) {
				foreach ( $config['allowedIPAddresses'] as $ip ) {
					$ip = trim( (string) $ip );
					if ( ! empty( $ip ) ) {
						$allowed_ip_addresses[] = $ip;
					}
				}
			}

			return empty( $allowed_ip_addresses ) || in_array( $remote_addr, $allowed_ip_addresses, true );
		} catch ( Throwable $e ) {
			return true;
		}
	}
	/**
	 * Get allowFirstServicePaymentDelay from general settings.
	 */
	public function allow_first_service_payment_delay(): bool {
		try {
			$config = $this->get_general_settings();
			return ! empty( $config['allowFirstServicePaymentDelay'] );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get if registration items are allowed
	 */
	public function allow_service_reg_items(): bool {
		try {
			$config = $this->get_general_settings();
			return ! empty( $config['allowServiceRegistrationItems'] );
		} catch ( Throwable $e ) {
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
		} catch ( Throwable $e ) {
			return 'PY1';
		}
	}

	/**
	 * Get excludedProducts from general settings.
	 * 
	 * @return array<string>
	 */
	public function get_excluded_products(): array {
		try {
			$config = $this->get_general_settings();
			if ( ! empty( $config['excludedProducts'] ) && is_array( $config['excludedProducts'] ) ) {
				return $config['excludedProducts'];
			}
			return array();
		} catch ( Throwable $e ) {
			return array();
		}
	}

	/**
	 * Get excludedCategories from general settings.
	 * 
	 * @return array<int>
	 */
	public function get_excluded_categories(): array {
		try {
			$config = $this->get_general_settings();
			if ( ! empty( $config['excludedCategories'] ) && is_array( $config['excludedCategories'] ) ) {
				return array_map( 'absint', $config['excludedCategories'] );
			}
			return array();
		} catch ( Throwable $e ) {
			return array();
		}
	}
	
	/**
	 * Get general settings as array
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, mixed>
	 */
	protected function get_general_settings(): array {
		return AdminAPI::get()
		->generalSettings( $this->get_store_id() )
		->getGeneralSettings()
		->toArray();
	}
	
	/**
	 * URL to the marketplace's plugin page.
	 */
	public function get_marketplace_url(): string {
		return 'https://wordpress.org/plugins/sequra/';
	}

	/**
	 * Saves dbVersion in integration database.
	 */
	public function save_db_version( string $db_version ): void {
		$this->saveConfigValue( self::CONF_DB_VERSION, $db_version );
	}

	/**
	 * Retrieves dbVersion from integration database.
	 */
	public function get_db_version(): string {
		return $this->getConfigValue( self::CONF_DB_VERSION, '' );
	}

	/**
	 * Get general settings as array
	 * 
	 * @throws Throwable
	 * 
	 * @return array<string, mixed>
	 */
	protected function get_widget_settings(): array {
		return AdminAPI::get()
		->widgetConfiguration( $this->get_store_id() )
		->getWidgetSettings()
		->toArray();
	}

	/**
	 * Get the environment
	 */
	public function get_env(): ?string {
		$data = null;
		try {
			/**
			 * Onboarding data
			 * 
			 * @var array<string, string>
			 */
			$data = AdminAPI::get()
			->connection( $this->get_store_id() )
			->getOnboardingData()
			->toArray();
		} catch ( Throwable $e ) {
			return null;
		}
		return $data['environment'] ?? null;
	}

	/**
	 * Get platform payload
	 */
	public function get_platform(): Platform {
		/**
		 * Constants service
		 *
		 * @var Interface_Constants
		 */
		$constants = ServiceRegister::getService( Interface_Constants::class );
		/**
		 * WooCommerce data
		 *
		 * @var array<string, string>
		 */
		$woo = $constants->get_woocommerce_data();
		
		/**
		 * Environment data
		 * 
		 * @var array<string, string>
		 */
		$env = $constants->get_environment_data();

		/**
		 * Plugin data
		 * 
		 * @var array<string, string>
		 */
		$sq = $constants->get_plugin_data();

		/**
		* Filter the module version to be used in the platform options.
		* 
		* @since 3.0.0
		*/
		$version = \apply_filters(
			'sequra_platform_options_version',
			$sq['Version'] ?? ''
		);
		
		$platform_version = ( $woo['Version'] ?? '' ) . ( isset( $env['wp_version'] ) ? " + WordPress {$env['wp_version']}" : '' );

		/**
		 * Filter the platform options.
		 *
		 * @since 3.0.0
		 */
		return \apply_filters(
			'sequra_platform_options',
			new Platform(
				$this->getIntegrationName(),
				$platform_version,
				$env['uname'],
				$env['db_name'],
				$env['db_version'],
				$version,
				$env['php_version']
			) 
		);
	}
}

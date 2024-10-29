<?php
/**
 * Extends the Configuration class.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\Infrastructure\Configuration;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Services\OrderStatusSettingsService;
use SeQura\Core\Infrastructure\Configuration\Configuration as CoreConfiguration;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models\Widget_Location;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Platform;
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
		if ( function_exists( 'get_sites' ) ) {
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
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/**
	 * Get order status mappings.
	 *
	 * @return OrderStatusMapping[]
	 */
	public function get_order_statuses(): array {
		try {
			$order_status_service = ServiceRegister::getService( OrderStatusSettingsService::class );
			return $order_status_service->getOrderStatusSettings(); // @phpstan-ignore-line
		} catch ( \Throwable $e ) {
			return array();
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
			return ! empty( $config['allowServiceRegItems'] );
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
	 * Check if the widget is enabled.
	 */
	public function is_widget_enabled( ?string $payment_method, ?string $campaign, ?string $country ): bool {
		try {
			$config = $this->get_widget_settings();
			return ! empty( $config['useWidgets'] ) 
			&& ! empty( $config['displayWidgetOnProductPage'] ) 
			&& $this->is_widget_enabled_in_custom_locations( $payment_method, $campaign, $country );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Look for the mini widget configuration for a country
	 * 
	 * @param array<string, string> $mini_widgets Mini widgets configuration
	 */
	protected function get_mini_widget( string $country, array $mini_widgets ): ?array {
		foreach ( $mini_widgets as $mini_widget ) {
			if ( isset( $mini_widget['countryCode'] ) && $mini_widget['countryCode'] === $country ) {
				return $mini_widget;
			}
		}
		return null;
	}

	/**
	 * Check if the cart widget is enabled.
	 */
	public function is_cart_widget_enabled( string $country ): bool {
		try {
			$config   = $this->get_widget_settings();
			$is_valid = ! empty( $config['useWidgets'] ) 
			&& ! empty( $config['showInstallmentAmountInCartPage'] ) 
			&& isset(
				$config['selForCartPrice'], 
				$config['selForCartLocation']
			);

			if ( $is_valid && isset( $config['cartMiniWidgets'] ) ) {
				$mini_widget = $this->get_mini_widget( $country, (array) $config['cartMiniWidgets'] );
				$is_valid    = isset( $mini_widget['message'], $mini_widget['product'] );
			}
			return $is_valid;
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			return false;
		}
	}

	/**
	 * Check if the product listing widget is enabled.
	 */
	public function is_product_listing_widget_enabled( string $country ): bool {
		try {
			$config   = $this->get_widget_settings();
			$is_valid = ! empty( $config['useWidgets'] ) 
			&& ! empty( $config['showInstallmentAmountInProductListing'] ) 
			&& isset(
				$config['selForListingPrice'], 
				$config['selForListingLocation']
			);

			if ( $is_valid && isset( $config['listingMiniWidgets'] ) ) {
				$mini_widget = $this->get_mini_widget( $country, (array) $config['listingMiniWidgets'] );
				$is_valid    = isset( $mini_widget['message'], $mini_widget['product'] );
			}
			return $is_valid;
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			return false;
		}
	}

	/**
	 * Get the cart widget configuration for a country as an array
	 * 
	 * @return null|array<string, mixed> Contains the following keys:
	 * - selForPrice: string
	 * - selForLocation: string
	 * - message: string
	 * - messageBelowLimit: string
	 * - product: string
	 * - campaign: ?string
	 */
	public function get_cart_widget_config( string $country ): ?array {
		try {
			$config      = $this->get_widget_settings();
			$mini_widget = $this->get_mini_widget( $country, $config['cartMiniWidgets'] ?? array() );
			
			return array(
				'selForPrice'       => empty( $mini_widget['selForPrice'] ) ? ( $config['selForCartPrice'] ?? '' ) : $mini_widget['selForPrice'],
				'selForLocation'    => empty( $mini_widget['selForLocation'] ) ? ( $config['selForCartLocation'] ?? '' ) : $mini_widget['selForLocation'],
				'message'           => $mini_widget['message'] ?? $this->get_mini_widget_default_message( $country ),
				'messageBelowLimit' => $mini_widget['messageBelowLimit'] ?? $this->get_mini_widget_default_message_below_limit( $country ),
				'product'           => $mini_widget['product'] ?? 'pp3',
				'campaign'          => $mini_widget['campaign'] ?? null,
				// 'title'             => $mini_widget['title'] ?? null,
			);
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get the product listing widget configuration for a country as an array
	 * 
	 * @return null|array<string, mixed> Contains the following keys:
	 * - selForPrice: string
	 * - selForLocation: string
	 * - message: string
	 * - messageBelowLimit: string
	 * - product: string
	 * - campaign: ?string
	 */
	public function get_product_listing_widget_config( string $country ): ?array {
		try {
			$config      = $this->get_widget_settings();
			$mini_widget = $this->get_mini_widget( $country, $config['listingMiniWidgets'] ?? array() );
			
			return array(
				'selForPrice'       => empty( $mini_widget['selForPrice'] ) ? ( $config['selForListingPrice'] ?? '' ) : $mini_widget['selForPrice'],
				'selForLocation'    => empty( $mini_widget['selForLocation'] ) ? ( $config['selForListingLocation'] ?? '' ) : $mini_widget['selForLocation'],
				'message'           => $mini_widget['message'] ?? $this->get_mini_widget_default_message( $country ),
				'messageBelowLimit' => $mini_widget['messageBelowLimit'] ?? $this->get_mini_widget_default_message_below_limit( $country ),
				'product'           => $mini_widget['product'] ?? 'pp3',
				'campaign'          => $mini_widget['campaign'] ?? null,
				// 'title'             => $mini_widget['title'] ?? null,
			);
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get the mini widget message
	 */
	public function get_mini_widget_default_message( string $country ): string {
		return $this->get_mini_widget_default_messages()[ $country ] ?? '';
	}

	/**
	 * Get the mini widget message
	 */
	public function get_mini_widget_default_message_below_limit( string $country ): string {
		return $this->get_mini_widget_default_messages_below_limit()[ $country ] ?? '';
	}
	/**
	 * Get the mini widget message
	 */
	public function get_mini_widget_default_messages(): array {
		/**
		 * Filter the default message below limit for the mini widget.
		 *
		 * @since 3.0.0
		 * @return string The default message below limit for the mini widget.
		 */
		return apply_filters(
			'sequra_mini_widget_default_message',
			array(
				'ES' => 'Desde %s/mes con seQura',
				'FR' => 'À partir de %s/mois avec seQura',
				'IT' => 'Da %s/mese con seQura',
				'PT' => 'De %s/mês com seQura',
			) 
		);
	}

	/**
	 * Get the mini widget message
	 */
	public function get_mini_widget_default_messages_below_limit(): array {
		/**
		 * Filter the default message below limit for the mini widget.
		 *
		 * @since 3.0.0
		 * @return string The default message below limit for the mini widget.
		 */
		return apply_filters(
			'sequra_mini_widget_default_message_below_limit',
			array(
				'ES' => 'Fracciona con seQura a partir de %s',
				'FR' => 'Fraction avec seQura à partir de %s',
				'IT' => 'Frazione con seQura da %s',
				'PT' => 'Fração com seQura a partir de %s',
			) 
		);
	}

	/**
	 * Check if the widget is enabled in custom locations.
	 */
	private function is_widget_enabled_in_custom_locations( ?string $payment_method, ?string $campaign, ?string $country ): bool {
		try {
			$custom_location = $this->get_widget_custom_location( $payment_method, $campaign, $country );
			return $custom_location ? $custom_location->get_display_widget() : true;
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the widget location selector
	 */
	public function get_widget_dest_css_sel( ?string $payment_method, ?string $campaign, ?string $country ): string {
		try {
			$config          = $this->get_widget_settings();
			$sel             = $config['selForDefaultLocation'];
			$custom_location = $this->get_widget_custom_location( $payment_method, $campaign, $country );
			return $custom_location ? $custom_location->get_sel_for_target() : $sel;
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/**
	 * Get the widget price selector
	 */
	public function get_widget_price_css_sel(): string {
		try {
			$config = $this->get_widget_settings();
			return $config['selForPrice'] ?? '';
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/**
	 * Get the widget alt price selector
	 */
	public function get_widget_alt_price_css_sel(): string {
		try {
			$config = $this->get_widget_settings();
			return $config['selForAltPrice'] ?? '';
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/**
	 * Get the selector used to check when the alt price should be displayed
	 */
	public function get_widget_is_alt_price_css_sel(): string {
		try {
			$config = $this->get_widget_settings();
			return $config['selForAltPriceTrigger'] ?? '';
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/**
	 * Get the widget custom location
	 */
	private function get_widget_custom_location( ?string $payment_method, ?string $campaign, ?string $country ): ?Widget_Location {
		$config = $this->get_widget_settings();
		if ( ! empty( $payment_method ) && ! empty( $country ) && isset( $config['customLocations'] ) && is_array( $config['customLocations'] ) ) {
			foreach ( $config['customLocations'] as $location ) {
				$loc = Widget_Location::from_array( $location );
				if ( null !== $loc
					&& $loc->get_product() === $payment_method
					&& $loc->get_country() === $country
					&& ( $loc->get_campaign() ?? null ) === $campaign ) {
					return $loc;
				}
			}
		}
		return null;
	}

	/**
	 * Get widget theme
	 */
	public function get_widget_theme( ?string $payment_method, ?string $campaign, ?string $country ): string {
		try {
			$config          = $this->get_widget_settings();
			$style           = $config['widgetConfiguration'] ?? '';
			$custom_location = $this->get_widget_custom_location( $payment_method, $campaign, $country );
			return $custom_location ? $custom_location->get_widget_styles() : $style;
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/**
	 * Get asset key
	 */
	public function get_assets_key(): ?string {
		try {
			$config = $this->get_widget_settings();
			return $config['assetsKey'] ?? null;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get merchant ref
	 */
	public function get_merchant_ref( $country ): ?string {
		try {
			$countries_conf = AdminAPI::get()
			->countryConfiguration( $this->get_store_id() )
			->getCountryConfigurations()
			->toArray();

			foreach ( $countries_conf as $country_conf ) {
				if ( $country_conf['countryCode'] === $country ) {
					return $country_conf['merchantId'] ?? null;
				}
			}
			return null;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get connection settings as array
	 *
	 * @throws Throwable
	 */
	private function get_connection_settings(): array {
		return AdminAPI::get()
		->connection( $this->get_store_id() )
		->getConnectionSettings()
		->toArray();
	}

	/**
	 * Get the environment
	 */
	public function get_env(): ?string {
		$conn = null;
		try {
			$conn = $this->get_connection_settings();
		} catch ( Throwable $e ) {
			return null;
		}
		return $conn['environment'] ?? null;
	}

	/**
	 * Get platform payload
	 */
	public function get_platform(): Platform {
		/**
		 * WooCommerce data
		 *
		 * @var array<string, string>
		 */
		$woo = ServiceRegister::getService( 'woocommerce.data' );
		
		/**
		 * Environment data
		 * 
		 * @var array<string, string>
		 */
		$env = ServiceRegister::getService( 'environment.data' );

		/**
		 * Plugin data
		 * 
		 * @var array<string, string>
		 */
		$sq = ServiceRegister::getService( 'plugin.data' );

		/**
		* Filter the module version to be used in the platform options.
		* TODO: document this hook
		* 
		* @since 3.0.0
		*/
		$version = apply_filters(
			'sequra_platform_options_version',
			$sq['Version'] ?? ''
		);

		/**
		 * Filter the platform options.
		 * TODO: document this hook
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'sequra_platform_options',
			new Platform(
				$this->getIntegrationName(),
				$woo['Version'] ?? '',
				$env['uname'],
				$env['db_name'],
				$env['db_version'],
				$version,
				$env['php_version']
			) 
		);
	}
}

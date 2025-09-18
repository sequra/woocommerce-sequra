<?php
/**
 * Post install migration for version 3.0.0 of the plugin.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories\Migrations;

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\ConnectionRequest;
use SeQura\Core\BusinessLogic\AdminAPI\Connection\Requests\OnboardingRequest;
use SeQura\Core\BusinessLogic\AdminAPI\CountryConfiguration\Requests\CountryConfigurationRequest;
use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Requests\GeneralSettingsRequest;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\WidgetSettingsRequest;
use SeQura\WC\Repositories\Repository;
use SeQura\WC\Core\Extension\Infrastructure\Configuration\Configuration;
use Throwable;

/**
 * Post install migration for version 3.0.0 of the plugin.
 */
class Migration_Install_300 extends Migration {

	/**
	 * Order repository.
	 * 
	 * @var Repository
	 */
	private $order_repository;

	/**
	 * Entity repository.
	 * 
	 * @var Repository
	 */
	private $entity_repository;

	/**
	 * Queue repository.
	 * 
	 * @var Repository
	 */
	private $queue_repository;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '3.0.0';
	}

	/**
	 * Constructor
	 *
	 * @param \wpdb $wpdb Database instance.
	 */
	public function __construct( 
		\wpdb $wpdb, 
		Configuration $configuration,
		Repository $order_repository,
		Repository $entity_repository,
		Repository $queue_repository
	) {
		parent::__construct( $wpdb, $configuration );
		$this->order_repository  = $order_repository;
		$this->entity_repository = $entity_repository;
		$this->queue_repository  = $queue_repository;
	}

	/**
	 * Run the migration.
	 *
	 * @throws Throwable|Critical_Migration_Exception
	 */
	public function run(): void {
		$this->add_new_tables_to_database();
		$woocommerce_sequra_settings = (array) \get_option( 'woocommerce_sequra_settings', array() );
		if ( ! empty( $woocommerce_sequra_settings ) ) {
			$this->migrate_connection_configuration( $woocommerce_sequra_settings );
			$this->migrate_general_settings_configuration( $woocommerce_sequra_settings );
			$this->migrate_country_configuration( $woocommerce_sequra_settings );
			$this->migrate_widget_configuration( $woocommerce_sequra_settings );
		}
	}

	/**
	 * Add new tables to the database.
	 * 
	 * @throws Throwable|Critical_Migration_Exception
	 */
	private function add_new_tables_to_database(): void {
		/**
		 * Repository instances.
		 * 
		 * @var Repository[] $repos
		 */
		$repos = array(
			$this->order_repository,
			$this->entity_repository,
			$this->queue_repository,
		);

		foreach ( $repos as $repo ) {
			// Skip this migration if the table already exists.
			if ( $repo->table_exists() ) {
				continue;
			}

			try {
				$repo->create_table();
			} catch ( Throwable $e ) {
				throw new Critical_Migration_Exception( \esc_html( "Could not create the table \"{$repo->get_table_name()}\". {$e->getMessage()}" ) );
			}
		}
	}

	/**
	 * Check if the entity exists in the database.
	 */
	private function entity_exists( string $type ): bool {
		$table_name = $this->db->prefix . 'sequra_entity';
		$query      = $this->db->prepare( 'SELECT id FROM %i WHERE `type` = %s LIMIT 1', $table_name, $type );
		$result     = $this->db->get_results( $query );
		return is_array( $result ) && count( $result ) > 0;
	}

	/** Migrate connection settings from v2
	 *
	 * @param string[] $settings
	 * @throws Throwable|Exception
	 */
	private function migrate_connection_configuration( array $settings ): void {
		if ( $this->entity_exists( 'ConnectionData' ) ) {
			// Skip this migration if the data is already set.
			return;
		}

		$env_mapping = array(
			'0' => 'live',
			'1' => 'sandbox',
		);

		if ( ! isset( $settings['env'], $settings['user'], $settings['password'] ) 
		|| ! array_key_exists( $settings['env'], $env_mapping ) ) {
			// Skip this migration if the data isn't set or valid.
			return;
		}

		$response = AdminAPI::get()
		->connection( $this->configuration->get_store_id() )
		->saveOnboardingData(
			new OnboardingRequest(
				array(
					new ConnectionRequest(
						$env_mapping[ $settings['env'] ],
						'',
						strval( $settings['user'] ),
						strval( $settings['password'] ),
						'sequra'
					),
				),
				true
			)
		);

		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error migrating connection settings' );
		}
	}

	/**
	 * Get the store country code.
	 */
	private function get_store_country(): string {
		$country = strval( get_option( 'woocommerce_default_country', 'ES' ) );
		return strtoupper( explode( ':', $country )[0] );
	}

	/**
	 * Translate the widget style identifier to the new format if it is needed.
	 * If no identifier is provided, the default widget style is returned.
	 */
	private function get_widget_style( ?string $style = null ): string {
		if ( is_string( $style ) && json_decode( $style, true ) !== null ) {
			return $style; // Already in the new format.
		}
		
		$default_widget_style = '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}';
		
		$map = array(
			'L'        => '{"alignment":"left"}',
			'R'        => '{"alignment":"right"}',
			'legacy'   => '{"type":"legacy"}',
			'legacyL'  => '{"type":"legacy","alignment":"left"}',
			'legacyR'  => '{"type":"legacy","alignment":"right"}',
			'minimal'  => '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as"}',
			'minimalL' => '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as","alignment":"left"}',
			'minimalR' => '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as","alignment":"right"}',
		);

		return null === $style || ! isset( $map[ $style ] ) ? $default_widget_style : $map[ $style ];
	}

	/** Migrate country settings from v2
	 *
	 * @param string[] $settings
	 * @throws Throwable|Exception
	 */
	private function migrate_country_configuration( array $settings ): void {
		if ( $this->entity_exists( 'CountryConfiguration' ) || ! isset( $settings['merchantref'] ) ) {
			return;
		}

		$response = AdminAPI::get()
		->countryConfiguration( $this->configuration->get_store_id() )
		->saveCountryConfigurations(
			new CountryConfigurationRequest(
				array(
					array(
						'countryCode' => $this->get_store_country(),
						'merchantId'  => strval( $settings['merchantref'] ),
					),
				) 
			) 
		);
		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error migrating country settings' );
		}
	}

	/** Migrate general settings from v2
	 *
	 * @param string[] $settings
	 * @throws Throwable|Exception
	 */
	private function migrate_general_settings_configuration( array $settings ): void {

		if ( $this->entity_exists( 'GeneralSettings' ) ) {
			return;
		}

		$allowed_ip_addresses = array();
		foreach ( explode( ',', strval( $settings['test_ips'] ?? '' ) ) as $ip ) {
			$ip = trim( $ip );
			if ( ! empty( $ip ) ) {
				$allowed_ip_addresses[] = $ip;
			}
		}

		$response = AdminAPI::get()
			->generalSettings( $this->configuration->get_store_id() )
			->saveGeneralSettings(
				new GeneralSettingsRequest(
					true,
					false,
					$allowed_ip_addresses,
					null,
					null,
					strval( $settings['enable_for_virtual'] ?? 'no' ) === 'yes',
					strval( $settings['allow_payment_delay'] ?? 'no' ) === 'yes',
					strval( $settings['allow_registration_items'] ?? 'no' ) === 'yes',
					strval( $settings['default_service_end_date'] ?? 'P1Y' )
				)
			);
			
		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error general settings' );
		}
	}

	/** Migrate widget settings from v2
	 *
	 * @param string[] $settings
	 * @throws Throwable|Exception
	 */
	private function migrate_widget_configuration( array $settings ): void {

		if ( $this->entity_exists( 'WidgetSettings' ) ) {
			return;
		}

		$enabled                           = false;
		$default_sel_for_alt_price         = '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount';
		$default_sel_for_alt_price_trigger = '.variations';
		$sel_for_default_location          = '.summary>.price';
		$custom_locations                  = array();
		foreach ( $settings as $key => $value ) {
			if ( false !== strpos( $key, 'enabled_in_product_' ) ) {
				$enabled_in_product = 'yes' === $value;
				$enabled            = $enabled || $enabled_in_product;
				$product_campaign   = str_replace( 'enabled_in_product_', '', $key );
				$parts              = explode( '_', $product_campaign, 2 );

				$custom_locations[] = array(
					'selForTarget'  => $settings[ "dest_css_sel_$product_campaign" ] ?? $sel_for_default_location,
					'product'       => $parts[0],
					'displayWidget' => $enabled_in_product,
					'widgetStyles'  => $this->get_widget_style( $settings[ "widget_theme_$product_campaign" ] ?? null ),
				);
			}
		}

		$response = AdminAPI::get()
		->widgetConfiguration( $this->configuration->get_store_id() )
		->setWidgetSettings(
			new WidgetSettingsRequest(
				$enabled,
				$enabled,
				false,
				false,
				$this->get_widget_style(),
				$settings['price_css_sel'] ?? '',
				$sel_for_default_location,
				'',
				'',
				'',
				'',
				'',
				'',
				$default_sel_for_alt_price,
				$default_sel_for_alt_price_trigger,
				$custom_locations
			)
		);

		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error migrating widget settings' );
		}
	}
}

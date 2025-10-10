<?php
/**
 * Post install migration for version 4.0.0 of the plugin.
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
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\Requests\WidgetSettingsRequest;
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\PaymentMethods\Requests\GetCachedPaymentMethodsRequest;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Services\WidgetSettingsService;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use Throwable;

/**
 * Post install migration for version 4.0.0 of the plugin.
 */
class Migration_Install_400 extends Migration {

	/**
	 * Entity table.
	 * 
	 * @var string
	 */
	private $entity_table;

	/**
	 * Encryptor service.
	 * 
	 * @var EncryptorInterface
	 */
	private $encryptor;

	/**
	 * In-memory cache of payment methods.
	 *
	 * @var array<int, array{
	 *  product: string,
	 *  category: string,
	 * }>
	 */
	private $payment_methods;

	/**
	 * Store context
	 * 
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Get the plugin version when the changes were made.
	 */
	public function get_version(): string {
		return '4.0.0';
	}

	/**
	 * Constructor
	 *
	 * @param \wpdb $wpdb Database instance.
	 */
	public function __construct( 
		\wpdb $wpdb,
		EncryptorInterface $encryptor,
		StoreContext $store_context
	) {
		parent::__construct( $wpdb );
		$this->entity_table  = $this->db->prefix . 'sequra_entity';
		$this->encryptor     = $encryptor;
		$this->store_context = $store_context;
	}

	/**
	 * Run the migration.
	 *
	 * @throws Throwable|Critical_Migration_Exception
	 */
	public function run(): void {
		$this->migrate_connection_data();
		$this->migrate_country_configuration();
		$this->migrate_payment_methods();
		$this->migrate_widget_settings();
	}

	/**
	 * Migrate connection data to the new schema.
	 *
	 * @throws Throwable|Exception
	 */
	private function migrate_connection_data(): void {
		$connections = $this->get_connection_data();

		if ( empty( $connections ) ) {
			// No connection data found, nothing to migrate.
			return;
		}

		// Remove old connection data.
		foreach ( $connections as $conn ) {
			$this->db->delete( $this->entity_table, array( 'id' => $conn['id'] ) );
		}
		// Remove also old credentials data to avoid issues if the authentication fails for some deployments.
		$this->db->delete( $this->entity_table, array( 'type' => 'Credentials' ) );

		// Take the first connection only.
		$conn = $connections[0];

		$environment = $conn['connectionData']['environment'];
		$username    = $conn['connectionData']['authorizationCredentials']['username'];
		$password    = $conn['connectionData']['authorizationCredentials']['password'];

		$response = AdminAPI::get()
		->connection( $this->store_context->getStoreId() )
		->connect(
			new OnboardingRequest(
				array(
					new ConnectionRequest(
						$environment,
						'',
						$username,
						$password,
						'sequra'
					),
					new ConnectionRequest(
						$environment,
						'',
						$username,
						$password,
						'svea'
					),
				),
				true
			)
		);

		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error migrating ConnectionData' );
		}
	}

	/**
	 * Migrate country configuration to the new schema.
	 *
	 * @throws Throwable|Exception
	 */
	private function migrate_country_configuration(): void {
		$query = $this->db->prepare( 'SELECT `index_2`, `index_3` FROM %i WHERE `type` = %s AND `index_1` = %s', $this->entity_table, 'Credentials', $this->store_context->getStoreId() );
		$rows  = $this->db->get_results( $query, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return;
		}
		
		// Remove old connection data.
		$this->db->delete( $this->entity_table, array( 'type' => 'CountryConfiguration' ) );

		$country_configurations = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $row['index_2'], $row['index_3'] ) 
			|| ! is_string( $row['index_3'] ) 
			|| '' === $row['index_3'] 
			|| ! is_string( $row['index_2'] ) 
			|| '' === $row['index_2'] ) {
				continue;
			}
			$country_configurations[] = array(
				'countryCode' => $row['index_2'],
				'merchantId'  => $row['index_3'],
			);
		}

		$response = AdminAPI::get()
		->countryConfiguration( $this->store_context->getStoreId() )
		->saveCountryConfigurations(
			new CountryConfigurationRequest( $country_configurations ) 
		);

		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error migrating CountryConfiguration' );
		}
	}

	/**
	 * Migrate widget settings to the new schema.
	 * 
	 * @throws Throwable|Exception
	 */
	private function migrate_widget_settings(): void {

		$data = $this->get_widget_settings();
		if ( null === $data ) {
			// No widget settings found, nothing to migrate.
			return;
		}

		// Remove old widget settings to prevent errors due to non-existing classes.
		if ( ! $this->db->delete( $this->entity_table, array( 'id' => $data['id'] ) ) ) {
			throw new Exception( 'Error removing old WidgetSettings' );
		}

		$response = AdminAPI::get()
		->widgetConfiguration( $this->store_context->getStoreId() )
		->setWidgetSettings(
			new WidgetSettingsRequest(
				$data['widgetSettings']['displayOnProductPage'],
				$data['widgetSettings']['showInstallmentsInProductListing'],
				$data['widgetSettings']['showInstallmentsInCartPage'],
				$data['widgetSettings']['widgetConfiguration'],
				$data['widgetSettings']['widgetSettingsForProduct']['priceSelector'],
				$data['widgetSettings']['widgetSettingsForProduct']['locationSelector'],
				$data['widgetSettings']['widgetSettingsForCart']['priceSelector'],
				$data['widgetSettings']['widgetSettingsForCart']['locationSelector'],
				$data['widgetSettings']['widgetSettingsForCart']['widgetProduct'],
				$data['widgetSettings']['widgetSettingsForListing']['widgetProduct'],
				$data['widgetSettings']['widgetSettingsForListing']['priceSelector'],
				$data['widgetSettings']['widgetSettingsForListing']['locationSelector'],
				$data['widgetSettings']['widgetSettingsForProduct']['altPriceSelector'],
				$data['widgetSettings']['widgetSettingsForProduct']['altPriceTriggerSelector'],
				array_map(
					function ( $item ) {
						return array(
							'selForTarget'  => $item['customLocationSelector'],
							'product'       => $item['product'],
							'displayWidget' => $item['displayWidget'],
							'widgetStyles'  => $item['customWidgetStyle'],
						);
					},
					$data['widgetSettings']['widgetSettingsForProduct']['customWidgetSettings']
				)
			)
		);

		if ( ! $response->isSuccessful() ) {
			throw new Exception( 'Error migrating WidgetSettings' );
		}
	}

	/**
	 * Migrate payment methods to the new schema.
	 * 
	 * @throws Throwable|Exception
	 */
	private function migrate_payment_methods(): void {
		// Remove old payment methods if any.
		$this->db->delete( $this->entity_table, array( 'type' => 'PaymentMethods' ) );
		$this->db->delete( $this->entity_table, array( 'type' => 'PaymentMethod' ) );

		// Force the caching of payment methods for every merchant.
		foreach ( $this->get_merchant_ids() as $merchant_id ) {
			$response = CheckoutAPI::get()
			->cachedPaymentMethods( $this->store_context->getStoreId() )
			->getCachedPaymentMethods(
				new GetCachedPaymentMethodsRequest( $merchant_id )
			);
			if ( ! $response->isSuccessful() ) {
				throw new Exception( \esc_html( 'Error migrating PaymentMethods for merchant ' . $merchant_id ) );
			}
		}
	}

	/**
	 * Get ConnectionData from the database.
	 *
	 * @return array<int,array{
	 *      id: int,
	 *      connectionData: array{
	 *          environment: string,
	 *          authorizationCredentials: array{
	 *              username: string,
	 *              password: string
	 *          }
	 *      }
	 *  }>|null Connection data or null if not found.
	 */
	private function get_connection_data(): ?array {
		$query = $this->db->prepare( 'SELECT `id`, `data` FROM %i WHERE `type` = %s AND `index_1` = %s', $this->entity_table, 'ConnectionData', $this->store_context->getStoreId() );
		$rows  = $this->db->get_results( $query, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return null;
		}
		$data_arr = array();

		foreach ( $rows as $row ) {
			/**
			 * Data
			 * 
			 * @var array{
			 *      connectionData: array{
			 *          environment: string,
			 *          authorizationCredentials: array{
			 *              username: string,
			 *              password: string
			 *          }
			 *      }
			 *  }|null $data
			 */
			$data = isset( $row['data'] ) && is_string( $row['data'] ) ? json_decode( $row['data'], true ) : null;
			if (
			isset(
				$row['id'],
				$data['connectionData']['environment'],
				$data['connectionData']['authorizationCredentials']['username'],
				$data['connectionData']['authorizationCredentials']['password']
			)
			&& is_string( $data['connectionData']['environment'] )
			&& is_string( $data['connectionData']['authorizationCredentials']['password'] )
			&& is_string( $data['connectionData']['authorizationCredentials']['username'] )
			) {
				$data['id'] = (int) $row['id'];
				$data['connectionData']['authorizationCredentials']['password'] = $this->encryptor->decrypt( $data['connectionData']['authorizationCredentials']['password'] );
				$data_arr[] = $data;
			}
		}
		return $data_arr;
	}

	/**
	 * Get an array of merchant ids from the database.
	 * 
	 * @return array<string> Merchant ids.
	 */
	private function get_merchant_ids(): array {
		$query = $this->db->prepare( 'SELECT DISTINCT `index_3` FROM %i WHERE `type` = %s', $this->entity_table, 'Credentials' );
		$rows  = $this->db->get_results( $query, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$ids = array();
		foreach ( $rows as $row ) {
			if ( isset( $row['index_3'] ) && is_string( $row['index_3'] ) && '' !== $row['index_3'] ) {
				$ids[] = $row['index_3'];
			}
		}
		return $ids;
	}

	/**
	 * Get WidgetSettings from the database.
	 *
	 * @return array{
	 *      id: int,
	 *      widgetSettings: array{
	 *          enabled: bool,
	 *          displayOnProductPage: bool,
	 *          showInstallmentsInProductListing: bool,
	 *          showInstallmentsInCartPage: bool,
	 *          widgetConfiguration: string,
	 *          widgetSettingsForProduct: array{
	 *              priceSelector: string,
	 *              locationSelector: string,
	 *              altPriceSelector: string,
	 *              altPriceTriggerSelector: string,
	 *              customWidgetSettings: array<array{
	 *                  customLocationSelector: string,
	 *                  product: string,
	 *                  displayWidget: bool,
	 *                  customWidgetStyle: string
	 *              }>
	 *          },
	 *          widgetSettingsForCart: array{
	 *              priceSelector: string,
	 *              locationSelector: string,
	 *              widgetProduct: string
	 *          },
	 *          widgetSettingsForListing: array{
	 *              priceSelector: string,
	 *              locationSelector: string,
	 *              widgetProduct: string
	 *          }
	 *      }
	 *  }|null WidgetSettings or null if not found.
	 */
	private function get_widget_settings(): ?array {
		$query = $this->db->prepare( 'SELECT `id`, `data` FROM %i WHERE `type` = %s AND `index_1` = %s LIMIT 1', $this->entity_table, 'WidgetSettings', $this->store_context->getStoreId() );
		$row   = $this->db->get_row( $query, ARRAY_A );
		$data  = isset( $row['data'] ) && is_string( $row['data'] ) ? json_decode( $row['data'], true ) : null;

		if ( ! is_array( $data ) || ! isset( $row['id'] ) ) {
			return null;
		}

		$custom_widget_settings = array();
		$source                 = $data['widgetSettings']['widgetSettingsForProduct']['customWidgetSettings'] ?? ( $data['widgetSettings']['widgetLocationConfiguration']['customLocations'] ?? array() );
		foreach ( $source as $value ) {
			if ( ! isset( $value['product'] ) || ! is_string( $value['product'] ) || '' === $value['product'] ) {
				// Skip empty product entries.
				continue;
			}
			$custom_widget_settings[] = array(
				'customLocationSelector' => $value['customLocationSelector'] ?? $value['selForTarget'] ?? '',
				'product'                => $value['product'],
				'displayWidget'          => $value['displayWidget'] ?? true,
				'customWidgetStyle'      => $value['customWidgetStyle'] ?? $value['widgetStyles'] ?? '',
			);
		}

		$cart_widget_product = $data['widgetSettings']['widgetSettingsForCart']['widgetProduct'] ?? '';
		if ( '' === $cart_widget_product ) {
			$cart_widget_product = $this->get_first_payment_method_product( WidgetSettingsService::WIDGET_SUPPORTED_CATEGORIES_ON_CART_PAGE );
		}
		$listing_widget_product = $data['widgetSettings']['widgetSettingsForListing']['widgetProduct'] ?? '';
		if ( '' === $listing_widget_product ) {
			$listing_widget_product = $this->get_first_payment_method_product( WidgetSettingsService::MINI_WIDGET_SUPPORTED_CATEGORIES_ON_PRODUCT_LISTING_PAGE );
		}

		return array(
			'id'             => (int) $row['id'],
			'widgetSettings' => array(
				'enabled'                          => $data['widgetSettings']['enabled'] ?? false,
				'displayOnProductPage'             => $data['widgetSettings']['displayOnProductPage'] ?? false,
				'showInstallmentsInProductListing' => $data['widgetSettings']['showInstallmentsInProductListing'] ?? false,
				'showInstallmentsInCartPage'       => $data['widgetSettings']['showInstallmentsInCartPage'] ?? false,
				'widgetConfiguration'              => $data['widgetSettings']['widgetConfiguration'] ?? '',
				'widgetSettingsForProduct'         => array(
					'priceSelector'           => $data['widgetSettings']['widgetSettingsForProduct']['priceSelector'] ?? ( $data['widgetSettings']['widgetLocationConfiguration']['selForPrice'] ?? '' ),
					'locationSelector'        => $data['widgetSettings']['widgetSettingsForProduct']['locationSelector'] ?? ( $data['widgetSettings']['widgetLocationConfiguration']['selForDefaultLocation'] ?? '' ),
					'altPriceSelector'        => $data['widgetSettings']['widgetSettingsForProduct']['altPriceSelector'] ?? ( $data['widgetSettings']['widgetLocationConfiguration']['selForAltPrice'] ?? '' ),
					'altPriceTriggerSelector' => $data['widgetSettings']['widgetSettingsForProduct']['altPriceTriggerSelector'] ?? ( $data['widgetSettings']['widgetLocationConfiguration']['selForAltPriceTrigger'] ?? '' ),
					'customWidgetSettings'    => $custom_widget_settings,
				),
				'widgetSettingsForCart'            => array(
					'priceSelector'    => $data['widgetSettings']['widgetSettingsForCart']['priceSelector'] ?? ( $data['widgetSettings']['cartMiniWidgetConfiguration']['selForPrice'] ?? '' ),
					'locationSelector' => $data['widgetSettings']['widgetSettingsForCart']['locationSelector'] ?? ( $data['widgetSettings']['cartMiniWidgetConfiguration']['selForDefaultLocation'] ?? '' ),
					'widgetProduct'    => $cart_widget_product,
				),
				'widgetSettingsForListing'         => array(
					'priceSelector'    => $data['widgetSettings']['widgetSettingsForListing']['priceSelector'] ?? ( $data['widgetSettings']['listingMiniWidgetConfiguration']['selForPrice'] ?? '' ),
					'locationSelector' => $data['widgetSettings']['widgetSettingsForListing']['locationSelector'] ?? ( $data['widgetSettings']['listingMiniWidgetConfiguration']['selForDefaultLocation'] ?? '' ),
					'widgetProduct'    => $listing_widget_product,
				),
			),
		);
	}

	/**
	 * Get the product name of the first payment method available.
	 *
	 * @param array $categories List of categories to filter payment methods.
	 * @return string Product name or empty string if no payment methods found.
	 */
	private function get_first_payment_method_product( $categories ): string {
		if ( null === $this->payment_methods ) {
			$query = $this->db->prepare( 'SELECT `data` FROM %i WHERE `type` = %s AND `index_1` = %s', $this->entity_table, 'PaymentMethod', $this->store_context->getStoreId() );
			$rows  = $this->db->get_results( $query, ARRAY_A );
			if ( ! is_array( $rows ) ) {
				return '';
			}
			$this->payment_methods = array();
			foreach ( $rows as $row ) {
				/**
				 * Data
				 * 
				 * @var array{
				 *  seQuraPaymentMethod: array{
				 *      product: string,
				 *      category: string
				 *  }
				 * }|null $data
				 */
				$data = isset( $row['data'] ) && is_string( $row['data'] ) ? json_decode( $row['data'], true ) : null;
				$data = $data['seQuraPaymentMethod'] ?? null;
				if ( is_array( $data ) 
					&& isset( $data['product'], $data['category'] ) 
				&& is_string( $data['product'] ) 
				&& is_string( $data['category'] ) 
				&& '' !== $data['product']
				&& '' !== $data['category'] 
				) {
					// Check for duplicates before adding.
					$found = false;
					foreach ( $this->payment_methods as $pm ) {
						if ( $pm['product'] === $data['product'] ) {
							$found = true;
							break;
						}
					}
					if ( $found ) {
						continue;
					}

					$this->payment_methods[] = array(
						'product'  => $data['product'],
						'category' => $data['category'],
					);
				}
			}

			// Reorder payment methods so product 'pp3' comes first if available.
			usort(
				$this->payment_methods,
				function ( $a, $b ) {
					if ( 'pp3' === $a['product'] ) {
						return -1;
					}
					if ( 'pp3' === $b['product'] ) {
						return 1;
					}
					return 0;
				}
			);
		}

		foreach ( $this->payment_methods as $payment_method ) {
			if ( in_array( $payment_method['category'], $categories, true ) ) {
				return $payment_method['product'];
			}
		}
		return '';
	}
}

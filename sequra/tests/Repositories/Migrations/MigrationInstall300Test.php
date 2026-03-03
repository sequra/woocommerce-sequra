<?php
/**
 * Tests for the Migration_Install_300 class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use SeQura\Core\BusinessLogic\DataAccess\ConnectionData\Entities\ConnectionData;
use SeQura\Core\BusinessLogic\DataAccess\CountryConfiguration\Entities\CountryConfiguration;
use SeQura\Core\BusinessLogic\DataAccess\Credentials\Entities\Credentials;
use SeQura\Core\BusinessLogic\DataAccess\Deployments\Entities\Deployment;
use SeQura\Core\BusinessLogic\DataAccess\GeneralSettings\Entities\GeneralSettings;
use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
use SeQura\Core\BusinessLogic\DataAccess\StatisticalData\Entities\StatisticalData;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Core\Infrastructure\Configuration\ConfigEntity;
use SeQura\Core\Infrastructure\ORM\RepositoryRegistry;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Core\Infrastructure\TaskExecution\QueueItem;
use SeQura\WC\Repositories\Migrations\Migration_Install_300;
use SeQura\WC\Repositories\Repository;
use WP_UnitTestCase;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

class MigrationInstall300Test extends WP_UnitTestCase {

	/**
	 * Migration instance.
	 * @var Migration_Install_300
	 */
	private $migration;

	/**
	 * WordPress database object.
	 * 
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Settings from version 2 of the plugin.
	 * 
	 * @var array
	 */
	private $v2_settings;

	/**
	 * Encryptor instance.
	 * 
	 * @var EncryptorInterface
	 */
	private $encryptor;

	private const V2_OPTION_NAME = 'woocommerce_sequra_settings';
	
	public function set_up() {
		global $wpdb;
		$this->wpdb        = $wpdb;
		$this->encryptor   = ServiceRegister::getService( EncryptorInterface::class );
		$this->v2_settings = array(
			'enabled'                           => 'yes',
			'title'                             => 'Flexible payment with seQura',
			'sign-up-info'                      => '',
			'merchantref'                       => 'dummy_automated_tests',
			'user'                              => 'dummy_automated_tests',
			'password'                          => getenv( 'DUMMY_PASSWORD' ),
			'assets_secret'                     => getenv( 'DUMMY_ASSETS_KEY' ),
			'enable_for_virtual'                => 'no',
			'default_service_end_date'          => 'P1Y',
			'allow_payment_delay'               => 'no',
			'allow_registration_items'          => 'no',
			'env'                               => '1',
			'test_ips'                          => '212.80.211.33',
			'debug'                             => 'no',
			'active_methods_info'               => '',
			'communication_fields'              => '',
			'price_css_sel'                     => '.summary .price>.amount,.summary .price ins .amount',
			'enabled_in_product_i1'             => 'yes',
			'dest_css_sel_i1'                   => '.summary .price>.amount,.summary .price ins .amount',
			'widget_theme_i1'                   => 'L',
			'enabled_in_product_sp1_permanente' => 'yes',
			'dest_css_sel_sp1_permanente'       => '.summary .price>.amount,.summary .price ins .amount',
			'widget_theme_sp1_permanente'       => '{"alignment":"left"}',
			'enabled_in_product_pp3'            => 'yes',
			'dest_css_sel_pp3'                  => '.summary .price>.amount,.summary .price ins .amount',
			'widget_theme_pp3'                  => 'L',
		);
		/**
		 * Order repository.
		 * 
		 * @var Repository $order_repository
		 */
		$order_repository = RepositoryRegistry::getRepository( SeQuraOrder::class );
		/**
		 * Entity repository.
		 * 
		 * @var Repository $entity_repository
		 */
		$entity_repository = RepositoryRegistry::getRepository( ConfigEntity::class );
		/**
		 * Queue item repository.
		 * 
		 * @var Repository $queue_item_repository
		 */
		$queue_item_repository = RepositoryRegistry::getRepository( QueueItem::class );

		/**
		 * Store context service.
		 *  
		 * @var StoreContext $store_context
		 */
		$store_context = ServiceRegister::getService( StoreContext::class );

		$this->migration = new Migration_Install_300(
			$this->wpdb,
			$order_repository,
			$entity_repository,
			$queue_item_repository,
			$store_context
		);
		$this->set_v2_data();
		$this->drop_v3_tables();
	}

	public function tear_down() {
		$this->remove_v2_data();
	}

	/**
	 * Insert the data from version 2 of the plugin.
	 */
	private function set_v2_data(): void {
		update_option(
			self::V2_OPTION_NAME,
			$this->v2_settings
		);
	}

	/**
	 * Remove the data from version 2 of the plugin.
	 */
	private function remove_v2_data(): void {
		delete_option( self::V2_OPTION_NAME );
	}

	/**
	 * Drop tables in the database for version 3.
	 */
	private function drop_v3_tables(): void {
		$tables = array(
			$this->wpdb->prefix . 'sequra_order',
			$this->wpdb->prefix . 'sequra_entity',
			$this->wpdb->prefix . 'sequra_queue',
		);
		foreach ( $tables as $table ) {
			$this->wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
	}

	public function testRun() {
		$this->migration->run();
		$this->assertSame( $this->v2_settings, get_option( self::V2_OPTION_NAME ) );

		/** @var Repository */
		$order_repository = RepositoryRegistry::getRepository( SeQuraOrder::class );
		/** @var Repository */
		$queue_item_repository = RepositoryRegistry::getRepository( QueueItem::class );
		/** @var Repository */
		$entity_repository                = RepositoryRegistry::getRepository( ConfigEntity::class );
		$deployment_repository            = RepositoryRegistry::getRepository( Deployment::class );
		$credentials_repository           = RepositoryRegistry::getRepository( Credentials::class );
		$connection_data_repository       = RepositoryRegistry::getRepository( ConnectionData::class );
		$statistical_data_repository      = RepositoryRegistry::getRepository( StatisticalData::class );
		$general_settings_repository      = RepositoryRegistry::getRepository( GeneralSettings::class );
		$country_configuration_repository = RepositoryRegistry::getRepository( CountryConfiguration::class );
		$widget_settings_repository       = RepositoryRegistry::getRepository( WidgetSettings::class );

		// Check tables exist.
		$this->assertTrue( $order_repository->table_exists() );
		$this->assertTrue( $entity_repository->table_exists() );
		$this->assertTrue( $queue_item_repository->table_exists() );
		
		// Check Deployment.
		$entities = $deployment_repository->select();
		$this->assertCount( 2, $entities );
		$actual_array = array();
		foreach ( $entities as $entity ) {
			$actual_array[] = $entity->toArray();
		}
		$expected_array = array(
			array(
				'class_name'   => Deployment::class,
				'id'           => $actual_array[0]['id'], // ID is auto-generated and is not so relevant for the test.
				'storeId'      => '1',
				'deploymentId' => 'sequra',
				'deployment'   => array(
					'id'      => 'sequra',
					'name'    => 'seQura',
					'live'    => array(
						'api_base_url'    => 'https://live.sequrapi.com/',
						'assets_base_url' => 'https://live.sequracdn.com/assets/',
					),
					'sandbox' => array(
						'api_base_url'    => 'https://sandbox.sequrapi.com/',
						'assets_base_url' => 'https://sandbox.sequracdn.com/assets/',
					),
				),
			),
			array(
				'class_name'   => Deployment::class,
				'id'           => $actual_array[1]['id'], // ID is auto-generated and is not so relevant for the test.
				'storeId'      => '1',
				'deploymentId' => 'svea',
				'deployment'   => array(
					'id'      => 'svea',
					'name'    => 'SVEA',
					'live'    => array(
						'api_base_url'    => 'https://live.sequra.svea.com/',
						'assets_base_url' => 'https://live.cdn.sequra.svea.com/assets/',
					),
					'sandbox' => array(
						'api_base_url'    => 'https://sandbox.sequra.svea.com/',
						'assets_base_url' => 'https://sandbox.cdn.sequra.svea.com/assets/',
					),
				),
			),
		);
		$this->assertSame( $expected_array, $actual_array );
		
		// Check Credentials.
		$entities = $credentials_repository->select();
		$this->assertCount( 1, $entities );
		$actual_array   = $entities[0]->toArray();
		$expected_array = array(
			'class_name'  => Credentials::class,
			'id'          => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'     => '1',
			'country'     => 'ES',
			'merchantId'  => 'dummy_automated_tests',
			'credentials' => array(
				'merchantId' => 'dummy_automated_tests',
				'country'    => 'ES',
				'currency'   => 'EUR',
				'assetsKey'  => getenv( 'DUMMY_ASSETS_KEY' ),
				'payload'    => array(
					'ref'               => 'dummy_automated_tests',
					'name'              => null,
					'country'           => 'ES',
					'allowed_countries' => array( 'ES' ),
					'currency'          => 'EUR',
					'assets_key'        => getenv( 'DUMMY_ASSETS_KEY' ),
					'contract_options'  => array( 'allow_first_instalment_delay', 'with_registration' ),
					'extra_information' => array(
						'type'         => 'regular',
						'phone_number' => '',
					),
					'verify_signature'  => false,
					'signature_secret'  => $actual_array['credentials']['payload']['signature_secret'], // Let's keep this secret.
					'confirmation_path' => 'default',
					'realm'             => 'sequra',
				),
				'deployment' => 'sequra',
			),
		);
		$this->assertSame( $expected_array, $actual_array );

		// Check ConnectionData.
		$entities = $connection_data_repository->select();
		$this->assertCount( 1, $entities );
		$actual_array = $entities[0]->toArray();
		$actual_array['connectionData']['authorizationCredentials']['password'] = $this->encryptor->decrypt( $actual_array['connectionData']['authorizationCredentials']['password'] );
		$expected_array = array(
			'class_name'     => ConnectionData::class,
			'id'             => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'        => '1',
			'deployment'     => 'sequra',
			'connectionData' => array(
				'environment'              => 'sandbox',
				'merchantId'               => '',
				'deployment'               => 'sequra',
				'authorizationCredentials' => array(
					'username' => 'dummy_automated_tests',
					'password' => getenv( 'DUMMY_PASSWORD' ),
				),
			),
		);
		$this->assertSame( $expected_array, $actual_array );

		// Check StatisticalData.
		$entities = $statistical_data_repository->select();
		$this->assertCount( 1, $entities );
		$actual_array   = $entities[0]->toArray();
		$expected_array = array(
			'class_name'      => StatisticalData::class,
			'id'              => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'         => '1',
			'statisticalData' => array(
				'sendStatisticalData' => true,
			),
		);
		$this->assertSame( $expected_array, $actual_array );

		// Check GeneralSettings.
		$entities = $general_settings_repository->select();
		$this->assertCount( 1, $entities );
		$actual_array   = $entities[0]->toArray();
		$expected_array = array(
			'class_name'      => GeneralSettings::class,
			'id'              => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'         => '1',
			'generalSettings' => array(
				'sendOrderReportsPeriodicallyToSeQura' => true,
				'showSeQuraCheckoutAsHostedPage'       => false,
				'allowedIPAddresses'                   => array( '212.80.211.33' ),
				'excludedProducts'                     => array(),
				'excludedCategories'                   => array(),
				'enabledForServices'                   => array(),
				'allowFirstServicePaymentDelay'        => array(),
				'allowServiceRegistrationItems'        => array(),
				'defaultServicesEndDate'               => 'P1Y',
			),
		);
		$this->assertSame( $expected_array, $actual_array );

		// Check CountryConfiguration.
		$entities = $country_configuration_repository->select();
		$this->assertCount( 1, $entities );
		$actual_array   = $entities[0]->toArray();
		$expected_array = array(
			'class_name'            => CountryConfiguration::class,
			'id'                    => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'               => '1',
			'countryConfigurations' => array(
				array(
					'countryCode' => 'ES',
					'merchantId'  => 'dummy_automated_tests',
				),
			),
		);
		$this->assertSame( $expected_array, $actual_array );

		// Check WidgetSettings.
		$entities = $widget_settings_repository->select();
		$this->assertCount( 1, $entities );
		$actual_array   = $entities[0]->toArray();
		$expected_array = array(
			'class_name'     => WidgetSettings::class,
			'id'             => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'        => '1',
			'widgetSettings' => array(
				'displayOnProductPage'             => true,
				'showInstallmentsInProductListing' => false,
				'showInstallmentsInCartPage'       => false,
				'widgetConfiguration'              => '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
				'widgetSettingsForProduct'         => array(
					'priceSelector'           => '.summary .price>.amount,.summary .price ins .amount',
					'locationSelector'        => '.summary>.price',
					'altPriceSelector'        => '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount',
					'altPriceTriggerSelector' => '.variations',
					'customWidgetSettings'    => array(
						array(
							'customLocationSelector' => '.summary .price>.amount,.summary .price ins .amount',
							'product'                => 'i1',
							'displayWidget'          => true,
							'customWidgetStyle'      => '{"alignment":"left"}',
						),
						array(
							'customLocationSelector' => '.summary .price>.amount,.summary .price ins .amount',
							'product'                => 'sp1',
							'displayWidget'          => true,
							'customWidgetStyle'      => '{"alignment":"left"}',
						),
						array(
							'customLocationSelector' => '.summary .price>.amount,.summary .price ins .amount',
							'product'                => 'pp3',
							'displayWidget'          => true,
							'customWidgetStyle'      => '{"alignment":"left"}',
						),
					),
				),
				'widgetSettingsForCart'            => array(
					'priceSelector'    => '',
					'locationSelector' => '',
					'widgetProduct'    => '',
				),
				'widgetSettingsForListing'         => array(
					'priceSelector'    => '',
					'locationSelector' => '',
					'widgetProduct'    => '',
				),
			),
		);
		$this->assertSame( $expected_array, $actual_array );
	}
}

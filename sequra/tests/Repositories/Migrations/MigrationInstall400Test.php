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
use SeQura\Core\BusinessLogic\DataAccess\PaymentMethod\Entities\PaymentMethod;
use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
use SeQura\Core\BusinessLogic\DataAccess\StatisticalData\Entities\StatisticalData;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Core\Infrastructure\ORM\RepositoryRegistry;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Repositories\Migrations\Migration_Install_400;
use WP_UnitTestCase;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

class MigrationInstall400Test extends WP_UnitTestCase {

	/**
	 * Migration instance.
	 * @var Migration_Install_400
	 */
	private $migration;

	/**
	 * WordPress database object.
	 * 
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Encryptor instance.
	 * 
	 * @var EncryptorInterface
	 */
	private $encryptor;
	
	public function set_up() {
		global $wpdb;
		$this->wpdb      = $wpdb;
		$this->encryptor = ServiceRegister::getService( EncryptorInterface::class );

		/**
		 * Store context service.
		 *  
		 * @var StoreContext $store_context
		 */
		$store_context = ServiceRegister::getService( StoreContext::class );

		$this->migration = new Migration_Install_400(
			$this->wpdb,
			$this->encryptor,
			$store_context
		);
		$this->truncate_tables();
	}

	/**
	 * Truncate tables in the database for version 3.
	 */
	private function truncate_tables(): void {
		$tables = array(
			$this->wpdb->prefix . 'sequra_entity',
		);
		foreach ( $tables as $table ) {
			$this->wpdb->query( "TRUNCATE TABLE $table" );
		}
	}

	public function dataProvider_Run() {
		return array(
			array( 'sequra_entity_migration_install_300_by_v3.sql' ),
			array( 'sequra_entity_migration_install_300_by_v4.sql' ),
		);
	}

	/**
	 * @dataProvider dataProvider_Run
	 */
	public function testRun( $sql_file ) {
		// Setup.
		$sql = str_replace( 
			array(
				'{{PASSWORD}}',
				'{{ASSETS_KEY}}',
			),
			array(
				$this->encryptor->encrypt( getenv( 'DUMMY_PASSWORD' ) ),
				getenv( 'DUMMY_ASSETS_KEY' ),
			),
			file_get_contents( __DIR__ . '/../../data/' . $sql_file ) 
		);

		$this->wpdb->query( $sql );

		// Execute.
		$this->migration->run();
		
		// Assert.
		
		// Check Deployment.
		$deployment_repo = RepositoryRegistry::getRepository( Deployment::class );
		$entities        = $deployment_repo->select();
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
		$credentials_repo = RepositoryRegistry::getRepository( Credentials::class );
		$entities         = $credentials_repo->select();
		$this->assertCount( 4, $entities );
		$actual_array   = array();
		$expected_array = array();
		$countries      = array( 'ES', 'PT', 'IT', 'FR' );
		$merchant_ids   = array( 'dummy_automated_tests', 'dummy_automated_tests_pt', 'dummy_automated_tests_it', 'dummy_automated_tests_fr' );
		$deployments    = array( 'sequra', 'svea' );
		foreach ( $entities as $entity ) {
			$actual_entity = $entity->toArray();
			
			$this->assertTrue( in_array( $actual_entity['country'], $countries, true ) );
			$this->assertTrue( in_array( $actual_entity['credentials']['country'], $countries, true ) );
			$this->assertTrue( in_array( $actual_entity['credentials']['payload']['country'], $countries, true ) );

			$this->assertTrue( in_array( $actual_entity['merchantId'], $merchant_ids, true ) );
			$this->assertTrue( in_array( $actual_entity['credentials']['merchantId'], $merchant_ids, true ) );
			$this->assertTrue( in_array( $actual_entity['credentials']['payload']['ref'], $merchant_ids, true ) );
			

			$this->assertTrue( in_array( $actual_entity['credentials']['deployment'], $deployments, true ) );
			$this->assertTrue( ! empty( array_intersect( $actual_entity['credentials']['payload']['allowed_countries'], $countries ) ) );
			$this->assertTrue( in_array( $actual_entity['credentials']['payload']['realm'], $deployments, true ) );


			$actual_array[]   = $actual_entity;
			$expected_array[] = array(
				'class_name'  => Credentials::class,
				'id'          => $actual_entity['id'], // ID is auto-generated and is not so relevant for the test.
				'storeId'     => '1',
				'country'     => $actual_entity['country'],
				'merchantId'  => $actual_entity['merchantId'], // Merchant ID is not so relevant for the test.
				'credentials' => array(
					'merchantId' => $actual_entity['credentials']['merchantId'], // Merchant ID is not so relevant for the test.
					'country'    => $actual_entity['credentials']['country'],
					'currency'   => 'EUR',
					'assetsKey'  => getenv( 'DUMMY_ASSETS_KEY' ),
					'payload'    => array(
						'ref'               => $actual_entity['credentials']['payload']['ref'], // Ref is not so relevant for the test.
						'name'              => null,
						'country'           => $actual_entity['credentials']['payload']['country'],
						'allowed_countries' => $actual_entity['credentials']['payload']['allowed_countries'],
						'currency'          => 'EUR',
						'assets_key'        => getenv( 'DUMMY_ASSETS_KEY' ),
						'contract_options'  => $actual_entity['credentials']['payload']['contract_options'],
						'extra_information' => array(
							'type'         => 'regular',
							'phone_number' => '',
						),
						'verify_signature'  => false,
						'signature_secret'  => $actual_entity['credentials']['payload']['signature_secret'], // Let's keep this secret.
						'confirmation_path' => $actual_entity['credentials']['payload']['confirmation_path'],
						'realm'             => $actual_entity['credentials']['payload']['realm'],
					),
					'deployment' => $actual_entity['credentials']['deployment'],
				),
			);
		}
		
		$this->assertSame( $expected_array, $actual_array );

		// Check ConnectionData.
		$conn_data_repo = RepositoryRegistry::getRepository( ConnectionData::class );
		$entities       = $conn_data_repo->select();
		$this->assertCount( 2, $entities );
		$actual_array   = array();
		$expected_array = array();
		foreach ( $entities as $entity ) {
			$actual_entity = $entity->toArray();
			
			$this->assertTrue( in_array( $actual_entity['deployment'], $deployments, true ) );
			
			$actual_entity['connectionData']['authorizationCredentials']['password'] = $this->encryptor->decrypt( $actual_entity['connectionData']['authorizationCredentials']['password'] );
			$actual_array[]   = $actual_entity;
			$expected_array[] = array(
				'class_name'     => ConnectionData::class,
				'id'             => $actual_entity['id'], // ID is auto-generated and is not so relevant for the test.
				'storeId'        => '1',
				'deployment'     => $actual_entity['deployment'],
				'connectionData' => array(
					'environment'              => 'sandbox',
					'merchantId'               => '',
					'deployment'               => $actual_entity['deployment'],
					'authorizationCredentials' => array(
						'username' => 'dummy_automated_tests',
						'password' => getenv( 'DUMMY_PASSWORD' ),
					),
				),
			);
		}
		
		$this->assertSame( $expected_array, $actual_array );

		// Check PaymentMethod.
		$payment_method_repo = RepositoryRegistry::getRepository( PaymentMethod::class );
		$entities            = $payment_method_repo->select();
		$this->assertCount( 6, $entities );
		$actual_array   = array();
		$expected_array = array();
		$products       = array( 'i1', 'sp1', 'pp3' );
		foreach ( $entities as $entity ) {
			$actual_entity = $entity->toArray();
			
			$this->assertTrue( in_array( $actual_entity['merchantId'], $merchant_ids, true ) );
			$this->assertTrue( in_array( $actual_entity['product'], $products, true ) );
			$this->assertTrue( array_key_exists( 'product', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'title', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'long_title', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'category', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'cost', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'starts_at', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'ends_at', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'claim', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'description', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'icon', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'cost_description', $actual_entity['seQuraPaymentMethod'] ) );

			$this->assertTrue( array_key_exists( 'campaign', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'min_amount', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( array_key_exists( 'max_amount', $actual_entity['seQuraPaymentMethod'] ) );
			$this->assertTrue( in_array( $actual_entity['seQuraPaymentMethod']['product'], $products, true ) );
			
			$actual_array[] = $actual_entity;
			
			$expected_array[] = array(
				'class_name'          => PaymentMethod::class,
				'id'                  => $actual_entity['id'], // ID is auto-generated and is not so relevant for the test.
				'storeId'             => '1',
				'merchantId'          => $actual_entity['merchantId'],
				'product'             => $actual_entity['product'],
				'seQuraPaymentMethod' => $actual_entity['seQuraPaymentMethod'],
			);
		}
		
		$this->assertSame( $expected_array, $actual_array );

		// Check StatisticalData.
		$statistical_data_repo = RepositoryRegistry::getRepository( StatisticalData::class );
		$entities              = $statistical_data_repo->select();
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
		$general_settings_repo = RepositoryRegistry::getRepository( GeneralSettings::class );
		$entities              = $general_settings_repo->select();
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
		$country_config_repo = RepositoryRegistry::getRepository( CountryConfiguration::class );
		$entities            = $country_config_repo->select();
		$this->assertCount( 1, $entities );
		$actual_array = $entities[0]->toArray();
		// Reorder $actual_array['countryConfigurations'] by countryCode to make the test stable.
		usort(
			$actual_array['countryConfigurations'],
			function ( $a, $b ) {
				return strcmp( $a['countryCode'], $b['countryCode'] );
			}
		);
		$expected_array = array(
			'class_name'            => CountryConfiguration::class,
			'id'                    => $actual_array['id'], // ID is auto-generated and is not so relevant for the test.
			'storeId'               => '1',
			'countryConfigurations' => array(
				array(
					'countryCode' => 'ES',
					'merchantId'  => 'dummy_automated_tests',
				),
				array(
					'countryCode' => 'FR',
					'merchantId'  => 'dummy_automated_tests_fr',
				),
				array(
					'countryCode' => 'IT',
					'merchantId'  => 'dummy_automated_tests_it',
				),
				array(
					'countryCode' => 'PT',
					'merchantId'  => 'dummy_automated_tests_pt',
				),
			),
		);
		$this->assertSame( $expected_array, $actual_array );

		// Check WidgetSettings.
		$widget_settings_repo = RepositoryRegistry::getRepository( WidgetSettings::class );
		$entities             = $widget_settings_repo->select();
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
					'widgetProduct'    => $actual_array['widgetSettings']['widgetSettingsForCart']['widgetProduct'],
				),
				'widgetSettingsForListing'         => array(
					'priceSelector'    => '',
					'locationSelector' => '',
					'widgetProduct'    => $actual_array['widgetSettings']['widgetSettingsForListing']['widgetProduct'],
				),
			),
		);
		$this->assertSame( $expected_array, $actual_array );
	}
}

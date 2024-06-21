<?php
/**
 * Implementation for the core bootstrap class.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\GeneralSettingsController;
use SeQura\Core\BusinessLogic\AdminAPI\PromotionalWidgets\PromotionalWidgetsController;
use SeQura\Core\BusinessLogic\BootstrapComponent;
use SeQura\Core\BusinessLogic\DataAccess\ConnectionData\Entities\ConnectionData;
use SeQura\Core\BusinessLogic\DataAccess\OrderSettings\Entities\OrderStatusSettings;
use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Entities\WidgetSettings;
use SeQura\Core\BusinessLogic\DataAccess\SendReport\Entities\SendReport;
use SeQura\Core\BusinessLogic\DataAccess\StatisticalData\Entities\StatisticalData;
use SeQura\Core\BusinessLogic\DataAccess\TransactionLog\Entities\TransactionLog;
use SeQura\Core\BusinessLogic\DataAccess\CountryConfiguration\Entities\CountryConfiguration;
use SeQura\Core\BusinessLogic\DataAccess\GeneralSettings\Entities\GeneralSettings;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\Services\CountryConfigurationService;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\RepositoryContracts\GeneralSettingsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Services\CategoryService;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Services\GeneralSettingsService;
use SeQura\Core\BusinessLogic\Domain\Integration\Category\CategoryServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\Disconnect\DisconnectServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\SellingCountries\SellingCountriesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\Store\StoreServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\Version\VersionServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Builders\CreateOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\RepositoryContracts\OrderStatusSettingsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Services\OrderStatusSettingsService;
use SeQura\Core\BusinessLogic\Domain\PaymentMethod\Services\PaymentMethodsService;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\RepositoryContracts\WidgetSettingsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Services\WidgetSettingsService;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Core\Infrastructure\Configuration\ConfigEntity;
use SeQura\Core\Infrastructure\Configuration\ConfigurationManager;
use SeQura\Core\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\Logger\LoggerConfiguration;
use SeQura\Core\Infrastructure\ORM\RepositoryRegistry;
use SeQura\Core\Infrastructure\ServiceRegister as Reg;
use SeQura\Core\Infrastructure\TaskExecution\Process;
use SeQura\Core\Infrastructure\TaskExecution\QueueItem;
use SeQura\Core\Infrastructure\Utility\TimeProvider;
use SeQura\WC\Controllers\Hooks\Asset\Assets_Controller;
use SeQura\WC\Controllers\Hooks\Asset\Interface_Assets_Controller;
use SeQura\WC\Controllers\Hooks\I18n\I18n_Controller;
use SeQura\WC\Controllers\Hooks\I18n\Interface_I18n_Controller;
use SeQura\WC\Controllers\Hooks\Payment\Interface_Payment_Controller;
use SeQura\WC\Controllers\Hooks\Payment\Payment_Controller;
use SeQura\WC\Controllers\Hooks\Settings\Interface_Settings_Controller;
use SeQura\WC\Controllers\Hooks\Settings\Settings_Controller;
use SeQura\WC\Controllers\Rest\General_Settings_REST_Controller;
use SeQura\WC\Controllers\Rest\Log_REST_Controller;
use SeQura\WC\Controllers\Rest\Onboarding_REST_Controller;
use SeQura\WC\Controllers\Rest\Payment_REST_Controller;
use SeQura\WC\Controllers\Settings_Controller;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\General_Settings_Controller;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\PromotionalWidgets\Promotional_Widgets_Controller;
use SeQura\WC\Core\Extension\BusinessLogic\DataAccess\GeneralSettings\Repositories\General_Settings_Repository;
use SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PromotionalWidgets\Repositories\Widget_Settings_Repository;
use SeQura\WC\Repositories\Entity_Repository;
use SeQura\WC\Repositories\Migrations\Migration_Install_300;
use SeQura\WC\Repositories\Queue_Item_Repository;
use SeQura\WC\Repositories\SeQura_Order_Repository;
use SeQura\WC\Services\Cart\Cart_Service;
use SeQura\WC\Services\Cart\Interface_Cart_Service;
use SeQura\WC\Services\Core\Category_Service;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\Core\Configuration_Service;
use SeQura\WC\Services\Core\Create_Order_Request_Builder;
use SeQura\WC\Services\Core\Default_Logger_Adapter;
use SeQura\WC\Services\Core\Disconnect_Service;
use SeQura\WC\Services\Core\Encryptor;
use SeQura\WC\Services\Core\Interface_Create_Order_Request_Builder;
use SeQura\WC\Services\Core\Order_Status_Service;
use SeQura\WC\Services\Core\Order_Status_Settings_Service;
use SeQura\WC\Services\Core\Selling_Countries_Service;
use SeQura\WC\Services\Core\Shop_Logger_Adapter;
use SeQura\WC\Services\Core\Store_Service;
use SeQura\WC\Services\Core\Version_Service;
use SeQura\WC\Services\Shopper\Shopper_Service;
use SeQura\WC\Services\Shopper\Interface_Shopper_Service;
use SeQura\WC\Services\I18n\I18n;
use SeQura\WC\Services\I18n\Interface_I18n;
use SeQura\WC\Services\Interface_Log_File;
use SeQura\WC\Services\Interface_Logger_Service;
use SeQura\WC\Services\Interface_Migration_Manager;
use SeQura\WC\Services\Log_File;
use SeQura\WC\Services\Logger_Service;
use SeQura\WC\Services\Migration_Manager;
use SeQura\WC\Services\Order\Interface_Order_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Method_Service;
use SeQura\WC\Services\Payment\Interface_Payment_Service;
use SeQura\WC\Services\Payment\Payment_Method_Service;
use SeQura\WC\Services\Payment\Payment_Service;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway_Block_Support;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use SeQura\WC\Services\Pricing\Pricing_Service;
use SeQura\WC\Services\Product\Interface_Product_Service;
use SeQura\WC\Services\Product\Product_Service;

/**
 * Implementation for the core bootstrap class.
 */
class Bootstrap extends BootstrapComponent {

	/**
	 * Cache for Service instances.
	 *
	 * @var array<string, mixed>
	 */
	private static $cache = array();

	/**
	 * Initialize the bootstrap.
	 */
	public static function init(): void {
		self::initConstants();
		parent::init();

		Reg::registerService(
			Plugin::class,
			static function () {
				return new Plugin(
					Reg::getService( 'plugin.data' ),
					Reg::getService( 'plugin.basename' ),
					Reg::getService( Interface_Migration_Manager::class ),
					Reg::getService( Interface_I18n_Controller::class ),
					Reg::getService( Interface_Assets_Controller::class ),
					Reg::getService( Interface_Settings_Controller::class ),
					Reg::getService( Interface_Payment_Controller::class ),
					Reg::getService( General_Settings_REST_Controller::class ),
					Reg::getService( Onboarding_REST_Controller::class ),
					Reg::getService( Payment_REST_Controller::class ),
					Reg::getService( Log_REST_Controller::class )
				);
			}
		);
	}

	/**
	 * Initializes constants.
	 */
	public static function initConstants(): void {
		Reg::registerService(
			'plugin.dir_path',
			static function () {
				if ( ! isset( self::$cache['plugin.dir_path'] ) ) {
					self::$cache['plugin.dir_path'] = trailingslashit( dirname( __DIR__, 1 ) );
				}
				return self::$cache['plugin.dir_path'];
			}
		);
		Reg::registerService(
			'plugin.file_path',
			static function () {
				if ( ! isset( self::$cache['plugin.file_path'] ) ) {
					self::$cache['plugin.file_path'] = Reg::getService( 'plugin.dir_path' ) . 'sequra.php';
				}
				return self::$cache['plugin.file_path'];
			}
		);
		Reg::registerService(
			'plugin.log_file_path',
			static function () {
				if ( ! isset( self::$cache['plugin.log_file_path'] ) ) {
					self::$cache['plugin.log_file_path'] = Reg::getService( 'plugin.dir_path' ) . 'sequra.{storeId}.log';
				}
				return self::$cache['plugin.log_file_path'];
			}
		);

		Reg::registerService(
			'plugin.basename',
			static function () {
				if ( ! isset( self::$cache['plugin.basename'] ) ) {
					self::$cache['plugin.basename'] = plugin_basename( Reg::getService( 'plugin.dir_path' ) . 'sequra.php' );
				}
				return self::$cache['plugin.basename'];
			}
		);

		Reg::registerService(
			'plugin.dir_url',
			static function () {
				if ( ! isset( self::$cache['plugin.dir_url'] ) ) {
					self::$cache['plugin.dir_url'] = plugin_dir_url( Reg::getService( 'plugin.file_path' ) );
				}
				return self::$cache['plugin.dir_url'];
			}
		);

		Reg::registerService(
			'plugin.rest_namespace',
			static function () {
				if ( ! isset( self::$cache['plugin.rest_namespace'] ) ) {
					self::$cache['plugin.rest_namespace'] = 'sequra/v1';
				}
				return self::$cache['plugin.rest_namespace'];
			}
		);

		Reg::registerService(
			'woocommerce.data',
			static function () {
				if ( ! isset( self::$cache['woocommerce.data'] ) ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					self::$cache['woocommerce.data'] = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
				}
				return self::$cache['woocommerce.data'];
			}
		);

		Reg::registerService(
			'plugin.data',
			static function () {
				if ( ! isset( self::$cache['plugin.data'] ) ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$add_wc_headers = function ( $headers ) {
						$headers['WC requires at least'] = 'WC requires at least';
						return $headers;
					};
					add_filter( 'extra_plugin_headers', $add_wc_headers );
					$data = get_plugin_data( Reg::getService( 'plugin.file_path' ) );
					remove_filter( 'extra_plugin_headers', $add_wc_headers );
					$data['RequiresWC'] = $data['WC requires at least'];
					unset( $data['WC requires at least'] );

					self::$cache['plugin.data'] = $data;
				}
				return self::$cache['plugin.data'];
			}
		);

		Reg::registerService(
			'environment.data',
			static function () {
				if ( ! isset( self::$cache['environment.data'] ) ) {
					/**
					 * Database instance.
					 *
					 * @var \wpdb $wpdb
					 */
					$wpdb = Reg::getService( \wpdb::class );
				
					self::$cache['environment.data'] = array(
						'php_version' => phpversion(),
						'php_os'      => PHP_OS,
						'uname'       => php_uname(),
						'db_name'     => false === strpos( strtolower( $wpdb->db_server_info() ), 'mariadb' ) ? 'mysql' : 'mariadb',
						'db_version'  => $wpdb->db_version() ?? '',
					);
				}
				return self::$cache['environment.data'];
			}
		);

		Reg::registerService(
			'plugin.templates_path',
			static function () {
				if ( ! isset( self::$cache['plugin.templates_path'] ) ) {
					self::$cache['plugin.templates_path'] = Reg::getService( 'plugin.dir_path' ) . 'templates/';
				}
				return self::$cache['plugin.templates_path'];
			}
		);
		Reg::registerService(
			'plugin.assets_path',
			static function () {
				if ( ! isset( self::$cache['plugin.assets_path'] ) ) {
					self::$cache['plugin.assets_path'] = Reg::getService( 'plugin.dir_path' ) . 'assets';
				}
				return self::$cache['plugin.assets_path'];
			}
		);
		Reg::registerService(
			'plugin.assets_url',
			static function () {
				if ( ! isset( self::$cache['plugin.assets_url'] ) ) {
					self::$cache['plugin.assets_url'] = Reg::getService( 'plugin.dir_url' ) . '/assets';
				}
				return self::$cache['plugin.assets_url'];
			}
		);
	}

	/**
	 * Initializes services and utilities.
	 */
	protected static function initServices(): void {
		parent::initServices();

		Reg::registerService(
			EncryptorInterface::class,
			static function () {
				if ( ! isset( self::$cache[ EncryptorInterface::class ] ) ) {
					self::$cache[ EncryptorInterface::class ] = new Encryptor();
				}
				return self::$cache[ EncryptorInterface::class ];
			}
		);

		Reg::registerService(
			DisconnectServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ DisconnectServiceInterface::class ] ) ) {
					self::$cache[ DisconnectServiceInterface::class ] = new Disconnect_Service(
						array(
							RepositoryRegistry::getRepository( ConnectionData::class ),
							RepositoryRegistry::getRepository( StatisticalData::class ),
							RepositoryRegistry::getRepository( SendReport::class ),
							RepositoryRegistry::getRepository( QueueItem::class ),
							RepositoryRegistry::getRepository( SeQuraOrder::class ), // TODO: why is this here?
						)
					);
				}
				return self::$cache[ DisconnectServiceInterface::class ];
			}
		);

		Reg::registerService(
			SellingCountriesServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ SellingCountriesServiceInterface::class ] ) ) {
					self::$cache[ SellingCountriesServiceInterface::class ] = new Selling_Countries_Service();
				}
				return self::$cache[ SellingCountriesServiceInterface::class ];
			}
		);

		Reg::registerService(
			StoreServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ StoreServiceInterface::class ] ) ) {
					self::$cache[ StoreServiceInterface::class ] = new Store_Service();
				}
				return self::$cache[ StoreServiceInterface::class ];
			}
		);

		Reg::registerService(
			VersionServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ VersionServiceInterface::class ] ) ) {
					self::$cache[ VersionServiceInterface::class ] = new Version_Service(
						Reg::getService( 'plugin.data' )['Version']
					);
				}
				return self::$cache[ VersionServiceInterface::class ];
			}
		);

		Reg::registerService(
			CategoryServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ CategoryServiceInterface::class ] ) ) {
					self::$cache[ CategoryServiceInterface::class ] = new Category_Service();
				}
				return self::$cache[ CategoryServiceInterface::class ];
			}
		);

		Reg::registerService(
			ShopOrderStatusesServiceInterface::class,
			static function () {
				if ( ! isset( self::$cache[ ShopOrderStatusesServiceInterface::class ] ) ) {
					self::$cache[ ShopOrderStatusesServiceInterface::class ] = new Order_Status_Service();
				}
				return self::$cache[ ShopOrderStatusesServiceInterface::class ];
			}
		);

		// Override OrderStatusSettingsService service to use the WC order statuses.
		Reg::registerService(
			OrderStatusSettingsService::class,
			static function () {
				if ( ! isset( self::$cache[ OrderStatusSettingsService::class ] ) ) {
					self::$cache[ OrderStatusSettingsService::class ] = new Order_Status_Settings_Service(
						Reg::getService( OrderStatusSettingsRepositoryInterface::class ),
						Reg::getService( ShopOrderStatusesServiceInterface::class )
					);
				}
				return self::$cache[ OrderStatusSettingsService::class ];
			}
		);

		Reg::registerService(
			Interface_Log_File::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Log_File::class ] ) ) {
					self::$cache[ Interface_Log_File::class ] = new Log_File(
						Reg::getService( 'plugin.log_file_path' )
					);
				}
				return self::$cache[ Interface_Log_File::class ];
			}
		);
		Reg::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			static function () {
				if ( ! isset( self::$cache[ ShopLoggerAdapter::CLASS_NAME ] ) ) {
					self::$cache[ ShopLoggerAdapter::CLASS_NAME ] = new Shop_Logger_Adapter();
				}
				return self::$cache[ ShopLoggerAdapter::CLASS_NAME ];
			}
		);
		Reg::registerService(
			DefaultLoggerAdapter::CLASS_NAME,
			static function () {
				if ( ! isset( self::$cache[ DefaultLoggerAdapter::CLASS_NAME ] ) ) {
					self::$cache[ DefaultLoggerAdapter::CLASS_NAME ] = new Default_Logger_Adapter(
						Reg::getService( Interface_Log_File::class ),
						Reg::getService( TimeProvider::CLASS_NAME )
					);
				}
				return self::$cache[ DefaultLoggerAdapter::CLASS_NAME ];
			}
		);

		Reg::registerService(
			LoggerConfiguration::class, 
			static function () {
				return LoggerConfiguration::getInstance();
			}
		);

		Reg::registerService(
			ConfigurationManager::CLASS_NAME,
			static function () {
				if ( ! isset( self::$cache[ ConfigurationManager::CLASS_NAME ] ) ) {
					self::$cache[ ConfigurationManager::CLASS_NAME ] = Services\Core\Configuration_Manager::getInstance();
				}
				return self::$cache[ ConfigurationManager::CLASS_NAME ];
			}
		);

		Reg::registerService(
			Configuration::class,
			static function () {
				if ( ! isset( self::$cache[ Configuration::class ] ) ) {
					self::$cache[ Configuration::class ] = Configuration_Service::getInstance();
				}
				return self::$cache[ Configuration::class ];
			}
		);
		Reg::registerService(
			\SeQura\Core\Infrastructure\Configuration\Configuration::CLASS_NAME,
			static function () {
				return Reg::getService( Configuration::class );
			}
		);

		// Plugin services.
		Reg::registerService(
			Interface_Migration_Manager::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Migration_Manager::class ] ) ) {
					self::$cache[ Interface_Migration_Manager::class ] = new Migration_Manager(
						Reg::getService( Configuration::CLASS_NAME ),
						Reg::getService( 'plugin.data' )['Version'],
						array(
							new Migration_Install_300( Reg::getService( \wpdb::class ) ),
						)
					);
				}
				return self::$cache[ Interface_Migration_Manager::class ];
			}
		);

		Reg::registerService(
			Interface_I18n::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_I18n::class ] ) ) {
					self::$cache[ Interface_I18n::class ] = new I18n();
				}
				return self::$cache[ Interface_I18n::class ];
			}
		);
		Reg::registerService(
			Interface_Logger_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Logger_Service::class ] ) ) {
					self::$cache[ Interface_Logger_Service::class ] = new Logger_Service(
						Reg::getService( LoggerConfiguration::class ),
						Reg::getService( Interface_Log_File::class )
					);
				}
				return self::$cache[ Interface_Logger_Service::class ];
			}
		);
		Reg::registerService(
			Interface_Payment_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Payment_Service::class ] ) ) {
					self::$cache[ Interface_Payment_Service::class ] = new Payment_Service(
						Reg::getService( Configuration::class ),
						Reg::getService( Interface_I18n::class ),
						Reg::getService( Interface_Create_Order_Request_Builder::class )
					);
				}
				return self::$cache[ Interface_Payment_Service::class ];
			}
		);
		Reg::registerService(
			Sequra_Payment_Gateway_Block_Support::class,
			static function () {
				if ( ! isset( self::$cache[ Sequra_Payment_Gateway_Block_Support::class ] ) ) {
					self::$cache[ Sequra_Payment_Gateway_Block_Support::class ] = new Sequra_Payment_Gateway_Block_Support(
						Reg::getService( 'plugin.assets_path' ),
						Reg::getService( 'plugin.assets_url' )
					);
				}
				return self::$cache[ Sequra_Payment_Gateway_Block_Support::class ];
			}
		);
		Reg::registerService(
			Interface_Create_Order_Request_Builder::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Create_Order_Request_Builder::class ] ) ) {
					self::$cache[ Interface_Create_Order_Request_Builder::class ] = new Create_Order_Request_Builder(
						Reg::getService( Interface_Payment_Service::class ),
						Reg::getService( Interface_Cart_Service::class ),
						Reg::getService( Configuration::class ),
						Reg::getService( Interface_Product_Service::class ),
						Reg::getService( Interface_Order_Service::class ),
						Reg::getService( Interface_I18n::class ),
						Reg::getService( Interface_Shopper_Service::class )
					);
				}
				return self::$cache[ CreateOrderRequestBuilder::class ];
			}
		);
		Reg::registerService(
			Interface_Pricing_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Pricing_Service::class ] ) ) {
					self::$cache[ Interface_Pricing_Service::class ] = new Pricing_Service();
				}
				return self::$cache[ Interface_Pricing_Service::class ];
			}
		);
		Reg::registerService(
			Interface_Cart_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Cart_Service::class ] ) ) {
					self::$cache[ Interface_Cart_Service::class ] = new Cart_Service(
						Reg::getService( Interface_Product_Service::class ),
						Reg::getService( Configuration::class ),
						Reg::getService( Interface_Pricing_Service::class ),
						Reg::getService( Interface_Order_Service::class ),
						Reg::getService( Interface_Shopper_Service::class )
					);
				}
				return self::$cache[ Interface_Cart_Service::class ];
			}
		);
		Reg::registerService(
			Interface_Product_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Product_Service::class ] ) ) {
					self::$cache[ Interface_Product_Service::class ] = new Product_Service(
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Interface_Product_Service::class ];
			}
		);
		Reg::registerService(
			Interface_Shopper_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Shopper_Service::class ] ) ) {
					self::$cache[ Interface_Shopper_Service::class ] = new Shopper_Service();
				}
				return self::$cache[ Interface_Shopper_Service::class ];
			}
		);
	}

	/**
	 * Initializes repositories.
	 */
	protected static function initRepositories(): void {

		Reg::registerService(
			\wpdb::class,
			static function () {
				if ( ! isset( self::$cache[ \wpdb::class ] ) ) {
					global $wpdb;
					self::$cache[ \wpdb::class ] = $wpdb;
				}
				return self::$cache[ \wpdb::class ];
			}
		);

		parent::initRepositories();

		// Extend GeneralSettingsRepository.
		Reg::registerService(
			GeneralSettingsRepositoryInterface::class,
			static function () {
				return new General_Settings_Repository(
					RepositoryRegistry::getRepository( GeneralSettings::getClassName() ),
					Reg::getService( StoreContext::class )
				);
			}
		);

		// Extend WidgetSettingsRepository.
		Reg::registerService(
			WidgetSettingsRepositoryInterface::class,
			static function () {
				return new Widget_Settings_Repository(
					RepositoryRegistry::getRepository( WidgetSettings::getClassName() ),
					Reg::getService( StoreContext::class )
				);
			}
		);

		RepositoryRegistry::registerRepository( ConfigEntity::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( QueueItem::class, Queue_Item_Repository::class );
		RepositoryRegistry::registerRepository( Process::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( ConnectionData::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( OrderStatusSettings::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( StatisticalData::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( CountryConfiguration::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( GeneralSettings::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( SeQuraOrder::class, SeQura_Order_Repository::class );
		RepositoryRegistry::registerRepository( WidgetSettings::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( SendReport::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( StatisticalData::class, Entity_Repository::class );
		RepositoryRegistry::registerRepository( TransactionLog::class, Entity_Repository::class );
	}

	/**
	 * Initializes controllers.
	 */
	protected static function initControllers(): void {
		parent::initControllers();

		// Extend GeneralSettingsController.
		Reg::registerService(
			GeneralSettingsController::class,
			static function () {
				return new General_Settings_Controller(
					Reg::getService( GeneralSettingsService::class ),
					Reg::getService( CategoryService::class )
				);
			}
		);

		// Extend PromotionalWidgetsController.
		Reg::registerService(
			PromotionalWidgetsController::class,
			static function () {
				return new Promotional_Widgets_Controller(
					Reg::getService( WidgetSettingsService::class )
				);
			}
		);

		// Plugin controllers.
		Reg::registerService(
			Interface_I18n_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_I18n_Controller::class ] ) ) {
					$data   = Reg::getService( 'plugin.data' );
					$domain = $data['TextDomain'];
					self::$cache[ Interface_I18n_Controller::class ] = new I18n_Controller( 
						$domain . $data['DomainPath'],
						$domain,
						Reg::getService( Interface_Logger_Service::class ),
						Reg::getService( 'plugin.templates_path' )
					);
				}
				return self::$cache[ Interface_I18n_Controller::class ];
			}
		);
		Reg::registerService(
			Interface_Assets_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Assets_Controller::class ] ) ) {
					self::$cache[ Interface_Assets_Controller::class ] = new Assets_Controller(
						Reg::getService( 'plugin.assets_url' ), 
						Reg::getService( 'plugin.assets_path' ), 
						Reg::getService( 'plugin.data' )['Version'],
						Reg::getService( Interface_I18n::class ),
						Reg::getService( Interface_Logger_Service::class ),
						Reg::getService( 'plugin.templates_path' ),
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Interface_Assets_Controller::class ];
			}
		);
		Reg::registerService(
			Interface_Settings_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Settings_Controller::class ] ) ) {
					self::$cache[ Interface_Settings_Controller::class ] = new Settings_Controller(
						Reg::getService( 'plugin.templates_path' ),
						Reg::getService( Configuration::class ),
						Reg::getService( Interface_Logger_Service::class )
					);
				}
				return self::$cache[ Interface_Settings_Controller::class ];
			}
		);
		Reg::registerService(
			Interface_Payment_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Payment_Controller::class ] ) ) {
					self::$cache[ Interface_Payment_Controller::class ] = new Payment_Controller(
						Reg::getService( Interface_Logger_Service::class ),
						Reg::getService( 'plugin.templates_path' ),
						Reg::getService( Interface_Payment_Service::class )
					);
				}
				return self::$cache[ Interface_Payment_Controller::class ];
			}
		);
		Reg::registerService(
			Onboarding_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Onboarding_REST_Controller::class ] ) ) {
					self::$cache[ Onboarding_REST_Controller::class ] = new Onboarding_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' ),
						Reg::getService( Interface_Logger_Service::class )
					);
				}
				return self::$cache[ Onboarding_REST_Controller::class ];
			}
		);
		Reg::registerService(
			Payment_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Payment_REST_Controller::class ] ) ) {
					self::$cache[ Payment_REST_Controller::class ] = new Payment_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' ),
						Reg::getService( Interface_Logger_Service::class )
					);
				}
				return self::$cache[ Payment_REST_Controller::class ];
			}
		);
		Reg::registerService(
			General_Settings_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ General_Settings_REST_Controller::class ] ) ) {
					self::$cache[ General_Settings_REST_Controller::class ] = new General_Settings_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' ),
						Reg::getService( Interface_Logger_Service::class )
					);
				}
				return self::$cache[ General_Settings_REST_Controller::class ];
			}
		);
		Reg::registerService(
			Log_REST_Controller::class,
			static function () {
				if ( ! isset( self::$cache[ Log_REST_Controller::class ] ) ) {
					self::$cache[ Log_REST_Controller::class ] = new Log_REST_Controller(
						Reg::getService( 'plugin.rest_namespace' ),
						Reg::getService( Interface_Logger_Service::class )
					);
				}
				return self::$cache[ Log_REST_Controller::class ];
			}
		);
		Reg::registerService(
			Interface_Payment_Method_Service::class,
			static function () {
				if ( ! isset( self::$cache[ Interface_Payment_Method_Service::class ] ) ) {
					self::$cache[ Interface_Payment_Method_Service::class ] = new Payment_Method_Service(
						Reg::getService( Configuration::class )
					);
				}
				return self::$cache[ Interface_Payment_Method_Service::class ];
			}
		);
	}
}
